<?php

namespace App\Livewire\Admin\Categories;

use Livewire\Component;
use App\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CategoryForm extends Component
{
    public ?int $categoryId = null;
    public bool $isEditing = false;

    public bool $redirectAfterSave = true;

    public string $name = '';
    public ?string $description = null;
    public ?int $parent_id = null;

    /** @var array<int, array{id:int, name:string}> */
    public array $parentOptions = [];

    public function mount(?int $categoryId = null): void
    {
        $this->categoryId = $categoryId;
        $this->isEditing = (bool) $categoryId;

        // Construir opciones para padre usando full_name cuando esté disponible
        $opts = Category::query()
            ->select(['id', 'name', 'full_name'])
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id' => (int) $c->id,
                'name' => (string) ($c->full_name ?: $c->name),
            ])
            ->toArray();

        // Si estamos editando, precargar datos y excluir la misma categoría de opciones
        if ($this->isEditing) {
            $category = Category::findOrFail($categoryId);
            $this->name = (string) $category->name;
            $this->description = $category->description;
            $this->parent_id = $category->parent_id;

            $this->parentOptions = array_values(array_filter($opts, fn($opt) => $opt['id'] !== $category->id));
        } else {
            $this->parentOptions = $opts;
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($this->categoryId)],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $data['slug'] = Str::slug($data['name']);

        if (!empty($data['parent_id'])) {
            $parent = Category::find($data['parent_id']);
            $parentFullName = $parent?->full_name ?: $parent?->name;
            $data['full_name'] = ($parentFullName ? $parentFullName . ' / ' : '') . $data['name'];
        } else {
            $data['full_name'] = $data['name'];
        }

        if ($this->isEditing) {
            $category = Category::findOrFail($this->categoryId);
            $category->update($data);
        } else {
            $category = Category::create($data);
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Categoria ' . $category->name . ($this->isEditing ? ' ha sido actualizada' : ' ha sido creada'),
        ]);

        if ($this->redirectAfterSave) {
            return redirect()->route('admin.categories.edit', $category);
        }

        $this->dispatch('category:saved', $category->id);
    }

    public function render()
    {
        return view('livewire.admin.categories.form');
    }
}