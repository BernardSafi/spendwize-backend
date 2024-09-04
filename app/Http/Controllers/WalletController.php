<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function addIncome(Request $request)
{
    $user = Auth::user();
    $wallet = $user->wallets;

    $amount = $request->input('amount');
    $currency = $request->input('currency'); // 'USD' or 'LBP'

    if ($currency == 'USD') {
        $wallet->usd_balance += $amount;
    } else {
        $wallet->lbp_balance += $amount;
    }

    $wallet->save();

    // Log the transaction
    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'income',
        'amount' => $amount,
        'currency' => $currency,
        'description' => $request->input('description'),
    ]);

    return response()->json(['message' => 'Income added successfully']);
}

public function getWallet(Request $request)
{
    $user = Auth::user();

    if ($user) {
        // Retrieve the wallet balance using the correct relationship method
        $wallet = $user->wallet; 
        
        if ($wallet) {
            $usd_balance = $wallet->usd_balance ?? 0.0; // Use null coalescing operator
            $lbp_balance = $wallet->lbp_balance ?? 0.0; // Use null coalescing operator

            return response()->json([
                'usd_balance' => $usd_balance,
                'lbp_balance' => $lbp_balance,
            ]);
        } else {
            return response()->json(['error' => 'Wallet not found'], 404);
        }
    } else {
        return response()->json(['error' => 'User not authenticated'], 401);
    }
}

}
