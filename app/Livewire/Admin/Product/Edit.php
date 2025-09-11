<?php

namespace App\Livewire\Admin\Product;

use Livewire\Component;
use App\Models\Category;
use App\Models\Product;

class Edit extends Component
{
    public $tab = 'product';
    public $product;

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

    public function mount($id)
    {
        $this->product = Product::findOrFail($id);
        
        // Cargar los datos del producto
        $this->name = $this->product->name;
        $this->description = $this->product->description;
        $this->price = $this->product->price;
        $this->category_id = $this->product->category_id;
    }

    public function render()
    {
        $categories = Category::all();
        return view('livewire.admin.product.edit', [
            'categories' => $categories
        ]);
    }

    public function updateProduct()
    {
        // Validar los campos del producto
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        // Actualizar el producto
        $this->product->update([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
        ]);

        // Mostrar mensaje de éxito
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Actualizado!',
            'text' => 'Producto ' . $this->name . ' ha sido actualizado correctamente',
        ]);

        // Redirigir a la lista de productos
        return redirect()->route('admin.products.index');
    }

    public function saveAttributes()
    {
        // Lógica básica para guardar atributos (por implementar)
        $this->validate([
            'attribute_name' => 'required|string|max:255',
            'attribute_value' => 'required|string|max:255',
        ]);

        // Aquí iría la lógica de guardado de atributos
        session()->flash('message', 'Atributos actualizados correctamente.');
    }
}