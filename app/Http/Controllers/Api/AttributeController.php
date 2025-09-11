<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    /**
     * Obtener todos los atributos
     */
    public function index()
    {
        $attributes = Attribute::select('id', 'name')
            ->orderBy('name')
            ->get();
            
        return response()->json($attributes);
    }
    
    /**
     * Buscar atributos por nombre
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        $attributes = Attribute::select('id', 'name')
            ->when($query, function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function($attribute) {
                return [
                    'label' => $attribute->name,
                    'value' => $attribute->id
                ];
            });
            
        return response()->json($attributes);
    }
    
    /**
     * Obtener valores de un atributo específico
     */
    public function getValues($attributeId, Request $request)
    {
        $query = $request->get('q', '');
        
        $values = AttributeValue::select('id', 'value')
            ->where('attribute_id', $attributeId)
            ->when($query, function($q) use ($query) {
                $q->where('value', 'like', '%' . $query . '%');
            })
            ->orderBy('value')
            ->limit(20)
            ->get()
            ->map(function($value) {
                return [
                    'label' => $value->value,
                    'value' => $value->value
                ];
            });
            
        // Agregar opción para crear nuevo valor si hay búsqueda
        if ($query && !$values->contains('value', $query)) {
            $values->prepend([
                'label' => 'Crear: ' . $query,
                'value' => 'Crear: ' . $query
            ]);
        }
            
        return response()->json($values);
    }
    
    /**
     * Crear un nuevo valor para un atributo
     */
    public function createValue(Request $request, $attributeId)
    {
        $request->validate([
            'value' => 'required|string|max:255'
        ]);
        
        $value = $request->input('value');
        
        // Verificar si el valor ya existe
        $existingValue = AttributeValue::where('attribute_id', $attributeId)
            ->where('value', $value)
            ->first();
            
        if ($existingValue) {
            return response()->json([
                'label' => $existingValue->value,
                'value' => $existingValue->value
            ]);
        }
        
        // Crear nuevo valor
        $attributeValue = AttributeValue::create([
            'attribute_id' => $attributeId,
            'value' => $value
        ]);
        
        return response()->json([
            'label' => $attributeValue->value,
            'value' => $attributeValue->value
        ], 201);
    }
}