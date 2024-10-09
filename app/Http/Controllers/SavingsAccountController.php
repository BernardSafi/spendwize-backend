<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

public function getSaving(Request $request)
{
    $user = Auth::user();

    if ($user) {
        // Retrieve the wallet balance
        $saving = $user->savingsAccounts; 
        
        if ($saving) {
            $usd_balance = $saving->usd_balance ?? 0.0; 
            $lbp_balance = $saving->lbp_balance ?? 0.0; 

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
