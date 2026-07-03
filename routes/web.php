<?php

use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashAccountController;
use App\Http\Controllers\ChartAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseRecordController;
use App\Http\Controllers\IncomeRecordController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ThirdPartyController;
use App\Http\Controllers\WithholdingRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/admin')->name('dashboard');
    Route::resource('third-parties', ThirdPartyController::class);
    Route::resource('chart-accounts', ChartAccountController::class);
    Route::resource('cash-accounts', CashAccountController::class);
    Route::resource('withholding-rules', WithholdingRuleController::class);
    Route::resource('income-records', IncomeRecordController::class);
    Route::resource('expense-records', ExpenseRecordController::class);
    Route::resource('payments', PaymentController::class);

    Route::get('accounting-reports/ledger', [AccountingReportController::class, 'ledger'])->name('accounting-reports.ledger');
    Route::get('accounting-reports/third-party-movements', [AccountingReportController::class, 'thirdPartyMovements'])->name('accounting-reports.third-party-movements');
    Route::get('accounting-reports/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('accounting-reports.trial-balance');
});
