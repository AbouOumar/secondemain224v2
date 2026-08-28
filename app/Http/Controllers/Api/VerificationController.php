<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Verification\IdentityVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    public function __construct(private IdentityVerificationService $verifications) {}

    /**
     * Get verification status
     */
    public function status(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'is_verified' => $user->is_verified,
            'verified_at' => $user->verified_at,
            'documents' => $user->verification_documents,
        ]);
    }

    /**
     * Submit verification documents
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|in:id_card,passport,business_license,tax_document',
            'document' => 'required|file|max:5120', // 5MB max
            'selfie' => 'required|file|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        $this->verifications->submit(
            $user,
            $request->document_type,
            $request->file('document'),
            $request->file('selfie'),
        );

        return response()->json([
            'message' => 'Verification documents submitted successfully',
            'status' => 'pending'
        ]);
    }

    /**
     * Approve verification (admin only)
     */
    public function approve(Request $request, User $user)
    {
        $this->authorize('admin');

        $verification = $user->latestIdentityVerification;
        if (! $verification) {
            return response()->json(['message' => 'No pending verification found'], 404);
        }

        $this->verifications->approve($verification, Auth::user());

        return response()->json([
            'message' => 'Verification approved successfully'
        ]);
    }

    /**
     * Reject verification (admin only)
     */
    public function reject(Request $request, User $user)
    {
        $this->authorize('admin');

        $verification = $user->latestIdentityVerification;
        if (! $verification) {
            return response()->json(['message' => 'No pending verification found'], 404);
        }

        $this->verifications->reject($verification, Auth::user(), $request->reason);

        return response()->json([
            'message' => 'Verification rejected'
        ]);
    }
}
