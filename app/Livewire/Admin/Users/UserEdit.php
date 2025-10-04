<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Models\Company;
use Livewire\Component;

class UserEdit extends Component
{
    public User $user;
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $selectedCompanies = [];
    public $allCompanies;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->allCompanies = Company::all();
        $this->selectedCompanies = $this->user->companies->pluck('id')->toArray();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if (!empty($this->password)) {
            $data['password'] = bcrypt($this->password);
        }

        $this->user->update($data);
        $this->user->companies()->sync($this->selectedCompanies);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Usuario actualizado exitosamente',
            'text' => 'El usuario ' . $this->name . ' ha sido actualizado exitosamente',
        ]);

        return redirect()->route('admin.users.edit', $this->user);
    }

    public function render()
    {
        return view('livewire.admin.users.user-edit');
    }
}
