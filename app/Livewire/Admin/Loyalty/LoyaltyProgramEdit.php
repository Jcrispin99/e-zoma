<?php

namespace App\Livewire\Admin\Loyalty;

use Livewire\Component;
use App\Models\LoyaltyProgram;
use Illuminate\Validation\Rule;

class LoyaltyProgramEdit extends Component
{
    public LoyaltyProgram $program;

    public string $name = '';
    public string $code = '';
    public string $type = 'promotion';
    public string $scope = 'both';
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

    public function mount(LoyaltyProgram $program)
    {
        $this->program = $program;
        $this->name = $program->name ?? '';
        $this->code = $program->code ?? '';
        $this->type = $program->type ?? 'promotion';
        $this->scope = $program->scope ?? 'both';
        $this->is_active = (bool) ($program->is_active ?? true);
        $this->valid_from = optional($program->valid_from)->format('Y-m-d');
        $this->valid_to = optional($program->valid_to)->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('loyalty_programs', 'code')->ignore($this->program->id),
            ],
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

        $this->program->update([
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
            'text' => 'Programa de lealtad actualizado exitosamente.',
        ]);

        return redirect()->route('admin.loyalty-programs.index');
    }

    public function render()
    {
        return view('livewire.admin.loyalty.program-edit');
    }
}
