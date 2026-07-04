<?php

use App\Http\Controllers\AccountingReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('welcome');

Route::get('accounting-reports/ledger', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->ledger($request);
})->name('accounting-reports.ledger');

Route::get('accounting-reports/third-party-movements', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->thirdPartyMovements($request);
})->name('accounting-reports.third-party-movements');

Route::get('accounting-reports/trial-balance', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->trialBalance($request);
})->name('accounting-reports.trial-balance');
