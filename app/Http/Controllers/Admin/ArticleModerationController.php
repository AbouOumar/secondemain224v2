<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\Request;

class ArticleModerationController extends Controller
{
    public function index(Request $request) {
        $articles = Article::where('is_verified', false)
            ->where('is_published', true)
            ->with(['user', 'category', 'images'])
            ->orderBy('created_at', 'asc')
            ->paginate(15);
        return ArticleResource::collection($articles);
    }

    public function verify(Article $article) {
        $article->update(['is_verified' => true]);
        return new ArticleResource($article);
    }

    public function reject(Request $request, Article $article) {
        $request->validate(['raison' => 'nullable|string|max:1000']);
        $article->update([
            'is_published' => false,
            'rejection_raison' => $request->raison,
        ]);
        return new ArticleResource($article);
    }
}
