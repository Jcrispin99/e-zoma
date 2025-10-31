<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Identity;

class IdentityController extends Controller
{
    public function index()
    {
        return Identity::select('id', 'name')->orderBy('id')->get();
    }
}
