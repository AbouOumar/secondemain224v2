<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Services\Article\BoostService;
use App\Services\Article\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function show($slug)
    {
        $article = Article::with(['images', 'user', 'category'])
            ->where('slug', $slug)
            ->firstOrFail();

        $article->increment('vue_count');

        $relatedArticles = Article::disponible()->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->where('is_published', true)
            ->latest()
            ->take(4)
            ->get();

        return view('articles.show', compact('article', 'relatedArticles'));
    }

    public function create()
    {
        $categories = Category::orderBy('libelle')->get();
        return view('articles.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:191',
            'description' => 'required|string',
            'prix' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'currency' => 'nullable|string|max:10',
            'localisation' => 'required|string|max:191',
            'etat' => 'nullable|string',
            'with_delivery' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $article = Article::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'titre' => $request->titre,
            'slug' => Str::slug($request->titre) . '-' . Str::random(6),
            'description' => $request->description,
            'prix' => $request->prix,
            'currency' => $request->currency ?? 'GNF',
            'localisation' => $request->localisation,
            'etat' => $request->etat ?? 'bon',
            'with_delivery' => $request->boolean('with_delivery', true),
            'delivery_prix' => $request->delivery_price,
            'is_published' => true,
            'statut' => 'en_vente',
            'stock' => $request->stock ?? 1,
        ]);

        if ($request->hasFile('images')) {
            $compression = app(ImageCompressionService::class);
            foreach ($request->file('images') as $ordre => $image) {
                $url = $compression->compressAndStore($image, 'articles');
                $article->images()->create(['url' => $url, 'ordre' => $ordre]);
            }
        }

        return redirect()->route('articles.show', $article->slug)
            ->with('success', 'Annonce publiée avec succès !');
    }

    public function edit(Article $article)
    {
        $this->authorize('update', $article);
        $categories = Category::orderBy('libelle')->get();
        return view('articles.edit', compact('article', 'categories'));
    }

    public function update(Request $request, Article $article)
    {
        $this->authorize('update', $article);

        $request->validate([
            'titre' => 'required|string|max:191',
            'description' => 'required|string',
            'prix' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'localisation' => 'required|string|max:191',
            'stock' => 'nullable|integer|min:0',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $data = $request->only([
            'titre', 'description', 'prix', 'currency', 'category_id',
            'localisation', 'etat', 'with_delivery', 'stock'
        ]);
        $data['delivery_prix'] = $request->delivery_price;
        $article->update($data);

        // Delete specific images if requested
        if ($request->has('delete_images')) {
            foreach ($request->input('delete_images', []) as $imageId) {
                $article->images()->where('id', $imageId)->delete();
            }
        }

        if ($request->hasFile('images')) {
            $compression = app(ImageCompressionService::class);
            // Get existing image count to maintain ordering
            $currentCount = $article->images()->count();
            foreach ($request->file('images') as $ordre => $image) {
                $url = $compression->compressAndStore($image, 'articles');
                $article->images()->create(['url' => $url, 'ordre' => $currentCount + $ordre]);
            }
        }

        return redirect()->route('articles.show', $article->slug)
            ->with('success', 'Annonce mise à jour.');
    }

    public function destroy(Article $article)
    {
        $this->authorize('delete', $article);
        $article->delete();
        return redirect()->route('profile.listings')
            ->with('success', 'Annonce supprimée.');
    }

    public function toggleStatus(Article $article)
    {
        $this->authorize('update', $article);
        
        $isVendu = $article->statut === 'vendu';
        
        $article->update([
            'statut' => $isVendu ? 'en_vente' : 'vendu',
            'is_published' => $isVendu ? 1 : 0,
            'date_fin' => $isVendu ? null : now()->format('d/m/Y')
        ]);
        
        return back()->with('success', 'Statut mis à jour.');
    }

    public function boost(Request $request, Article $article, BoostService $boostService)
    {
        if ($article->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'duree_heures' => 'nullable|integer|min:1|max:720',
        ]);

        $duree = $request->duree_heures ?? 24;
        $prix = $boostService->getPrix($duree);

        try {
            $boost = $boostService->boost($article, $duree);
            return back()->with('success', "Annonce boostée pour {$duree}h ({$prix} GNF débités de votre portefeuille).");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
