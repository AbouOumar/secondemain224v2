<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Revoke the access token used for the current request, logging the
     * device out without affecting sessions on other devices.
     */
    public function store(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    /**
     * Revoke every access token belonging to the user, logging them out
     * everywhere at once.
     */
    public function destroyAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Déconnecté de tous les appareils.']);
    }
}
