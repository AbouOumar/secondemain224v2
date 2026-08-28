<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Verification\IdentityVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function __construct(private IdentityVerificationService $verifications) {}

    public function show()
    {
        $user = Auth::user();
        $latest = $user->latestIdentityVerification;

        return view('profile.verification', compact('user', 'latest'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|in:id_card,passport,business_license,tax_document',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'selfie' => 'required|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = Auth::user();

        if ($user->is_verified) {
            return back()->with('info', 'Votre compte est déjà vérifié.');
        }

        $this->verifications->submit(
            $user,
            $request->document_type,
            $request->file('document'),
            $request->file('selfie'),
        );

        return back()->with('success', 'Documents soumis avec succès. Votre demande sera examinée sous peu.');
    }
}
