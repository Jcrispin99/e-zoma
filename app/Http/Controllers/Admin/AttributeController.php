<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function searchValues(Request $request, $index)
    {
        $query = $request->get('search', '');
        
        if (empty($query)) {
            return response()->json([]);
        }
        
        // Buscar valores existentes
        $existingValues = AttributeValue::where('value', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($attributeValue) {
                return [
                    'label' => $attributeValue->value,
                    'value' => $attributeValue->value,
                    'id' => $attributeValue->id
                ];
            });
        
        // Si no hay coincidencias exactas, agregar opción para crear nuevo valor
        $exactMatch = AttributeValue::where('value', $query)->exists();
        
        if (!$exactMatch && !empty($query)) {
            $existingValues->prepend([
                'label' => "Crear: {$query}",
                'value' => $query,
                'id' => null,
                'create_new' => true
            ]);
        }
        
        return response()->json($existingValues->values()->all());
    }

    public function searchAttributes(Request $request)
    {
        $query = $request->get('search', '');
        
        if (empty($query)) {
            return response()->json([]);
        }
        
        // Buscar atributos existentes
        $existingAttributes = Attribute::where('name', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($attribute) {
                return [
                    'label' => $attribute->name,
                    'value' => $attribute->name,
                    'id' => $attribute->id
                ];
            });
        
        // Si no hay coincidencias exactas, agregar opción para crear nuevo atributo
        $exactMatch = Attribute::where('name', $query)->exists();
        
        if (!$exactMatch && !empty($query)) {
            $existingAttributes->prepend([
                'label' => "Crear: {$query}",
                'value' => $query,
                'id' => null,
                'create_new' => true
            ]);
        }
        
        return response()->json($existingAttributes->values()->all());
    }
}