<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => Category::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.categories.create', [
            'categories' => Category::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $data['slug'] = \Illuminate\Support\Str::slug($data['name']);

        if (!empty($data['parent_id'])) {
            $parent = Category::find($data['parent_id']);
            $parent_full_name = $parent->full_name ?: $parent->name;
            $data['full_name'] = $parent_full_name . ' / ' . $data['name'];
        } else {
            $data['full_name'] = $data['name'];
        }

        $category = Category::create($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Categoria ' . $category->name . ' ha sido creada',
        ]);

        return redirect()->route('admin.categories.edit', $category);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'categories' => Category::whereNull('parent_id')->get()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $data['slug'] = \Illuminate\Support\Str::slug($data['name']);

        if (!empty($data['parent_id'])) {
            $parent = Category::find($data['parent_id']);
            $parent_full_name = $parent->full_name ?: $parent->name;
            $data['full_name'] = $parent_full_name . ' / ' . $data['name'];
        } else {
            $data['full_name'] = $data['name'];
        }

        $category->update($data);

        // Si el nombre o el padre de la categoría cambiaron, actualizamos el full_name de los hijos.
        if ($category->wasChanged('name') || $category->wasChanged('parent_id')) {
            $this->updateChildrenFullName($category);
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Categoria ' . $category->name . ' ha sido actualizada',
        ]);

        return redirect()->route('admin.categories.edit', $category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
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

    /**
     * Actualiza recursivamente el campo full_name de las categorías hijas.
     *
     * @param Category $category
     * @return void
     */
    private function updateChildrenFullName(Category $category)
    {
        foreach ($category->children as $child) {
            $child->full_name = $category->full_name . ' / ' . $child->name;
            $child->save();

            $this->updateChildrenFullName($child);
        }
    }
}
