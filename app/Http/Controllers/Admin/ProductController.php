<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Image;
use App\Models\QrStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductController extends Controller
{

    public function index()
    {
        Gate::authorize('read_products', Product::class);
        $products = Product::query()->paginate(10);
        return view('admin.products.index', compact('products'));
    }

    public function inventoryDashboard()
    {
        return Inertia::render('inventory/Index');
    }

    public function indexWeb(Request $request)
    {
        Gate::authorize('read_products', Product::class);

        $query = Product::with(['variants.mainImage', 'mainImage', 'category']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($variantQuery) use ($search) {
                        $variantQuery->where('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('variants', function ($variantQuery) use ($search) {
                        $variantQuery->where('barcode', 'like', "%{$search}%");
                    })
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $products = $query->latest()->paginate(80);
        $products->getCollection()->each->append(['image', 'sku', 'barcode']);

        return Inertia::render('inventory/products/Index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_products', Product::class);
        $categories = Category::all();
        // Unificamos la vista a 'admin.products.form' para crear/editar
        return view('admin.products.form', compact('categories'));
    }

    public function createWeb()
    {
        Gate::authorize('create_products', Product::class);
        $categories = Category::with('parent')->get();
        $attributes = Attribute::with('attributeValues')->get();
        return Inertia::render('inventory/products/CreateEdit', compact('categories', 'attributes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create_products', Product::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
        ]);

        $product = Product::create($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Producto ' . $data['name'] . ' ha sido creado',
        ]);

        return redirect()->route('admin.products.edit', $product);
    }

    public function storeWeb(Request $request)
    {
        Gate::authorize('create_products', Product::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:10240',
            'attributeLines' => 'nullable|array',
            'generatedVariants' => 'nullable|array',
            'additionalImages.*' => 'nullable|image|max:10240',
        ], [], [
            'category_id' => 'categoría',
        ]);

        DB::transaction(function () use ($request, $data) {
            $product = Product::create($data);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('images/products', 'public');
                $product->images()->create([
                    'path' => $path,
                ]);
            }

            if ($request->hasFile('additionalImages')) {
                foreach ($request->file('additionalImages') as $imageFile) {
                    $path = $imageFile->store('images/products', 'public');
                    $product->images()->create([
                        'path' => $path,
                        'size' => $imageFile->getSize(),
                    ]);
                }
            }

            if (!empty($data['attributeLines'])) {
                foreach ($data['attributeLines'] as $line) {
                    if (empty($line['attribute_id']) || empty($line['values']))
                        continue;

                    $attribute = Attribute::find($line['attribute_id']);
                    if (!$attribute)
                        continue;

                    foreach ($line['values'] as $valueName) {
                        AttributeValue::firstOrCreate([
                            'attribute_id' => $attribute->id,
                            'value' => $valueName
                        ]);
                    }
                }
            }

            if (!empty($data['generatedVariants'])) {
                foreach ($data['generatedVariants'] as $index => $variantData) {
                    $variant = $product->variants()->create([
                        'sku' => $variantData['sku'] ?? null,
                        'barcode' => $variantData['barcode'] ?? null,
                        'price' => $variantData['price'] ?? $product->price,
                        'stock' => $variantData['stock'] ?? 0,
                        'is_principal' => $index === 0,
                    ]);

                    if (!empty($variantData['attributes'])) {
                        foreach ($variantData['attributes'] as $attributeId => $valueName) {
                            $attributeValue = AttributeValue::where('attribute_id', $attributeId)
                                ->where('value', $valueName)
                                ->first();

                            if ($attributeValue) {
                                $variant->attributeValues()->attach($attributeValue->id);
                            }
                        }
                    }
                }
            } else {
                $product->variants()->create([
                    'sku' => $data['sku'] ?? null,
                    'barcode' => $data['barcode'] ?? null,
                    'price' => $data['price'],
                    'stock' => 0,
                    'is_principal' => true,
                ]);
            }
        });

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Producto ' . $data['name'] . ' ha sido creado',
        ]);

        return redirect()->route('inventory.products.index');
    }

    public function editWeb(Product $product)
    {
        Gate::authorize('update_products', $product);

        $product->load([
            'variants' => function ($query) {
                $query->orderBy('is_principal', 'desc');
            },
            'variants.attributeValues',
            'images'
        ]);

        if ($product->variants->isNotEmpty()) {
            $hasPrincipal = $product->variants->where('is_principal', true)->isNotEmpty();
            if (!$hasPrincipal) {
                $firstVariant = $product->variants->first();
                $firstVariant->update(['is_principal' => true]);
                $product->load([
                    'variants' => function ($query) {
                        $query->orderBy('is_principal', 'desc');
                    },
                    'variants.attributeValues'
                ]);
            }
        }

        $categories = Category::with('parent')->get();
        $attributes = Attribute::with('attributeValues')->get();
        $product->append(['image', 'sku', 'barcode']);
        return Inertia::render('inventory/products/CreateEdit', compact('product', 'categories', 'attributes'));
    }

    public function updateWeb(Request $request, Product $product)
    {
        Gate::authorize('update_products', $product);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:10240',
            'attributeLines' => 'nullable|array',
            'generatedVariants' => 'nullable|array',
            'additionalImages.*' => 'nullable|image|max:10240',
            'existingImageIds' => 'nullable|array',
        ], [], [
            'category_id' => 'categoría',
        ]);

        DB::transaction(function () use ($request, $product, $data) {
            $product->update($data);

            $mainImageId = null;

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('images/products', 'public');
                $mainImage = $product->images()->first();

                if ($mainImage) {
                    Storage::disk('public')->delete($mainImage->path);
                    $mainImage->update([
                        'path' => $path,
                    ]);
                    $mainImageId = $mainImage->id;
                } else {
                    $newImage = $product->images()->create([
                        'path' => $path,
                    ]);
                    $mainImageId = $newImage->id;
                }
            } else {
                $mainImage = $product->images()->first();
                if ($mainImage) {
                    $mainImageId = $mainImage->id;
                }
            }

            if ($request->has('existingImageIds')) {
                $existingIds = $request->input('existingImageIds', []);
                $product->images()
                    ->whereNotIn('id', $existingIds)
                    ->when($mainImageId, function ($query) use ($mainImageId) {
                        return $query->where('id', '!=', $mainImageId);
                    })
                    ->each(function ($image) {
                        /** @var Image $image */
                        Storage::disk('public')->delete($image->path);
                        $image->delete();
                    });
            }

            if ($request->hasFile('additionalImages')) {
                foreach ($request->file('additionalImages') as $imageFile) {
                    $path = $imageFile->store('images/products', 'public');
                    $product->images()->create([
                        'path' => $path,
                        'size' => $imageFile->getSize(),
                    ]);
                }
            }

            if (!empty($data['attributeLines'])) {
                foreach ($data['attributeLines'] as $line) {
                    if (empty($line['attribute_id']) || empty($line['values']))
                        continue;

                    $attribute = Attribute::find($line['attribute_id']);
                    if (!$attribute)
                        continue;

                    foreach ($line['values'] as $valueName) {
                        AttributeValue::firstOrCreate([
                            'attribute_id' => $attribute->id,
                            'value' => $valueName
                        ]);
                    }
                }
            }

            if (!empty($data['generatedVariants'])) {
                $existingVariants = $product->variants()->with('attributeValues')->get();
                $processedVariantIds = [];
                $isFirstVariant = true;

                foreach ($data['generatedVariants'] as $variantData) {
                    $attributeKey = '';
                    if (!empty($variantData['attributes'])) {
                        ksort($variantData['attributes']);
                        $attributeKey = json_encode($variantData['attributes']);
                    }

                    $existingVariant = null;
                    foreach ($existingVariants as $ev) {
                        $evAttributes = [];
                        if ($ev->attributeValues) {
                            foreach ($ev->attributeValues as $av) {
                                $evAttributes[$av->attribute_id] = $av->value;
                            }
                            ksort($evAttributes);
                        }
                        $evKey = json_encode($evAttributes);

                        if ($evKey === $attributeKey) {
                            $existingVariant = $ev;
                            break;
                        }
                    }

                    if ($existingVariant) {
                        $existingVariant->update([
                            'sku' => $variantData['sku'] ?? null,
                            'barcode' => $variantData['barcode'] ?? null,
                            'price' => $variantData['price'] ?? $product->price,
                        ]);

                        if ($existingVariant->is_principal) {
                            $product->update([
                                'price' => $variantData['price'] ?? $product->price,
                            ]);
                        }
                        $processedVariantIds[] = $existingVariant->id;
                    } else {
                        $hasPrincipalExisting = $existingVariants->where('is_principal', true)->isNotEmpty();
                        $shouldBePrincipal = $isFirstVariant && !$hasPrincipalExisting;

                        $variant = $product->variants()->create([
                            'sku' => $variantData['sku'] ?? null,
                            'barcode' => $variantData['barcode'] ?? null,
                            'price' => $variantData['price'] ?? $product->price,
                            'stock' => $variantData['stock'] ?? 0,
                            'is_principal' => $shouldBePrincipal,
                        ]);

                        if ($shouldBePrincipal) {
                            $product->update([
                                'price' => $variantData['price'] ?? $product->price,
                            ]);
                            $isFirstVariant = false;
                        }

                        if (!empty($variantData['attributes'])) {
                            foreach ($variantData['attributes'] as $attributeId => $valueName) {
                                $attributeValue = AttributeValue::where('attribute_id', $attributeId)
                                    ->where('value', $valueName)
                                    ->first();

                                if ($attributeValue) {
                                    $variant->attributeValues()->attach($attributeValue->id);
                                }
                            }
                        }

                        $processedVariantIds[] = $variant->id;
                    }
                }

                $product->variants()->whereNotIn('id', $processedVariantIds)->delete();

            } else {
                $existingVariant = $product->variants()->first();
                if ($existingVariant) {
                    $existingVariant->update([
                        'sku' => $data['sku'] ?? null,
                        'barcode' => $data['barcode'] ?? null,
                        'price' => $data['price'],
                        'is_principal' => true,
                    ]);
                } else {
                    $product->variants()->create([
                        'sku' => $data['sku'] ?? null,
                        'barcode' => $data['barcode'] ?? null,
                        'price' => $data['price'],
                        'stock' => 0,
                        'is_principal' => true,
                    ]);
                }
            }
        });

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Producto ' . $data['name'] . ' ha sido actualizado',
        ]);

        return redirect()->route('inventory.products.edit', $product);
    }

    public function destroyWeb(Product $product)
    {
        Gate::authorize('delete_products', $product);

        $product->variants()->delete();
        $product->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Producto ' . $product->name . ' ha sido eliminado correctamente',
        ]);

        return redirect()->route('inventory.products.index');
    }

    public function massDestroyWeb(Request $request)
    {
        Gate::authorize('delete_products', Product::class);
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->back();
        }

        $count = 0;

        foreach ($ids as $id) {
            $product = Product::find($id);
            if ($product) {
                $product->variants()->delete();
                $product->delete();
                $count++;
            }
        }

        if ($count > 0) {
            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => $count . ' productos han sido eliminado correctamente',
            ]);
        } else {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => '¡Atención!',
                'text' => 'No se eliminaron productos.',
            ]);
        }

        return redirect()->route('inventory.products.index');
    }

    public function qrWeb(Product $product)
    {
        Gate::authorize('read_products', Product::class);
        $product->load(['variants.attributeValues', 'variants.product']);
        $styles = QrStyle::all();
        $variants = $product->variants;
        return Inertia::render('inventory/products/Qr', compact('product', 'variants', 'styles'));
    }

    public function massQrWeb(Request $request)
    {
        Gate::authorize('read_products', Product::class);

        if ($request->isMethod('post')) {
            session([
                'mass_qr_ids_products' => $request->input('ids'),
                'mass_qr_select_all_products' => $request->input('select_all'),
                'mass_qr_search_products' => $request->input('search')
            ]);
            return redirect()->route('inventory.products.mass_qr');
        }

        $ids = session('mass_qr_ids_products');
        $selectAll = session('mass_qr_select_all_products');
        $search = session('mass_qr_search_products');

        $query = Product::with(['variants.attributeValues', 'variants.product']);

        if ($selectAll) {
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('variants', function ($variantQuery) use ($search) {
                            $variantQuery->where('sku', 'like', "%{$search}%");
                        })
                        ->orWhereHas('variants', function ($variantQuery) use ($search) {
                            $variantQuery->where('barcode', 'like', "%{$search}%");
                        })
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }
        } else {
            if (empty($ids)) {
                return redirect()->route('inventory.products.index');
            }
            $query->whereIn('id', $ids);
        }

        $products = $query->get();
        $variants = $products->pluck('variants')->flatten();
        $styles = QrStyle::all();

        return Inertia::render('inventory/BulkQr', [
            'variants' => $variants,
            'styles' => $styles,
            'context' => 'products'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        Gate::authorize('update_products', $product);
        $categories = Category::all();
        // Usamos la misma vista 'admin.products.form' para edición
        return view('admin.products.form', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        Gate::authorize('update_products', $product);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
        ]);

        $product->update($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Producto ' . $data['name'] . ' ha sido actualizado',
        ]);

        return redirect()->route('admin.products.edit', $product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        Gate::authorize('delete_products', $product);
        if ($product->variants()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => '¡Error!',
                'text' => 'No se puede eliminar el producto porque tiene variantes asociadas.',
            ]);

            return redirect()->route('admin.products.index');
        }

        $product->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Producto ' . $product->name . ' ha sido eliminado',
        ]);

        return redirect()->route('admin.products.index');
    }

    public function dropzone(Request $request, Product $product)
    {
        Gate::authorize('upload_product_images', $product);
        $image = $product->images()->create([
            'path' => Storage::put('/images/products', $request->file('file')),
            'size' => $request->file('file')->getSize(),
        ]);

        return response()->json([
            'id' => $image->id,
            'path' => $image->path,
        ]);
    }

    public function import()
    {
        Gate::authorize('import_products', Product::class);
        return view('admin.products.import');
    }

    public function getCategoriesApi()
    {
        return Category::with('parent')->get();
    }
}
