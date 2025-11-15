<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    public function index()
    {
        Gate::authorize('read_roles', Role::class);
        return view('admin.roles.index');
    }

    public function create()
    {
        Gate::authorize('create_roles', Role::class);
        return view('admin.roles.form');
    }

    public function edit(Role $role)
    {
        Gate::authorize('update_roles', $role);
        return view('admin.roles.form', compact('role'));
    }

    public function destroy(Role $role)
    {
        Gate::authorize('delete_roles', $role);
        $role->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Rol ha sido eliminado',
        ]);

        return redirect()->route('admin.roles.index');
    }
}