<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Category;

class ProductCreate extends Component
{
    public $tab = 'persona';
    
    // Propiedades para pestaña Persona
    public $nombres = '';
    public $apellidos = '';
    public $dni = '';
    
    // Propiedades para pestaña Empresa
    public $razon_social = '';
    public $ruc = '';
    public $contacto = '';
    
    // Propiedades del producto original
    public $name = '';
    public $description = '';
    public $price = '';
    public $category_id = '';
    
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
        
        return view('livewire.admin.product-create', [
            'categories' => $categories
        ]);
    }
    
    public function savePersona()
    {
        // Lógica para guardar datos de persona (por implementar)
        $this->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => 'required|string|max:20',
        ]);
        
        // Aquí iría la lógica de guardado
        session()->flash('message', 'Datos de persona guardados correctamente.');
    }
    
    public function saveEmpresa()
    {
        // Lógica para guardar datos de empresa (por implementar)
        $this->validate([
            'razon_social' => 'required|string|max:255',
            'ruc' => 'required|string|max:20',
            'contacto' => 'required|string|max:255',
        ]);
        
        // Aquí iría la lógica de guardado
        session()->flash('message', 'Datos de empresa guardados correctamente.');
    }
}