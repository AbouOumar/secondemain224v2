<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index() {
        $categories = Category::with('children')->orderBy('libelle')->get();
        return CategoryResource::collection($categories);
    }

    public function show(Category $category) {
        $category->load('children', 'parent');
        return new CategoryResource($category);
    }

    public function store(Request $request) {
        $request->validate([
            'libelle' => 'required|string|max:191',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);
        $category = Category::create([
            'libelle' => $request->libelle,
            'slug' => Str::slug($request->libelle),
            'parent_id' => $request->parent_id,
            'icon' => $request->icon,
            'description' => $request->description,
        ]);
        return new CategoryResource($category, 201);
    }

    public function update(Request $request, Category $category) {
        $request->validate([
            'libelle' => 'nullable|string|max:191',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);
        $data = $request->only(['libelle', 'parent_id', 'icon', 'description']);
        if ($request->filled('libelle')) {
            $data['slug'] = Str::slug($request->libelle);
        }
        $category->update($data);
        return new CategoryResource($category);
    }

    public function destroy(Category $category) {
        if ($category->children()->exists() || $category->articles()->exists()) {
            return response()->json(['message' => 'Impossible de supprimer une catégorie avec des enfants ou des articles.'], 409);
        }
        $category->delete();
        return response()->json(null, 204);
    }
}
