<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Customer::select('id', 'identity_id', 'document_number', 'name')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "{$search}")
                    ->orWhere('document_number', 'like', "{$search}");
            })
            ->when(
                $request->exists('selected'),
                fn($query) => $query->whereIn('id', $request->input('selected', [])),
                fn($query) => $query->limit(10)
            )->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // Validación base
        $validated = validator($data, [
            'identity_id'     => ['required', 'exists:identities,id'],
            'document_number' => ['required', 'string', 'max:20', 'unique:customers,document_number'],
            'name'            => ['required', 'string', 'max:255'],
            'address'         => ['nullable', 'string', 'max:255'],
            'email'           => ['nullable', 'email', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:20'],
        ])->after(function ($validator) use ($data) {
            // Validaciones específicas según tipo de identidad
            $identity = \App\Models\Identity::find($data['identity_id'] ?? null);
            $doc = $data['document_number'] ?? '';
            if ($identity) {
                if ($identity->name === 'DNI') {
                    if (!preg_match('/^\d{8}$/', $doc)) {
                        $validator->errors()->add('document_number', 'El DNI debe tener 8 dígitos.');
                    }
                } elseif ($identity->name === 'RUC') {
                    if (!preg_match('/^\d{11}$/', $doc)) {
                        $validator->errors()->add('document_number', 'El RUC debe tener 11 dígitos.');
                    } elseif (!$this->isValidRuc($doc)) {
                        $validator->errors()->add('document_number', 'El RUC no es válido.');
                    }
                }
            }
        })->validate();

        $customer = Customer::create([
            'identity_id'     => $validated['identity_id'],
            'document_number' => $validated['document_number'],
            'name'            => $validated['name'],
            'address'         => $validated['address'] ?? null,
            'email'           => $validated['email'] ?? null,
            'phone'           => $validated['phone'] ?? null,
        ]);

        return response()->json($customer->load('identity'), 201);
    }

    /**
     * Valida dígito verificador de RUC peruano.
     */
    private function isValidRuc(string $ruc): bool
    {
        if (!preg_match('/^\d{11}$/', $ruc)) {
            return false;
        }
        // Pesos estándar para cálculo del dígito verificador del RUC
        $weights = [5,4,3,2,7,6,5,4,3,2];
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int) $ruc[$i]) * $weights[$i];
        }
        $remainder = $sum % 11;
        $checkDigit = 11 - $remainder;
        if ($checkDigit === 11) $checkDigit = 0;
        if ($checkDigit === 10) $checkDigit = 1;
        return (int)$ruc[10] === $checkDigit;
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        //
    }
}
