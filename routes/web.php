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

Route::get('accounting-reports/journal', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->journal($request);
})->name('accounting-reports.journal');

Route::get('accounting-reports/financial-statements', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->financialStatements($request);
})->name('accounting-reports.financial-statements');

Route::get('accounting-reports/accounts-receivable', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->accountsReceivable();
})->name('accounting-reports.accounts-receivable');

Route::get('accounting-reports/general-ledger', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->generalLedger($request);
})->name('accounting-reports.general-ledger');

Route::get('accounting-reports/bank-reconciliation', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->bankReconciliation($request);
})->name('accounting-reports.bank-reconciliation');
