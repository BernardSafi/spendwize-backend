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
        // Ensure the user is authenticated
        $user = auth()->user();
    
        if ($user) {
            // Fetch transactions related to the authenticated user
            $transactions = Transaction::where('user_id', $user->id)->with('user')->get();
    
            // Return a successful response with a 201 status code
            return response()->json($transactions, 201);
        } else {
            // Return unauthorized response if the user is not authenticated
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function exchangeCurrency(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    
        $validatedData = $request->validate([
            'from_account' => 'required|string|in:wallet_usd,wallet_lbp',
            'to_account' => 'required|string|in:wallet_usd,wallet_lbp',
            'amount' => 'required|numeric|min:1',
            'exchange_rate' => 'required|numeric|min:0.01',
            'date' => 'nullable|date', // Validate the date
        ]);
    
        $fromAccount = $validatedData['from_account'];
        $toAccount = $validatedData['to_account'];
        $amount = $validatedData['amount'];
        $exchangeRate = $validatedData['exchange_rate'];
    
        if ($fromAccount === $toAccount) {
            return response()->json(['message' => 'Cannot exchange within the same account'], 400);
        }
    
        $wallet = $user->wallet;
    
        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }
    
        if ($fromAccount === 'wallet_usd' && $toAccount === 'wallet_lbp') {
            if ($wallet->usd_balance < $amount) {
                return response()->json(['message' => 'Insufficient USD balance'], 400);
            }
            $wallet->usd_balance -= $amount;
            $wallet->lbp_balance += $amount * $exchangeRate;
    
        } elseif ($fromAccount === 'wallet_lbp' && $toAccount === 'wallet_usd') {
            if ($wallet->lbp_balance < $amount) {
                return response()->json(['message' => 'Insufficient LBP balance'], 400);
            }
            $wallet->lbp_balance -= $amount;
            $wallet->usd_balance += $amount / $exchangeRate;
        }
    
        $wallet->save();
    
        $currency = ($fromAccount === 'wallet_usd') ? 'USD' : 'LBP';
    
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'exchange',
            'from_account' => $fromAccount,
            'to_account' => $toAccount,
            'amount' => $amount,
            'exchange_rate' => $exchangeRate,
            'currency' => $currency,
            'date' => $validatedData['date'] ?? now() // Use provided date or current date
        ]);
    
        return response()->json([
            'message' => 'Currency exchanged successfully',
            'from_account' => $fromAccount,
            'to_account' => $toAccount,
            'amount' => $amount,
            'exchange_rate' => $exchangeRate,
            'currency' => $currency
        ], 201);
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
            'date' => 'nullable|date', // Validate the date
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
            'date' => 'nullable|date', // Validate the date
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
        // Get the authenticated user
        $user = Auth::user();
    
        // Check if user is authenticated
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    
        // Find the transaction
        $transaction = Transaction::find($id);
    
        // Check if the transaction exists and belongs to the authenticated user
        if (!$transaction || $transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Transaction not found or access denied'], 404);
        }
    
        // Get user's wallet
        $wallet = $user->wallet; // Assuming the user has a relationship with a wallet
    
        // Handle different transaction types
        switch ($transaction->type) {
            case 'income':
                // For income, subtract the amount from the wallet
                if ($transaction->currency === 'USD') {
                    $wallet->usd_balance -= $transaction->amount;
                } elseif ($transaction->currency === 'LBP') {
                    $wallet->lbp_balance -= $transaction->amount;
                }
                break;
    
            case 'expense':
                // For expense, add the amount back to the wallet
                if ($transaction->currency === 'USD') {
                    $wallet->usd_balance += $transaction->amount;
                } elseif ($transaction->currency === 'LBP') {
                    $wallet->lbp_balance += $transaction->amount;
                }
                break;
    
            case 'transfer':
                // Handle transfers based on from_account and to_account
                if ($transaction->from_account === 'wallet_usd' && $transaction->to_account === 'savings_usd') {
                    $wallet->usd_balance += $transaction->amount;
                    $user->savingsAccounts->usd_balance -= $transaction->amount;
                } elseif ($transaction->from_account === 'wallet_lbp' && $transaction->to_account === 'savings_lbp') {
                    $wallet->lbp_balance += $transaction->amount;
                    $user->savingsAccounts->lbp_balance -= $transaction->amount;
                } elseif ($transaction->from_account === 'savings_usd' && $transaction->to_account === 'wallet_usd') {
                    $wallet->usd_balance -= $transaction->amount;
                    $user->savingsAccounts->usd_balance += $transaction->amount;
                } elseif ($transaction->from_account === 'savings_lbp' && $transaction->to_account === 'wallet_lbp') {
                    $wallet->lbp_balance -= $transaction->amount;
                    $user->savingsAccounts->lbp_balance += $transaction->amount;
                }
                break;
    
            case 'exchange':
                // For exchange, reverse the conversion between LBP and USD
                $exchangeRate = $transaction->exchange_rate; // Assuming the exchange rate is stored in the transaction
    
                if ($transaction->from_account === 'wallet_usd' && $transaction->to_account === 'wallet_lbp') {
                    // Reverse exchange from USD to LBP
                    $wallet->usd_balance += $transaction->amount; // Add back USD to wallet
                    $wallet->lbp_balance -= $transaction->amount * $exchangeRate; // Deduct LBP based on exchange rate
                } elseif ($transaction->from_account === 'wallet_lbp' && $transaction->to_account === 'wallet_usd') {
                    // Reverse exchange from LBP to USD
                    $wallet->lbp_balance += $transaction->amount; // Add back LBP to wallet
                    $wallet->usd_balance -= $transaction->amount / $exchangeRate; // Deduct USD based on exchange rate
                }
                break;
    
            default:
                return response()->json(['message' => 'Invalid transaction type'], 400);
        }
    
        // Save the updated wallet and savings account balances
        $wallet->save();
        $user->savingsAccounts->save(); // Ensure the savings account is also saved if applicable
    
        // Delete the transaction
        $transaction->delete();
    
        return response()->json(['message' => 'Transaction deleted successfully'], 200);
    }
    
    

    // Transfer from Wallet LBP to Savings LBP
    public function walletToSavingLbp(Request $request)
    {
        $user = Auth::user();
    
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:1',
            'date' => 'nullable|date', // Validate the date
        ]);
    
        $wallet = $user->wallet; 
        $savings = $user->savingsAccounts; 
    
        $walletBalance = $wallet->lbp_balance ?? 0;
        if ($walletBalance < $validatedData['amount']) {
            return response()->json(['message' => 'Insufficient balance in Wallet LBP.'], 400);
        }
    
        try {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'savings_account_id' => $savings->id,
                'type' => 'transfer',
                'amount' => $validatedData['amount'],
                'currency' => 'LBP',
                'from_account' => 'wallet_lbp',
                'to_account' => 'savings_lbp',
                'date' => $validatedData['date'] ?? now() // Use provided date or current date
            ]);
    
            $wallet->lbp_balance -= $validatedData['amount'];
            $savings->lbp_balance += $validatedData['amount'];
    
            $wallet->save();
            $savings->save();
    
            return response()->json($transaction, 201);
        } catch (\Exception $e) {
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
            'date' => 'nullable|date', // Validate the date
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
                'date' => $validatedData['date'] ?? now() // Use provided date or current date
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
        'date' => 'nullable|date', // Validate the date
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
            'date' => $validatedData['date'] ?? now() // Use provided date or current date
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
            'date' => 'nullable|date', // Validate the date
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
                'date' => $validatedData['date'] ?? now() // Use provided date or current date
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
                'amount' => 'required|numeric|min:0.1',
                'currency' => 'required|in:USD,LBP',
                'description' => 'nullable|string',
                'income_type' => 'required|string|exists:income_types,name', // Validate income type name
                'date' => 'required|date', // Validate the provided date from frontend
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
            'subtype' => $incomeType->name, // Use the retrieved income type ID
            'date' => $validatedData['date'], // Use the provided date from frontend
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
            'date' => 'required|date', // Validate the provided date from frontend
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
        'subtype' => $expenseType->name, // Use the retrieved expense type ID
        'date' => $validatedData['date'],
    ]);

    Log::info('Expense transaction recorded.', ['transaction' => $transaction]);

    return response()->json([
        'message' => 'Expense added successfully',
        'transaction' => $transaction,
    ], 201);
}

    
}
