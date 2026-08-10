<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleModerationController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['user', 'category', 'images']);

        if ($request->get('filtre') === 'rejetes') {
            $query->where('is_published', false)->whereNotNull('rejection_raison');
        } else {
            $query->where('is_verified', false)->where('is_published', true);
        }

        $articles = $query->orderBy('created_at', 'asc')->paginate(15)->withQueryString();

        return view('admin.articles.index', compact('articles'));
    }

    public function verify(Article $article)
    {
        $article->update(['is_verified' => true, 'rejection_raison' => null]);

        return back()->with('success', "L'annonce « {$article->titre} » a été validée.");
    }

    public function reject(Request $request, Article $article)
    {
        $request->validate(['raison' => 'nullable|string|max:1000']);

        $article->update([
            'is_published' => false,
            'rejection_raison' => $request->raison,
        ]);

        return back()->with('success', "L'annonce « {$article->titre} » a été rejetée.");
    }
}
