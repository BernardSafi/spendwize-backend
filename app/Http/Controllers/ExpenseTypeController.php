<?php

namespace App\Http\Controllers;

use App\Models\ExpenseType;
use Illuminate\Http\Request;

class ExpenseTypeController extends Controller
{
    public function index()
    {
        return response()->json(ExpenseType::all());
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate(['name' => 'required|string|max:255']);
        $expenseType = ExpenseType::create($validatedData);
        return response()->json($expenseType, 201);
    }
}
