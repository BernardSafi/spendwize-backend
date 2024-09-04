<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SavingsAccountController extends Controller
{
    public function transferToSavings(Request $request)
{
    $user = Auth::user();
    $wallet = $user->wallets;
    $savings = $user->savingsAccounts;

    $amount = $request->input('amount');
    $currency = $request->input('currency'); // 'USD' or 'LBP'

    if ($currency == 'USD') {
        $wallet->usd_balance -= $amount;
        $savings->usd_balance += $amount;
    } else {
        $wallet->lbp_balance -= $amount;
        $savings->lbp_balance += $amount;
    }

    $wallet->save();
    $savings->save();

    // Log the transaction
    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'savings_account_id' => $savings->id,
        'type' => 'transfer',
        'amount' => $amount,
        'currency' => $currency,
        'description' => $request->input('description'),
    ]);

    return response()->json(['message' => 'Transfer to savings successful']);
}

public function getSaving()
{
    $user = Auth::user(); // Get the authenticated user
    return response()->json([
        'usd_balance' => $user->savingsAccount->usd_balance,
        'lbp_balance' => $user->savingsAccount->lbp_balance
    ]);
}

}
