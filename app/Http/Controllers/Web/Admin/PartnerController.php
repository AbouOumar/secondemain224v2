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

    /** Taux de commission du magasin (en %, dans les bornes de config/marketplace.php ; vide = taux par défaut). */
    public function commission(Request $request, Partner $partner)
    {
        $min = config('marketplace.partner_commission_min') * 100;
        $max = config('marketplace.partner_commission_max') * 100;
        $data = $request->validate(
            ['rate' => "nullable|numeric|min:{$min}|max:{$max}"],
            ['rate.min' => "Le taux doit être compris entre {$min} et {$max} %.", 'rate.max' => "Le taux doit être compris entre {$min} et {$max} %."],
        );

        $rate = $data['rate'] === null ? null : round($data['rate'] / 100, 4);
        $partner->update(['commission_rate' => $rate]);

        $shown = ($rate ?? config('marketplace.partner_commission_rate')) * 100;

        return back()->with('success', "Commission du magasin « {$partner->nom_magasin} » : {$shown} %" . ($rate === null ? ' (taux par défaut)' : '') . '. S\'applique aux prochaines commandes payées.');
    }

    public function destroy(Partner $partner)
    {
        $nom = $partner->nom_magasin;
        $partner->delete();

        return back()->with('success', "Le magasin « {$nom} » a été supprimé.");
    }
}
