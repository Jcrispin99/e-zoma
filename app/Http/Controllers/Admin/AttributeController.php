<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.attributes.index', [
            'attributes' => Attribute::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.attributes.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255|unique:attributes,name',
            'values' => 'nullable|array',
            'values.*' => 'string|max:255', // Valida cada valor en el array
        ]);

        DB::transaction(function () use ($data) {
            $attribute = Attribute::create(['name' => $data['name']]);

            if (isset($data['values'])) {
                foreach ($data['values'] as $value) {
                    $attribute->attributeValues()->create(['value' => $value]);
                }
            }
        });


        session()->flash('swalt', [
            'icon'  => 'success',
            'title' => '¡Bien hecho!',
            'text'  => 'Atributo y sus valores han sido creados.',
        ]);

        return redirect()->route('admin.attributes.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attribute $attribute)
    {
        return view('admin.attributes.form', [
            'attribute' => $attribute
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attribute $attribute)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:attributes,name,' . $attribute->id,
            'values' => 'nullable|array',
            'values.*.id' => 'nullable|integer|exists:attribute_values,id',
            'values.*.value' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($data, $attribute) {
            $attribute->update(['name' => $data['name']]);

            $existingValueIds = [];

            if (isset($data['values'])) {
                foreach ($data['values'] as $valueData) {
                    if (isset($valueData['id'])) {
                        // Actualizar valor existente
                        $value = $attribute->attributeValues()->find($valueData['id']);
                        if ($value) {
                            $value->update(['value' => $valueData['value']]);
                            $existingValueIds[] = $value->id;
                        }
                    } else {
                        // Crear nuevo valor
                        $newValue = $attribute->attributeValues()->create(['value' => $valueData['value']]);
                        $existingValueIds[] = $newValue->id;
                    }
                }
            }

            // Eliminar valores que ya no están en la lista
            $attribute->attributeValues()->whereNotIn('id', $existingValueIds)->delete();
        });

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Atributo ' . $attribute->name . ' ha sido actualizado',
        ]);

        return redirect()->route('admin.attributes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attribute $attribute)
    {
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
}
