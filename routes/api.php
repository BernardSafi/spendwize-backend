<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SavingsAccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\IncomeTypeController;
use App\Http\Controllers\ExpenseTypeController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('/income-types', [IncomeTypeController::class, 'index']);
Route::post('/income-types', [IncomeTypeController::class, 'store']);

Route::get('/expense-types', [ExpenseTypeController::class, 'index']);
Route::post('/expense-types', [ExpenseTypeController::class, 'store']);


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::middleware('auth:sanctum')->group(function () {
    // Saving transfers
    Route::post('/savings/transfer', [SavingsAccountController::class, 'transferToSavings']);
    
    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
    
    // Wallet and user info
    Route::get('/wallet', [WalletController::class, 'getWallet']);
    Route::get('/user/name', [UserController::class, 'getUserName']);
    Route::get('/saving', [SavingsAccountController::class, 'getSaving']);
    
    // Transfers between wallets and savings
    Route::post('/transfer/wallet-to-saving-lbp', [TransactionController::class, 'walletToSavingLbp']);
    Route::post('/transfer/saving-to-wallet-lbp', [TransactionController::class, 'savingToWalletLbp']);
    Route::post('/transfer/wallet-to-saving-usd', [TransactionController::class, 'walletToSavingUsd']);
    Route::post('/transfer/saving-to-wallet-usd', [TransactionController::class, 'savingToWalletUsd']);
    
    // Income transactions
    Route::post('/transactions/income', [TransactionController::class, 'addIncome']);
    Route::post('/transactions/expense', [TransactionController::class, 'addexpense']);
    Route::post('/transactions/exchange', [TransactionController::class, 'exchangeCurrency']);
});

