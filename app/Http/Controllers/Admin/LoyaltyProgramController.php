<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyProgram;
use Illuminate\Http\Request;

class LoyaltyProgramController extends Controller
{
    public function index()
    {
        return view('admin.loyalty-programs.index');
    }

    public function create()
    {
        return view('admin.loyalty-programs.create');
    }

    public function edit(LoyaltyProgram $loyaltyProgram)
    {
        return view('admin.loyalty-programs.edit', [
            'program' => $loyaltyProgram,
        ]);
    }
}
