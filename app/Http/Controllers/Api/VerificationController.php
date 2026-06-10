<?php
 
namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
 
class VerificationController extends Controller
{
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
            'selfie' => 'required|file|max:2048', // 2MB max
        ]);
 
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
 
        $user = Auth::user();
 
        // Store files
        $documentPath = $request->file('document')->store('verification_documents', 'public');
        $selfiePath = $request->file('selfie')->store('verification_selfies', 'public');
 
        // Update user with verification data
        $user->update([
            'verification_documents' => [
                'type' => $request->document_type,
                'document_path' => $documentPath,
                'selfie_path' => $selfiePath,
                'submitted_at' => now()->toIso8601String(),
                'status' => 'pending',
            ],
        ]);
 
        // In a real app, you would queue this for admin review
        // For now, we'll auto-approve for demonstration
        // $this->approveVerification($user);
 
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
 
        $user->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
 
        // Update verification documents status
        $docs = $user->verification_documents;
        $docs['status'] = 'approved';
        $docs['approved_at'] = now()->toIso8601String();
 
        $user->update([
            'verification_documents' => $docs,
        ]);
 
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
 
        // Update verification documents status
        $docs = $user->verification_documents;
        $docs['status'] = 'rejected';
        $docs['rejected_at'] = now()->toIso8601String();
        $docs['rejection_reason'] = $request->reason ?? 'No reason provided';
 
        $user->update([
            'verification_documents' => $docs,
        ]);
 
        return response()->json([
            'message' => 'Verification rejected'
        ]);
    }
}
