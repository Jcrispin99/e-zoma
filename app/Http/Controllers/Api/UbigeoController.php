<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UbigeoDepartment;
use App\Models\UbigeoProvince;
use App\Models\UbigeoDistrict;
use Illuminate\Http\Request;

class UbigeoController extends Controller
{
    public function departments()
    {
        return UbigeoDepartment::select('id', 'name')->orderBy('name')->get();
    }

    public function provinces(Request $request)
    {
        $departmentId = $request->query('department_id');
        if (!$departmentId) {
            return response()->json([]);
        }
        return UbigeoProvince::where('department_id', $departmentId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function districts(Request $request)
    {
        $provinceId = $request->query('province_id');
        if (!$provinceId) {
            return response()->json([]);
        }
        return UbigeoDistrict::where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}