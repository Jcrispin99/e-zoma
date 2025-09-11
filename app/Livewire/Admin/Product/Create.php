<?php

namespace App\Livewire\Admin\Product;

use Livewire\Component;
use App\Models\Category;
use App\Models\Product;

class Create extends Component
{
    public $tab = 'product';

    // Propiedades del producto
    public $name = '';
    public $description = '';
    public $price = '';
    public $category_id = '';

    // Propiedades para pestaña Atributos (básico por ahora)
    public $attribute_name = '';
    public $attribute_value = '';

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function mount()
    {
        // Inicializar valores si es necesario
    }

    public function render()
    {
        $categories = Category::all();
        return view('livewire.admin.product.create', [
            'categories' => $categories
        ]);
    }

    public function saveProduct()
    {
        // Validar los campos del producto
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        // Crear el producto
        $product = Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
        ]);

        // Mostrar mensaje de éxito
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Producto ' . $this->name . ' ha sido creado',
        ]);

        // Redirigir a la página de edición del producto
        return redirect()->route('admin.products.edit', $product->id);
    }

    public function saveAttributes()
    {
        // Lógica básica para guardar atributos (por implementar)
        $this->validate([
            'attribute_name' => 'required|string|max:255',
            'attribute_value' => 'required|string|max:255',
        ]);

        // Aquí iría la lógica de guardado de atributos
        session()->flash('message', 'Atributos guardados correctamente.');
    }
}