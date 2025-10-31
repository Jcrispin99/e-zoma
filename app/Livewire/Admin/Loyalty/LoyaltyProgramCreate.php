<?php

namespace App\Livewire\Admin\Loyalty;

use Livewire\Component;
use App\Models\LoyaltyProgram;
use Illuminate\Validation\Rule;

class LoyaltyProgramCreate extends Component
{
    public string $name = '';
    public string $code = '';
    public string $type = 'promotion'; // 'promotion' | 'points'
    public string $scope = 'both'; // 'pos' | 'sales' | 'both'
    public bool $is_active = true;
    public ?string $valid_from = null;
    public ?string $valid_to = null;

    public function boot()
    {
        $this->withValidator(function ($validator) {
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $html = "<ul class='text-left'>";
                foreach ($errors as $error) {
                    $html .= "<li>{$error[0]}</li>";
                }
                $html .= "</ul>";
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Error de validación',
                    'html' => $html,
                ]);
            }
        });
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('loyalty_programs', 'code')],
            'type' => ['required', Rule::in(['promotion', 'points'])],
            'scope' => ['required', Rule::in(['pos', 'sales', 'both'])],
            'is_active' => ['boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    public function save()
    {
        $this->validate();

        $program = LoyaltyProgram::create([
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'scope' => $this->scope,
            'is_active' => (bool) $this->is_active,
            'valid_from' => $this->valid_from ?: null,
            'valid_to' => $this->valid_to ?: null,
        ]);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Programa de lealtad creado exitosamente.',
        ]);

        return redirect()->route('admin.loyalty-programs.index');
    }

    public function toggleScope(string $target): void
    {
        $pos = in_array($this->scope, ['pos', 'both']);
        $sales = in_array($this->scope, ['sales', 'both']);

        if ($target === 'pos') {
            $pos = !$pos;
        } elseif ($target === 'sales') {
            $sales = !$sales;
        }

        if ($pos && $sales) {
            $this->scope = 'both';
        } elseif ($pos) {
            $this->scope = 'pos';
        } elseif ($sales) {
            $this->scope = 'sales';
        } else {
            // Mantener al menos una selección por usabilidad: si ambas quedan falsas, default a 'pos'
            $this->scope = 'pos';
        }
    }

    public function render()
    {
        return view('livewire.admin.loyalty.program-create');
    }
}
