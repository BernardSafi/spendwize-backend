<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\IncomeType; 
use App\Models\ExpenseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    public function addIncome(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
    
        // Check if user is authenticated
        if ($user) {
            Log::info('User authenticated successfully', ['user_id' => $user->id]);
        } else {
            Log::warning('User authentication failed');
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    
        Log::info("Starting income addition...");
    
        // Validate input
        try {
            // Validate input
            $validatedData = $request->validate([
                'amount' => 'required|numeric|min:1',
                'currency' => 'required|in:USD,LBP',
                'description' => 'nullable|string',
                'income_type' => 'required|string|exists:income_types,name', // Validate income type name
            ]);
    
            // Log validation success
            Log::info("Validation passed", ['data' => $validatedData]);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation failed", ['errors' => $e->validator->errors()->all()]);
            return response()->json(['message' => 'Validation failed', 'errors' => $e->validator->errors()], 422);
        }
    
        // Retrieve the income type ID based on the name
        $incomeType = IncomeType::where('name', $validatedData['income_type'])->first();
        if (!$incomeType) {
            return response()->json(['message' => 'Income type not found'], 404);
        }
    
        // Get user's wallet
        $wallet = $user->wallet; // Assuming the user has a relationship with a wallet
    
        // Update wallet balance based on the currency
        if ($validatedData['currency'] == 'USD') {
            $wallet->usd_balance += $validatedData['amount'];
        } elseif ($validatedData['currency'] == 'LBP') {
            $wallet->lbp_balance += $validatedData['amount'];
        }
    
        // Save the updated wallet balance
        $wallet->save();
    
        // Log the transaction as 'income' and associate with income_type
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => $validatedData['amount'],
            'currency' => $validatedData['currency'],
            'description' => $validatedData['description'],
            'income_type_id' => $incomeType->id, // Use the retrieved income type ID
        ]);
    
        Log::info('Income transaction recorded.', ['transaction' => $transaction]);
    
        return response()->json([
            'message' => 'Income added successfully',
            'transaction' => $transaction,
        ], 201);
    }

    public function addExpense(Request $request)
{
    $user = Auth::user(); // Get the authenticated user

    // Check if user is authenticated
    if ($user) {
        Log::info('User authenticated successfully', ['user_id' => $user->id]);
    } else {
        Log::warning('User authentication failed');
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    Log::info("Starting expense addition...");

    // Validate input
    try {
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|in:USD,LBP',
            'description' => 'nullable|string',
            'expense_type' => 'required|string|exists:expense_types,name', // Validate expense type name
        ]);

        // Log validation success
        Log::info("Validation passed", ['data' => $validatedData]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error("Validation failed", ['errors' => $e->validator->errors()->all()]);
        return response()->json(['message' => 'Validation failed', 'errors' => $e->validator->errors()], 422);
    }

    // Retrieve the expense type ID based on the name
    $expenseType = ExpenseType::where('name', $validatedData['expense_type'])->first();
    if (!$expenseType) {
        return response()->json(['message' => 'Expense type not found'], 404);
    }

    // Get user's wallet
    $wallet = $user->wallet; // Assuming the user has a relationship with a wallet

    // Update wallet balance based on the currency
    if ($validatedData['currency'] == 'USD') {
        $wallet->usd_balance -= $validatedData['amount']; // Subtract from USD balance
    } elseif ($validatedData['currency'] == 'LBP') {
        $wallet->lbp_balance -= $validatedData['amount']; // Subtract from LBP balance
    }

    // Save the updated wallet balance
    $wallet->save();

    // Log the transaction as 'expense' and associate with expense_type
    $transaction = Transaction::create([
        'user_id' => $user->id,
        'type' => 'expense',
        'amount' => $validatedData['amount'],
        'currency' => $validatedData['currency'],
        'description' => $validatedData['description'],
        'expense_type_id' => $expenseType->id, // Use the retrieved expense type ID
    ]);

    Log::info('Expense transaction recorded.', ['transaction' => $transaction]);

    return response()->json([
        'message' => 'Expense added successfully',
        'transaction' => $transaction,
    ], 201);
}

    
}
