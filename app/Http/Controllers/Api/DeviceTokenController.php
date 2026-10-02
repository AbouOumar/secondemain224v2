<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Enregistre le téléphone de l'utilisateur connecté. Un même jeton
     * (téléphone partagé) est rattaché au dernier utilisateur connecté.
     */
    public function store(Request $request) {
        $data = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'required|string|in:android,ios,web',
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                // Session de connexion : le téléphone est oublié quand elle est supprimée.
                'personal_access_token_id' => $request->user()->currentAccessToken()?->getKey(),
                'platform' => $data['platform'],
            ],
        );

        return response()->noContent();
    }

    /**
     * À appeler avant la déconnexion : ce téléphone ne reçoit plus rien.
     */
    public function destroy(Request $request) {
        $data = $request->validate(['token' => 'required|string|max:512']);

        $request->user()->deviceTokens()->where('token', $data['token'])->delete();

        return response()->noContent();
    }
}
