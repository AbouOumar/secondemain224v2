<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = Partner::with('user');

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        $partners = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.partners.index', compact('partners'));
    }

    public function verify(Partner $partner)
    {
        $partner->update(['is_verified' => true]);

        return back()->with('success', "Le magasin « {$partner->nom_magasin} » a été vérifié.");
    }

    public function unverify(Partner $partner)
    {
        $partner->update(['is_verified' => false]);

        return back()->with('success', "La vérification du magasin « {$partner->nom_magasin} » a été retirée.");
    }

    public function destroy(Partner $partner)
    {
        $nom = $partner->nom_magasin;
        $partner->delete();

        return back()->with('success', "Le magasin « {$nom} » a été supprimé.");
    }
}
