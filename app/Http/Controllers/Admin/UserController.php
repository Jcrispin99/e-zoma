<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_users', User::class);
        return view('admin.users.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_users', User::class);
        $companies = Company::all();
        return view('admin.users.create', compact('companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create_users', User::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'companies' => 'nullable|array',
            'companies.*' => 'exists:companies,id',
        ]);

        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);

        // Asignar compañías al usuario
        if (isset($data['companies'])) {
            $user->companies()->sync($data['companies']);
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Usuario creado exitosamente',
            'text' => 'El usuario ' . $data['name'] . ' ha sido creado exitosamente',
        ]);

        return redirect()->route('admin.users.edit', $user);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        Gate::authorize('read_users', $user);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        Gate::authorize('update_users', $user);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('update_users', $user);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'companies' => 'nullable|array',
            'companies.*' => 'exists:companies,id',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        // Actualizar compañías del usuario
        $user->companies()->sync($data['companies'] ?? []);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Usuario actualizado exitosamente',
            'text' => 'El usuario ' . $data['name'] . ' ha sido actualizado exitosamente',
        ]);

        return redirect()->route('admin.users.edit', $user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete_users', $user);
        $user->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Usuario eliminado exitosamente',
            'text' => 'El usuario ' . $user->name . ' ha sido eliminado exitosamente',
        ]);

        return redirect()->route('admin.users.index');
    }
}
