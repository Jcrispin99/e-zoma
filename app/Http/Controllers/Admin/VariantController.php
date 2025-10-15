<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $variants = Variant::with('product')->get();

        return view('admin.variants.index', compact('variants'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Variant $variant)
    {
        $products = Product::all();
        return view('admin.variants.edit', compact('variant', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Variant $variant)
    {
        $request->validate([
            'barcode' => 'nullable',
            'price' => 'required',
        ]);

        // Generar código de barras si no se proporciona
        $data = $request->all();
        if (empty($data['barcode'])) {
            $data['barcode'] = Variant::generateUniqueBarcode();
        }

        $variant->update($data);
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien',
            'text' => 'Variant actualizado correctamente.',
        ]);
        return redirect()->route('admin.variants.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Variant $variant)
    {
        if ($variant->inventories()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se puede eliminar el variant porque está relacionado con un inventario.',
            ]);

            return redirect()->route('admin.variants.index');
        }

        if ($variant->purchasesOrders()->exists() || $variant->quotes()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se puede eliminar el variant porque está relacionado con una orden de compra o una cotización.',
            ]);

            return redirect()->route('admin.variants.index');
        }

        $variant->delete();
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien',
            'text' => 'Variant eliminado correctamente.',
        ]);

        return redirect()->route('admin.variants.index');
    }

    public function dropzone(Request $request, Variant $variant)
    {
        $image = $variant->images()->create([
            'path' => Storage::put('/images/variants', $request->file('file')),
            'size' => $request->file('file')->getSize(),
        ]);

        return response()->json([
            'id' => $image->id,
            'path' => $image->path,
        ]);
    }

    public function kardex(Variant $variant)
    {
        return view('admin.variants.kardex', compact('variant'));
    }
}
