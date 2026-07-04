<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Http\Resources\ArticleResource;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\Article\ImageCompressionService;

class ArticleController extends Controller
{
    public function __construct(private ImageCompressionService $imageCompression) {}

    public function index(Request $request) {
        $query = Article::disponible()->where('is_published', true)
            ->with(['images', 'user', 'category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('prix_min')) {
            $query->where('prix', '>=', $request->prix_min);
        }
        if ($request->filled('prix_max')) {
            $query->where('prix', '<=', $request->prix_max);
        }
        if ($request->filled('localisation')) {
            $query->where('localisation', 'like', "%{$request->localisation}%");
        }

        $articles = $query->orderBy('is_boosted', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return ArticleResource::collection($articles);
    }

    public function show(string $slug) {
        $article = Article::where('slug', $slug)->orWhere('id', $slug)->firstOrFail();
        $article->increment('vue_count');
        $article->load(['images', 'user', 'category']);
        return new ArticleResource($article);
    }

    public function store(StoreArticleRequest $request) {
        $article = Article::create([
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'titre' => $request->titre,
            'slug' => Str::slug($request->titre).'-'.Str::random(6),
            'description' => $request->description,
            'prix' => $request->prix,
            'currency' => $request->currency ?? 'GNF',
            'stock' => $request->stock ?? 1,
            'etat' => $request->etat,
            'annee' => $request->annee,
            'localisation' => $request->localisation,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'with_delivery' => $request->with_delivery ?? false,
            'delivery_prix' => $request->delivery_prix,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $this->imageCompression->compressAndStore($image);
                $article->images()->create([
                    'url' => $path,
                    'ordre' => $index,
                ]);
            }
        }

        $article->load(['images', 'user', 'category']);
        return new ArticleResource($article, 201);
    }

    public function update(UpdateArticleRequest $request, Article $article) {
        $this->authorize('update', $article);

        $data = $request->validated();
        if ($request->filled('titre')) {
            $data['slug'] = Str::slug($request->titre).'-'.Str::random(6);
        }
        $article->update($data);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $this->imageCompression->compressAndStore($image);
                $article->images()->create([
                    'url' => $path,
                    'ordre' => $index,
                ]);
            }
        }

        $article->load(['images', 'user', 'category']);
        return new ArticleResource($article);
    }

    public function destroy(Article $article) {
        $this->authorize('delete', $article);
        $article->delete();
        return response()->json(null, 204);
    }

    public function myArticles(Request $request) {
        $articles = Article::where('user_id', $request->user()->id)
            ->with(['images', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return ArticleResource::collection($articles);
    }

    public function toggleSave(Request $request, Article $article) {
        $user = $request->user();
        if ($user->savedArticles()->where('article_id', $article->id)->exists()) {
            $user->savedArticles()->detach($article->id);
            $saved = false;
        } else {
            $user->savedArticles()->attach($article->id);
            $saved = true;
        }
        return response()->json(['saved' => $saved]);
    }

    public function checkSave(Request $request, Article $article) {
        $saved = $request->user()->savedArticles()->where('article_id', $article->id)->exists();
        return response()->json(['saved' => $saved]);
    }

    public function stats(Request $request) {
        $userId = $request->user()->id;
        
        // Statistiques de base
        $totalArticles = Article::where('user_id', $userId)->count();
        $articlesEnVente = Article::where('user_id', $userId)->where('is_published', true)->count();
        $articlesVendus = Article::where('user_id', $userId)->whereHas('orders', function($q) {
            $q->where('status', 'termine');
        })->count();
        
        // Revenus générés
        $revenus = Order::where('seller_id', $userId)
            ->where('status', 'termine')
            ->sum('total');
            
        // Articles par catégorie
        $articlesParCategorie = Article::where('user_id', $userId)
            ->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id')
            ->toArray();
            
        // Articles avec livraison vs sans livraison
        $articlesAvecLivraison = Article::where('user_id', $userId)
            ->where('with_delivery', true)
            ->count();
            
        $articlesSansLivraison = Article::where('user_id', $userId)
            ->where('with_delivery', false)
            ->count();
        
        // Articles en stock (pour les revendeurs pros)
        $articlesEnStock = 0;
        $articlesRuptureStock = 0;
        if ($request->user()->role === 'revendeur_pro') {
            $articlesEnStock = Article::where('user_id', $userId)
                ->where('stock', '>', 0)
                ->count();
                
            $articlesRuptureStock = Article::where('user_id', $userId)
                ->where('stock', 0)
                ->count();
        }
        
        return response()->json([
            'total' => $totalArticles,
            'en_vente' => $articlesEnVente,
            'vendus' => $articlesVendus,
            'revenus' => round($revenus, 0),
            'taux_conversion' => $totalArticles > 0 ? round(($articlesVendus / $totalArticles) * 100, 1) : 0,
            'articles_avec_livraison' => $articlesAvecLivraison,
            'articles_sans_livraison' => $articlesSansLivraison,
            'articles_en_stock' => $articlesEnStock,
            'articles_rupture_stock' => $articlesRuptureStock,
            'par_categorie' => $articlesParCategorie,
        ]);
    }
}
