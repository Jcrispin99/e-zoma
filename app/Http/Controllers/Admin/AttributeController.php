<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_attributes', Attribute::class);
        return view('admin.attributes.index', [
            'attributes' => Attribute::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_attributes', Attribute::class);
        return view('admin.attributes.form');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attribute $attribute)
    {
        Gate::authorize('update_attributes', $attribute);
        return view('admin.attributes.form', [
            'attribute' => $attribute
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attribute $attribute)
    {
        Gate::authorize('delete_attributes', $attribute);
        if ($attribute->attributeValues()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => '¡Error!',
                'text' => 'El atributo ' . $attribute->name . ' tiene valores asignados, por lo que no puede ser eliminado',
            ]);

            return redirect()->route('admin.attributes.index');
        }

        $attribute->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Atributo ' . $attribute->name . ' ha sido eliminado',
        ]);

        return redirect()->route('admin.attributes.index');
    }

    public function indexWeb(Request $request)
    {
        $query = Attribute::withCount('attributeValues');

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $attributes = $query->orderBy('created_at', 'desc')->paginate(80);

        return Inertia::render('inventory/attributes/Index', [
            'attributes' => $attributes
        ]);
    }

    public function createWeb()
    {
        return Inertia::render('inventory/attributes/CreateEdit');
    }

    public function storeWeb(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:attributes,name',
            'values' => 'array|nullable',
            'values.*.value' => 'required|string|distinct',
        ]);

        DB::beginTransaction();
        try {
            $attribute = Attribute::create(['name' => $validated['name']]);

            if (!empty($validated['values'])) {
                foreach ($validated['values'] as $val) {
                    $attribute->attributeValues()->create(['value' => $val['value']]);
                }
            }

            DB::commit();
            return redirect()->route('inventory.attributes.index')->with('success', 'Atributo creado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el atributo: ' . $e->getMessage());
        }
    }

    public function editWeb(Attribute $attribute)
    {
        $attribute->load('attributeValues');
        return Inertia::render('inventory/attributes/CreateEdit', [
            'attribute' => $attribute
        ]);
    }

    public function updateWeb(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:attributes,name,' . $attribute->id,
            'values' => 'array|nullable',
            'values.*.value' => 'required|string|distinct',
            'values.*.id' => 'nullable|integer|exists:attribute_values,id',
        ]);

        DB::beginTransaction();
        try {
            $attribute->update(['name' => $validated['name']]);

            $existingIds = collect($validated['values'] ?? [])->pluck('id')->filter()->toArray();

            $attribute->attributeValues()->whereNotIn('id', $existingIds)->delete();

            if (!empty($validated['values'])) {
                foreach ($validated['values'] as $val) {
                    if (isset($val['id'])) {
                        $attribute->attributeValues()->where('id', $val['id'])->update(['value' => $val['value']]);
                    } else {
                        $attribute->attributeValues()->create(['value' => $val['value']]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('inventory.attributes.edit', $attribute->id)->with('success', 'Atributo actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el atributo: ' . $e->getMessage());
        }
    }

    public function destroyWeb(Attribute $attribute)
    {
        try {
            $attribute->delete();
            return redirect()->route('inventory.attributes.index')->with('success', 'Atributo eliminado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el atributo');
        }
    }

    public function massDestroyWeb(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:attributes,id',
        ]);

        try {
            Attribute::whereIn('id', $request->ids)->delete();
            return redirect()->route('inventory.attributes.index')->with('success', 'Atributos eliminados correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar los atributos');
        }
    }
}
