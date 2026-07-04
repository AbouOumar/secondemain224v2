<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MagasinController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $magasin = $user->partner;

        if (!$magasin) {
            return redirect()->route('magasin.setup');
        }

        $articles = Article::where('user_id', $user->id)
            ->with('images')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('magasin.dashboard', compact('magasin', 'articles'));
    }

    public function setup()
    {
        $user = Auth::user();
        if ($user->partner) {
            return redirect()->route('magasin.dashboard');
        }
        return view('magasin.setup');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->partner) {
            return redirect()->route('magasin.dashboard');
        }

        $data = $request->validate([
            'nom_magasin' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:partners,slug',
            'description' => 'nullable|string|max:2000',
            'adresse' => 'nullable|string|max:191',
            'telephone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'couverture' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'horaire' => 'nullable|string',
        ]);

        $data['user_id'] = $user->id;
        $data['slug'] = $data['slug'] ? Str::slug($data['slug']) : Str::slug($data['nom_magasin']) . '-' . $user->id;
        $data['horaire'] = $data['horaire'] ? json_decode($data['horaire'], true) : null;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('magasins/logos', 'public');
        }
        if ($request->hasFile('couverture')) {
            $data['couverture'] = $request->file('couverture')->store('magasins/couvertures', 'public');
        }

        $magasin = Partner::create($data);

        return redirect()->route('magasin.edit')->with('success', 'Votre magasin a été créé !');
    }

    public function edit()
    {
        $magasin = Auth::user()->partner;
        if (!$magasin) {
            return redirect()->route('magasin.setup');
        }
        return view('magasin.edit', compact('magasin'));
    }

    public function update(Request $request)
    {
        $magasin = Auth::user()->partner;
        if (!$magasin) {
            return redirect()->route('magasin.setup');
        }

        $data = $request->validate([
            'nom_magasin' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:partners,slug,' . $magasin->id,
            'description' => 'nullable|string|max:2000',
            'adresse' => 'nullable|string|max:191',
            'telephone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'couverture' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'horaire' => 'nullable|string',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['nom_magasin']) . '-' . $magasin->user_id;
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }
        $data['horaire'] = $request->filled('horaire') ? json_decode($data['horaire'], true) : $magasin->horaire;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('magasins/logos', 'public');
        }
        if ($request->hasFile('couverture')) {
            $data['couverture'] = $request->file('couverture')->store('magasins/couvertures', 'public');
        }

        $magasin->update($data);

        return redirect()->route('magasin.edit')->with('success', 'Magasin mis à jour !');
    }

    public function show($slug)
    {
        $magasin = Partner::where('slug', $slug)->firstOrFail();
        $articles = Article::disponible()->where('user_id', $magasin->user_id)
            ->where('is_published', true)
            ->with('images', 'category')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('magasin.show', compact('magasin', 'articles'));
    }
}
