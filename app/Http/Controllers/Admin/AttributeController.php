<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Support\Facades\Gate;

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
}
