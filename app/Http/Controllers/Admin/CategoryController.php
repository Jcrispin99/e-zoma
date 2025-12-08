<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;



class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_categories', Category::class);
        return view('admin.categories.index', [
            'categories' => Category::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_categories', Category::class);
        return view('admin.categories.form');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        Gate::authorize('update_categories', $category);
        return view('admin.categories.form', [
            'category' => $category,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        Gate::authorize('delete_categories', $category);

        if ($category->products()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => '¡Error!',
                'text' => 'No se puede eliminar la categoría porque tiene productos asociados',
            ]);
            return redirect()->route('admin.categories.index');
        }

        $category->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Categoria ' . $category->name . ' ha sido eliminada',
        ]);

        return redirect()->route('admin.categories.index');
    }

    public function indexWeb(Request $request)
    {
        Gate::authorize('read_categories', Category::class);
        $query = Category::with('parent');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        $categories = $query->paginate(80);

        return Inertia::render('inventory/categories/Index', compact('categories'));
    }

    public function createWeb()
    {
        Gate::authorize('create_categories', Category::class);
        $categories = Category::with('parent')->get();
        return Inertia::render('inventory/categories/CreateEdit', compact('categories'));
    }

    public function storeWeb(Request $request)
    {
        Gate::authorize('create_categories', Category::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Category::create($validated);

        return redirect()->route('inventory.categories.index')->with('success', 'Categoría creada correctamente');
    }

    public function editWeb(Category $category)
    {
        Gate::authorize('update_categories', $category);
        $categories = Category::where('id', '!=', $category->id)->with('parent')->get();

        return Inertia::render('inventory/categories/CreateEdit', compact('category', 'categories'));
    }

    public function updateWeb(Request $request, Category $category)
    {
        Gate::authorize('update_categories', $category);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($validated['name'] !== $category->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if ($validated['parent_id'] == $category->id) {
            return redirect()->back()->withErrors(['parent_id' => 'Una categoría no puede ser su propio padre.']);
        }

        $category->update($validated);

        return redirect()->back()->with('success', 'Categoría actualizada correctamente');
    }

    public function destroyWeb(Category $category)
    {
        Gate::authorize('delete_categories', $category);

        if ($category->products()->exists() || $category->children()->exists()) {
            return redirect()->back()->with('swalt', [
                'icon' => 'error',
                'title' => '¡Error!',
                'text' => 'No se puede eliminar la categoría porque tiene productos o subcategorías asociadas.'
            ]);
        }

        $category->delete();

        return redirect()->route('inventory.categories.index')->with('swalt', [
            'icon' => 'success',
            'title' => '¡Éxito!',
            'text' => 'Categoría eliminada correctamente'
        ]);
    }

    public function massDestroyWeb(Request $request)
    {
        Gate::authorize('delete_categories', Category::class);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:categories,id',
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            $category = Category::find($id);
            if (!$category->products()->exists() && !$category->children()->exists()) {
                $category->delete();
                $count++;
            }
        }

        if ($count < count($request->ids)) {
            $skipped = count($request->ids) - $count;
            session()->flash('swalt', [
                'icon' => 'warning',
                'title' => '¡Atención!',
                'text' => "Se eliminaron {$count} categorías y se saltaron {$skipped} categorías porque tienen productos o subcategorías asociadas.",
            ]);
        } else {
            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => $count === 1 ? 'Categoría eliminada correctamente' : 'Categorías eliminadas correctamente',
            ]);
        }

        return redirect()->route('inventory.categories.index');
    }
}
