<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->orderBy('libelle')
            ->get();

        $allCategories = Category::orderBy('libelle')->get();

        return view('admin.categories.index', compact('categories', 'allCategories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'libelle' => 'required|string|max:191',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        $data['slug'] = Str::slug($data['libelle']);

        Category::create($data);

        return back()->with('success', 'Catégorie créée.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'libelle' => 'required|string|max:191',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        $data['slug'] = Str::slug($data['libelle']);

        $category->update($data);

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists() || $category->articles()->exists()) {
            return back()->with('error', 'Impossible de supprimer une catégorie avec des enfants ou des annonces.');
        }

        $category->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }
}
