<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ArticleResource;

class CategoryController extends Controller
{
    public function index() {
        $categories = Category::with('children')->get();
        return CategoryResource::collection($categories);
    }

    public function show(Category $category) {
        $articles = $category->articles()
            ->where('is_published', true)
            ->with(['images', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return response()->json([
            'category' => new CategoryResource($category),
            'articles' => ArticleResource::collection($articles),
        ]);
    }
}
