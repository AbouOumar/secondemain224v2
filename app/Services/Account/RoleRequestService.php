<?php

namespace App\Services\Account;

use App\Enums\UserRole;
use App\Exceptions\RoleRequestException;
use App\Models\RoleRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Notification\FirebaseNotificationService;
use Illuminate\Support\Facades\DB;

class RoleRequestService
{
    private const LABELS = ['motard' => 'livreur', 'revendeur_pro' => 'revendeur pro'];

    public function __construct(private FirebaseNotificationService $notifications) {}

    /** Règles de validation des détails demandés, selon le rôle. */
    public static function detailRules(string $role): array
    {
        return match ($role) {
            'motard' => [
                'details.vehicle' => 'required|string|max:50',
                'details.plate' => 'nullable|string|max:20',
                'details.zone' => 'required|string|max:100',
            ],
            'revendeur_pro' => [
                'details.business_name' => 'required|string|max:100',
                'details.address' => 'required|string|max:191',
                'details.registration_number' => 'nullable|string|max:50',
            ],
            default => [],
        };
    }

    /**
     * @param array<string, mixed>|null $details null à l'inscription (le candidat complétera ensuite)
     * @throws RoleRequestException rôle non demandable ou déjà détenu (403), demande déjà en cours (409)
     */
    public function request(User $user, string $role, ?array $details): RoleRequest
    {
        if (! in_array($role, RoleRequest::REQUESTABLE, true)) {
            throw new RoleRequestException('Ce rôle ne peut pas être demandé.', 422);
        }
        if (! in_array($user->role?->value, ['acheteur', 'vendeur'], true)) {
            throw new RoleRequestException('Votre compte a déjà un rôle spécial.', 403);
        }

        return DB::transaction(function () use ($user, $role, $details) {
            if (RoleRequest::where('user_id', $user->id)->where('status', RoleRequest::PENDING)->lockForUpdate()->exists()) {
                throw new RoleRequestException('Une demande est déjà en cours d\'examen.', 409);
            }

            return RoleRequest::create([
                'user_id' => $user->id,
                'role' => $role,
                'status' => RoleRequest::PENDING,
                'details' => $details,
            ]);
        });
    }

    /** Le candidat retire sa demande en attente. */
    public function withdraw(User $user): void
    {
        RoleRequest::where('user_id', $user->id)->where('status', RoleRequest::PENDING)->delete();
    }

    /** Accorde le rôle. Faux si la demande avait déjà été traitée. */
    public function approve(RoleRequest $request, User $admin): bool
    {
        $done = DB::transaction(function () use ($request, $admin) {
            if (! $this->finish($request, RoleRequest::APPROVED, null, $admin)) {
                return false;
            }

            $user = $request->user;
            $user->forceFill(['role' => $request->role])->save();
            if ($request->role === 'motard') {
                $user->forceFill(['rider_status' => 'hors_ligne'])->save();
            }
            Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0, 'currency' => 'GNF']);

            return true;
        });

        if ($done) {
            $label = self::LABELS[$request->role] ?? $request->role;
            $this->notifications->send($request->user, 'Demande acceptée', "Votre compte {$label} est activé.", 'role.traite', ['role' => $request->role]);
        }

        return $done;
    }

    /** Refuse la demande, le rôle ne change pas. Faux si elle avait déjà été traitée. */
    public function reject(RoleRequest $request, User $admin, ?string $note): bool
    {
        if (! $this->finish($request, RoleRequest::REJECTED, $note, $admin)) {
            return false;
        }

        $label = self::LABELS[$request->role] ?? $request->role;
        $this->notifications->send(
            $request->user,
            'Demande refusée',
            "Votre demande de compte {$label} n'a pas été retenue" . ($note ? " : {$note}." : '.'),
            'role.traite',
            ['role' => $request->role],
        );

        return true;
    }

    /** Fait passer une demande en attente à son état final ; faux si quelqu'un l'a déjà traitée. */
    private function finish(RoleRequest $request, string $status, ?string $note, User $admin): bool
    {
        return RoleRequest::whereKey($request->id)->where('status', RoleRequest::PENDING)
            ->update(['status' => $status, 'note' => $note, 'processed_by' => $admin->id, 'processed_at' => now()]) === 1;
    }
}
