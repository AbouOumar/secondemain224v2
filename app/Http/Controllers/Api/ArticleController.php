<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\Order;
use App\Http\Resources\ArticleResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Services\Article\ImageCompressionService;

class ArticleController extends Controller
{
    private const MAX_IMAGES = Article::MAX_IMAGES;

    public function __construct(private ImageCompressionService $imageCompression) {}

    public function index(Request $request) {
        $query = Article::disponible()->where('is_published', true)
            ->with(['images', 'user.partner', 'category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
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

        // Annonces des boutiques vérifiées (section « Espaces partenaires » de l'accueil).
        if ($request->boolean('partner')) {
            $query->where('is_verified', true)
                ->whereHas('user.partner', fn ($q) => $q->where('is_verified', true));
        }

        // Mêmes valeurs que le tri du site ; toute autre valeur garde l'ordre par défaut.
        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('prix', 'asc'),
            'price_desc' => $query->orderBy('prix', 'desc'),
            default => $query->orderBy('is_boosted', 'desc')->orderBy('created_at', 'desc'),
        };

        return ArticleResource::collection($query->paginate(12));
    }

    public function show(string $slug) {
        $article = Article::where('slug', $slug)->orWhere('id', $slug)->firstOrFail();

        // Une annonce retirée, en attente ou d'un compte supprimé n'est visible que de son auteur (ou d'un admin),
        // et l'auteur qui consulte sa propre annonce n'ajoute pas de vue.
        $viewer = auth('sanctum')->user();
        $isOwner = $viewer && ($viewer->id === $article->user_id || $viewer->role?->value === 'admin');
        abort_unless($article->is_published || $isOwner, 404);
        if (! $isOwner) {
            $article->increment('vue_count');
        }

        $article->load(['images', 'user.partner', 'category']);
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
            'colors' => $this->sanitizeColors($request->colors),
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

        // Les nouvelles photos s'ajoutent aux anciennes : deux au total, comme à la création.
        $added = count($request->file('images', []));
        if ($added > 0 && $article->images()->count() + $added > self::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'images' => ['Une annonce ne peut avoir que ' . self::MAX_IMAGES . ' photos : retirez-en une avant d\'en ajouter.'],
            ]);
        }

        $data = $request->validated();
        unset($data['colors']);
        if ($request->filled('titre')) {
            $data['slug'] = Str::slug($request->titre).'-'.Str::random(6);
        }
        if ($request->has('colors')) {
            $data['colors'] = $this->sanitizeColors($request->colors);
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

    public function destroyImage(Article $article, ArticleImage $image) {
        $this->authorize('update', $article);
        abort_unless($image->article_id === $article->id, 404);

        // Chemin brut (l'accesseur `url` renvoie une adresse complète) ; la vignette est supprimée par le modèle.
        $path = $image->getAttributes()['url'] ?? null;
        $image->delete();
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(null, 204);
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
            $q->where('status', 'livre');
        })->count();
        
        // Revenus générés
        $revenus = Order::where('seller_id', $userId)
            ->where('status', 'livre')
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
        if ($request->user()->role?->value === 'revendeur_pro') {
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

    private function sanitizeColors($colors): ?array
    {
        if (!is_array($colors) || empty($colors)) {
            return null;
        }

        $valid = \App\Enums\ProductColor::values();

        $normalized = array_map('strtolower', $colors);
        $filtered = array_values(array_filter($normalized, fn ($color) => in_array($color, $valid, true)));

        return $filtered === [] ? null : array_slice(array_unique($filtered), 0, 6);
    }
}
