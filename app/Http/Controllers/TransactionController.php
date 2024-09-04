<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Display a listing of the user's transactions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = Auth::user(); // Get the authenticated user

        // Retrieve all transactions for this user
        $transactions = Transaction::where('user_id', $user->id)->get();

        return response()->json($transactions);
    }

    /**
     * Display the specified transaction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user(); // Get the authenticated user

        // Retrieve the transaction that belongs to the user
        $transaction = Transaction::where('user_id', $user->id)->findOrFail($id);

        return response()->json($transaction);
    }

    /**
     * Store a newly created transaction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user

        // Validate the incoming request
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|string', // 'income', 'expense', 'transfer', etc.
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        // Create a new transaction
        $transaction = new Transaction([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => $request->type,
            'description' => $request->description,
            'date' => $request->date,
        ]);

        $transaction->save();

        return response()->json($transaction, 201); // 201 Created
    }

    /**
     * Update the specified transaction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user(); // Get the authenticated user

        // Validate the incoming request
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|string',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        // Find the transaction that belongs to the user
        $transaction = Transaction::where('user_id', $user->id)->findOrFail($id);

        // Update the transaction details
        $transaction->amount = $request->amount;
        $transaction->type = $request->type;
        $transaction->description = $request->description;
        $transaction->date = $request->date;

        $transaction->save();

        return response()->json($transaction);
    }

    /**
     * Remove the specified transaction from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user(); // Get the authenticated user

        // Find the transaction that belongs to the user
        $transaction = Transaction::where('user_id', $user->id)->findOrFail($id);

        // Delete the transaction
        $transaction->delete();

        return response()->json(null, 204); // 204 No Content
    }

    /**
     * Retrieve the last 5 transactions of the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentTransactions()
    {
        $user = Auth::user(); // Get the authenticated user

        // Retrieve the last 5 transactions for this user, ordered by date
        $transactions = Transaction::where('user_id', $user->id)
                                    ->orderBy('date', 'desc')
                                    ->take(5)
                                    ->get();

        return response()->json($transactions);
    }
}
