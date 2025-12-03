<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Models\Product;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class VariantController extends Controller
{
    public function indexWeb(Request $request)
    {
        Gate::authorize('read_products', Product::class);

        $query = Variant::with(['product.mainImage', 'mainImage', 'attributeValues']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $variants = $query->latest()->paginate(80);
        $variants->getCollection()->each->append('image');

        return Inertia::render('inventory/variants/Index', compact('variants'));
    }

    public function editWeb(Variant $variant)
    {
        Gate::authorize('update_variants', $variant);
        $variant->load(['product.images', 'images', 'attributeValues']);
        $variant->append('image');
        return Inertia::render('inventory/variants/Edit', compact('variant'));
    }

    public function updateWeb(Request $request, Variant $variant)
    {
        Gate::authorize('update_variants', $variant);

        $validated = $request->validate([
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:10240',
            'additionalImages' => 'nullable|array',
            'additionalImages.*' => 'image|max:10240',
            'existingImageIds' => 'nullable|array',
            'existingImageIds.*' => 'exists:images,id',
        ]);

        if (empty($validated['barcode'])) {
            $validated['barcode'] = Variant::generateUniqueBarcode();
        }

        $variant->update([
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
        ]);

        if ($variant->is_principal) {
            $variant->product()->update([
                'price' => $validated['price'],
            ]);
        }

        $mainImageId = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images/variants', 'public');
            $size = $request->file('image')->getSize();

            $mainImage = $variant->images()->oldest()->first();

            if ($mainImage) {
                Storage::disk('public')->delete($mainImage->path);
                $mainImage->update([
                    'path' => $path,
                    'size' => $size,
                ]);
                $mainImageId = $mainImage->id;
            } else {
                $newImage = $variant->images()->create([
                    'path' => $path,
                    'size' => $size,
                ]);
                $mainImageId = $newImage->id;
            }
        } else {
            $mainImage = $variant->images()->oldest()->first();
            if ($mainImage) {
                $mainImageId = $mainImage->id;
            }
        }

        if ($request->has('existingImageIds')) {
            $existingIds = $request->input('existingImageIds');
            $imagesToDelete = $variant->images()
                ->whereNotIn('id', $existingIds)
                ->when($mainImageId, function ($query) use ($mainImageId) {
                    return $query->where('id', '!=', $mainImageId);
                })
                ->get();

            /** @var Image $image */
            foreach ($imagesToDelete as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        } else {
            if ($request->exists('existingImageIds')) {
                $imagesToDelete = $variant->images()
                    ->when($mainImageId, function ($query) use ($mainImageId) {
                        return $query->where('id', '!=', $mainImageId);
                    })
                    ->get();
                /** @var Image $image */
                foreach ($imagesToDelete as $image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                }
            }
        }

        if ($request->hasFile('additionalImages')) {
            foreach ($request->file('additionalImages') as $file) {
                $path = $file->store('images/variants', 'public');
                $variant->images()->create([
                    'path' => $path,
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('inventory.variants.edit', $variant)->with('success', 'Variante actualizada correctamente');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_variants', Variant::class);
        $variants = Variant::with('product')->get();

        return view('admin.variants.index', compact('variants'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Variant $variant)
    {
        Gate::authorize('update_variants', $variant);
        return view('admin.variants.form', compact('variant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Variant $variant)
    {
        Gate::authorize('update_variants', $variant);
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
        Gate::authorize('delete_variants', $variant);
        if ($variant->inventories()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se puede eliminar el variant porque está relacionado con un inventario.',
            ]);

            return redirect()->route('admin.variants.index');
        }

        // Bloquear si la variante está usada en cualquier documento vía pivot variantables
        if ($variant->variantables()->exists() || $variant->posOrderLines()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se puede eliminar la variante porque está relacionada con documentos o transacciones.',
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
        Gate::authorize('upload_variant_images', $variant);
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
        Gate::authorize('read_variants_kardex', $variant);
        return view('admin.variants.kardex', compact('variant'));
    }

    public function massDestroy(Request $request)
    {
        Gate::authorize('delete_variants');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:variants,id',
        ]);

        $ids = $request->input('ids');
        $count = 0;

        foreach ($ids as $id) {
            $variant = Variant::find($id);

            if ($variant->inventories()->exists() || $variant->variantables()->exists() || $variant->posOrderLines()->exists()) {
                continue;
            }

            foreach ($variant->images as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }

            $variant->delete();
            $count++;
        }

        if ($count < count($ids)) {
            return redirect()->back()->with('error', 'Algunas variantes no pudieron ser eliminadas porque tienen registros relacionados.');
        }

        return redirect()->back()->with('success', "$count variantes eliminadas correctamente.");
    }
}
