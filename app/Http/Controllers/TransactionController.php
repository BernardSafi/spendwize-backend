<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Display a listing of the transactions.
     */
    public function index()
    {
        $transactions = Transaction::with('user')->get();
        return response()->json($transactions);
    }

    /**
     * Store a new transaction.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:income,expense,transfer,exchange',
            'subtype' => 'nullable|string',
            'amount' => 'required|numeric',
            'currency' => 'required|in:USD,LBP',
            'from_account' => 'nullable|string',
            'to_account' => 'nullable|string',
            'exchange_rate' => 'nullable|numeric',
            'description' => 'nullable|string', 
        ]);

        $transaction = Transaction::create($validatedData);

        return response()->json($transaction, 201);
    }

    /**
     * Display the specified transaction.
     */
    public function show($id)
    {
        $transaction = Transaction::with('user')->findOrFail($id);
        return response()->json($transaction);
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'type' => 'required|in:income,expense,transfer,exchange',
            'subtype' => 'nullable|string',
            'amount' => 'required|numeric',
            'currency' => 'required|in:USD,LBP',
            'from_account' => 'nullable|string',
            'to_account' => 'nullable|string',
            'exchange_rate' => 'nullable|numeric',
            'description' => 'nullable|string', 
        ]);

        $transaction = Transaction::findOrFail($id);
        $transaction->update($validatedData);

        return response()->json($transaction);
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted successfully']);
    }

    // Transfer from Wallet LBP to Savings LBP
    public function walletToSavingLbp(Request $request)
{
    $user = Auth::user(); // Get the authenticated user

    // Validate input
    $validatedData = $request->validate([
        'amount' => 'required|numeric|min:1',
    ]);

    // Get user's wallet and savings account
    $wallet = $user->wallet; 
    $savings = $user->savingsAccounts; 

    // Validate if the user has sufficient balance in Wallet LBP
    $walletBalance = $wallet->lbp_balance ?? 0; // Use default value if null
    if ($walletBalance < $validatedData['amount']) {
        return response()->json(['message' => 'Insufficient balance in Wallet LBP.'], 400);
    }

    try {
        // Create the transfer transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id, // Assuming this is needed for reference
            'savings_account_id' => $savings->id, // Assuming this is needed for reference
            'type' => 'transfer',
            'amount' => $validatedData['amount'],
            'currency' => 'LBP',
            'from_account' => 'wallet_lbp',
            'to_account' => 'savings_lbp',
        ]);

        // Update balances
        $wallet->lbp_balance -= $validatedData['amount'];
        $savings->lbp_balance += $validatedData['amount'];

        // Save the updated balances
        $wallet->save();
        $savings->save();

        return response()->json($transaction, 201);
    } catch (\Exception $e) {
        // Log the exception for debugging purposes
        return response()->json(['message' => 'Transfer failed, please try again.'], 500);
    }
}


    // Transfer from Savings LBP to Wallet LBP
    public function savingToWalletLbp(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
    
        // Validate input
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);
    
        // Get user's wallet and savings account
        $wallet = $user->wallet; // Assuming this is the correct relationship
        $savings = $user->savingsAccounts; // Assuming this is the correct relationship
    
        // Validate if the user has sufficient balance in Savings LBP
        $savingsBalance = $savings->lbp_balance ?? 0; // Use default value if null
        if ($savingsBalance < $validatedData['amount']) {
            return response()->json(['message' => 'Insufficient balance in Savings LBP.'], 400);
        }
    
        try {
            // Create the transfer transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id, // Assuming this is needed for reference
                'savings_account_id' => $savings->id, // Assuming this is needed for reference
                'type' => 'transfer',
                'amount' => $validatedData['amount'],
                'currency' => 'LBP',
                'from_account' => 'savings_lbp',
                'to_account' => 'wallet_lbp',
            ]);
    
            // Update balances
            $savings->lbp_balance -= $validatedData['amount'];
            $wallet->lbp_balance += $validatedData['amount'];
    
            // Save the updated balances
            $savings->save();
            $wallet->save();
    
            return response()->json($transaction, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Transfer failed, please try again.'], 500);
        }
    }

    // Transfer from Wallet USD to Savings USD
    public function walletToSavingUsd(Request $request)
{
    $user = Auth::user(); // Get the authenticated user

    // Validate input
    $validatedData = $request->validate([
        'amount' => 'required|numeric|min:1',
    ]);

    // Get user's wallet and savings account
    $wallet = $user->wallet; 
    $savings = $user->savingsAccounts; 

    // Validate if the user has sufficient balance in Wallet USD
    $walletBalance = $wallet->usd_balance ?? 0; // Use default value if null
    if ($walletBalance < $validatedData['amount']) {
        return response()->json(['message' => 'Insufficient balance in Wallet USD.'], 400);
    }

    try {
        // Create the transfer transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id, // Assuming this is needed for reference
            'savings_account_id' => $savings->id, // Assuming this is needed for reference
            'type' => 'transfer',
            'amount' => $validatedData['amount'],
            'currency' => 'USD',
            'from_account' => 'wallet_usd',
            'to_account' => 'savings_usd',
        ]);

        // Update balances
        $wallet->usd_balance -= $validatedData['amount'];
        $savings->usd_balance += $validatedData['amount'];

        // Save the updated balances
        $wallet->save();
        $savings->save();

        return response()->json($transaction, 201);
    } catch (\Exception $e) {
        // Log the exception for debugging purposes
        return response()->json(['message' => 'Transfer failed, please try again.'], 500);
    }
}

    

    // Transfer from Savings USD to Wallet USD
    public function savingToWalletUsd(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
    
        // Validate input
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);
    
        // Get user's wallet and savings account
        $wallet = $user->wallet; // Assuming this is the correct relationship
        $savings = $user->savingsAccounts; // Assuming this is the correct relationship
    
        // Validate if the user has sufficient balance in Savings USD
        $savingsBalance = $savings->usd_balance ?? 0; // Use default value if null
        if ($savingsBalance < $validatedData['amount']) {
            return response()->json(['message' => 'Insufficient balance in Savings USD.'], 400);
        }
    
        try {
            // Create the transfer transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id, // Assuming this is needed for reference
                'savings_account_id' => $savings->id, // Assuming this is needed for reference
                'type' => 'transfer',
                'amount' => $validatedData['amount'],
                'currency' => 'USD',
                'from_account' => 'savings_usd',
                'to_account' => 'wallet_usd',
            ]);
    
            // Update balances
            $savings->usd_balance -= $validatedData['amount'];
            $wallet->usd_balance += $validatedData['amount'];
    
            // Save the updated balances
            $savings->save();
            $wallet->save();
    
            return response()->json($transaction, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Transfer failed, please try again.'], 500);
        }
    }
    
}
