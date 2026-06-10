<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Http\Resources\PartnerResource;
use App\Http\Resources\ArticleResource;

class PartnerController extends Controller
{
    public function index() {
        $partners = Partner::where('is_verified', true)
            ->with('user')
            ->orderBy('nom_magasin')
            ->paginate(12);
        return PartnerResource::collection($partners);
    }

    public function show($slug) {
        $partner = Partner::where('slug', $slug)
            ->with('user.articles.images')
            ->firstOrFail();
        $articles = $partner->user->articles()
            ->where('is_published', true)
            ->with(['images', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return response()->json([
            'partner' => new PartnerResource($partner),
            'articles' => ArticleResource::collection($articles),
        ]);
    }
}
