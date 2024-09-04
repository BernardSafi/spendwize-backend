<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\UserController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


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
    Route::post('/wallet/add-income', [WalletController::class, 'addIncome']);
    Route::post('/savings/transfer', [SavingsAccountController::class, 'transferToSavings']);
    Route::get('/transactions', [TransactionController::class, 'index']); // List transactions
    Route::get('/wallet', [WalletController::class, 'getWallet']);
    Route::get('/user/name', [UserController::class, 'getUserName']);
});
