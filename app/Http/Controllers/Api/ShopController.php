<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PartnerResource;
use App\Models\Article;
use App\Models\Order;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Boutique du revendeur pro (une par compte) et son tableau de bord.
 * Routes réservées au rôle revendeur_pro.
 */
class ShopController extends Controller
{
    public function show(Request $request) {
        $shop = $request->user()->partner;

        return response()->json(['data' => $shop ? new PartnerResource($shop) : null]);
    }

    public function store(Request $request) {
        $user = $request->user();
        if (Partner::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Vous avez déjà une boutique.'], 409);
        }

        $data = $this->validated($request);
        $data['user_id'] = $user->id;
        $data['slug'] = ! empty($data['slug'])
            ? Str::slug($data['slug'])
            : Str::slug($data['nom_magasin']) . '-' . $user->id;

        $shop = Partner::create($this->withUploads($request, $data));

        return response()->json(['data' => new PartnerResource($shop)], 201);
    }

    public function update(Request $request) {
        $shop = $request->user()->partner;
        if (! $shop) {
            return response()->json(['message' => "Vous n'avez pas encore de boutique."], 404);
        }

        $data = $this->validated($request, $shop);
        $data['slug'] = ! empty($data['slug'])
            ? Str::slug($data['slug'])
            : Str::slug($data['nom_magasin']) . '-' . $shop->user_id;
        if (! array_key_exists('horaire', $data)) {
            unset($data['horaire']); // horaires non envoyés : on garde les anciens
        }

        $shop->update($this->withUploads($request, $data));

        return response()->json(['data' => new PartnerResource($shop->fresh())]);
    }

    public function dashboard(Request $request, \App\Services\Seller\SellerDashboard $dashboard) {
        return response()->json(['data' => $dashboard->for($request->user())]);
    }

    private function validated(Request $request, ?Partner $shop = null): array {
        // Les horaires arrivent en JSON texte quand la requête est multipart (envoi du logo).
        if (is_string($request->input('horaire'))) {
            $request->merge(['horaire' => json_decode($request->input('horaire'), true)]);
        }

        return $request->validate([
            'nom_magasin' => 'required|string|max:191',
            'slug' => ['nullable', 'string', 'max:191', Rule::unique('partners', 'slug')->ignore($shop?->id)],
            'description' => 'nullable|string|max:2000',
            'adresse' => 'required|string|max:191',
            'telephone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'couverture' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'horaire' => 'nullable|array|max:7',
            'horaire.*.jour' => 'required_with:horaire|string|max:20',
            'horaire.*.ouverture' => 'nullable|string|max:10',
            'horaire.*.fermeture' => 'nullable|string|max:10',
        ]);
    }

    private function withUploads(Request $request, array $data): array {
        unset($data['logo'], $data['couverture']);
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('magasins/logos', 'public');
        }
        if ($request->hasFile('couverture')) {
            $data['couverture'] = $request->file('couverture')->store('magasins/couvertures', 'public');
        }

        return $data;
    }
}
