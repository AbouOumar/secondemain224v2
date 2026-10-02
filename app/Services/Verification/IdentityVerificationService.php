<?php

namespace App\Services\Verification;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Gère le cycle de vie d'une vérification d'identité vendeur : soumission,
 * approbation, rejet. Garde `users.is_verified` / `verified_at` synchronisés
 * pour un accès rapide (badge) et maintient `users.verification_documents`
 * pour la compatibilité avec le contrat JSON déjà consommé par l'app mobile.
 */
class IdentityVerificationService
{
    public function submit(User $user, string $documentType, UploadedFile $document, UploadedFile $selfie): IdentityVerification
    {
        // Disque privé : jamais accessible par une URL publique, seulement
        // via la route d'administration protégée.
        $documentPath = $document->store('verification_documents', IdentityVerification::DISK);
        $selfiePath = $selfie->store('verification_selfies', IdentityVerification::DISK);

        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'document_type' => $documentType,
            'document_path' => $documentPath,
            'selfie_path' => $selfiePath,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $user->update([
            'verification_documents' => [
                'type' => $documentType,
                'document_path' => $documentPath,
                'selfie_path' => $selfiePath,
                'submitted_at' => $verification->submitted_at->toIso8601String(),
                'status' => 'pending',
            ],
        ]);

        return $verification;
    }

    public function approve(IdentityVerification $verification, User $admin): void
    {
        $verification->update([
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $verification->user->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $this->syncLegacyDocumentsStatus($verification, 'approved');
    }

    public function reject(IdentityVerification $verification, User $admin, ?string $reason): void
    {
        $verification->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->syncLegacyDocumentsStatus($verification, 'rejected', $reason);
    }

    private function syncLegacyDocumentsStatus(IdentityVerification $verification, string $status, ?string $reason = null): void
    {
        $user = $verification->user;
        $docs = $user->verification_documents ?? [];
        $docs['status'] = $status;
        $docs[$status . '_at'] = now()->toIso8601String();
        if ($reason !== null) {
            $docs['rejection_reason'] = $reason;
        }
        $user->update(['verification_documents' => $docs]);
    }
}
