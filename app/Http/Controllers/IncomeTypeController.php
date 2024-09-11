<?php

namespace App\Http\Controllers;

use App\Models\IncomeType;
use Illuminate\Http\Request;

class IncomeTypeController extends Controller
{
    public function index()
    {
        return response()->json(IncomeType::all());
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate(['name' => 'required|string|max:255']);
        $incomeType = IncomeType::create($validatedData);
        return response()->json($incomeType, 201);
    }
}
