<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Article;
use App\Models\Partner;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')->orderBy('libelle')->get();
        // Récupérer les articles des partenaires vérifiés
        $partnerArticles = Article::disponible()->whereHas('user.partner', function($query) {
            $query->where('is_verified', true);
        })
        ->where('is_published', 1)
        ->where('is_verified', 1)
        ->with(['images', 'user', 'category'])
        ->orderBy('is_boosted', 'desc')
        ->orderBy('created_at', 'desc')
        ->take(6)
        ->get();
        
        // Récupérer quelques articles en vedette pour l'affichage initial
        $featuredArticles = Article::disponible()->with(['images', 'user', 'category'])
            ->where('is_published', 1)
            ->orderBy('is_boosted', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();
        
        return view('home', compact('categories', 'partnerArticles', 'featuredArticles'));
    }

    public function search(Request $request)
    {
        $categories = Category::with('children')->orderBy('libelle')->get();

        $partnerArticles = Article::disponible()->whereHas('user.partner', function($query) {
            $query->where('is_verified', true);
        })
        ->where('is_published', 1)
        ->where('is_verified', 1)
        ->with(['images', 'user', 'category'])
        ->orderBy('is_boosted', 'desc')
        ->orderBy('created_at', 'desc')
        ->take(6)
        ->get();

        $query = Article::disponible()->with(['images', 'user', 'category'])
            ->where('is_published', 1);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('min_price')) {
            $query->where('prix', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('prix', '<=', $request->max_price);
        }

        if ($request->filled('localisation')) {
            $query->where('localisation', 'like', "%{$request->localisation}%");
        }

        $featuredArticles = $query->orderBy('is_boosted', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $isAjaxRequest = $request->ajax()
            || $request->boolean('ajax')
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($isAjaxRequest) {
            return response()->json([
                'html' => view('partials.articles-grid', ['articles' => $featuredArticles])->render(),
                'hasMore' => $featuredArticles->hasMorePages()
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
              ->header('Pragma', 'no-cache');
        }

        return view('home', compact('categories', 'partnerArticles', 'featuredArticles'));
    }
}
