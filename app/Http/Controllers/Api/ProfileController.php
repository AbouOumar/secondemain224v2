<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\ArticleResource;
use App\Services\Account\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request) {
        $user = $request->user()->load('wallet');
        return new UserResource($user);
    }

    public function update(UpdateProfileRequest $request) {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        if (isset($data['new_password'])) {
            $data['password'] = bcrypt($data['new_password']);
            unset($data['new_password'], $data['current_password']);
        }

        $user->update($data);
        return new UserResource($user);
    }

    /**
     * Suppression du compte (exigée par le Play Store). Mot de passe demandé, sauf
     * pour un compte Google ; refusée tant qu'un argent ou une commande est en jeu.
     */
    public function destroy(Request $request, AccountDeletionService $deletion) {
        $user = $request->user();

        if ($deletion->needsPassword($user)) {
            $request->validate(['password' => 'required|string']);
            if (! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages(['password' => ['Mot de passe incorrect.']]);
            }
        }

        $blockers = $deletion->blockers($user);
        if ($blockers) {
            return response()->json(['message' => implode(' ', $blockers)], 409);
        }

        $deletion->delete($user);

        return response()->json(['message' => 'Votre compte a été supprimé.']);
    }

    public function avatar(Request $request) {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,jpg|max:5120'], [
            'avatar.image' => 'Le fichier sélectionné n\'est pas une image valide.',
            'avatar.mimes' => 'Formats autorisés : JPG, PNG.',
            'avatar.max' => 'L\'image dépasse la taille maximale autorisée (5 Mo). Veuillez choisir une image plus légère en volume.',
        ]);
        $user = $request->user();
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);
        return response()->json(['avatar' => asset('storage/'.$path)]);
    }

    /** Identifiants de toutes les annonces enregistrées, en un seul appel (cœurs des listes). */
    public function savedIds(Request $request) {
        return response()->json(['data' => $request->user()->savedArticles()->pluck('articles.id')->all()]);
    }

    public function saved(Request $request) {
        $articles = $request->user()->savedArticles()
            ->with(['images', 'category', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return ArticleResource::collection($articles);
    }
}
