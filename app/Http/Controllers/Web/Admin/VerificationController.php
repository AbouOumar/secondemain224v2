<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Services\Verification\IdentityVerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(private IdentityVerificationService $verifications) {}

    public function index(Request $request)
    {
        $query = IdentityVerification::with('user');

        $status = $request->get('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $verifications = $query->orderBy('submitted_at', 'asc')->paginate(15)->withQueryString();

        return view('admin.verifications.index', compact('verifications', 'status'));
    }

    public function show(IdentityVerification $verification)
    {
        $verification->load(['user', 'reviewer']);

        return view('admin.verifications.show', compact('verification'));
    }

    public function approve(IdentityVerification $verification)
    {
        $this->verifications->approve($verification, auth()->user());

        return back()->with('success', "La vérification de {$verification->user->name} a été approuvée.");
    }

    public function reject(Request $request, IdentityVerification $verification)
    {
        $request->validate(['reason' => 'nullable|string|max:1000']);

        $this->verifications->reject($verification, auth()->user(), $request->reason);

        return back()->with('success', "La vérification de {$verification->user->name} a été rejetée.");
    }
}
