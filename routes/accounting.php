<?php

use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TaxRateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('accounting')->group(function () {
    Route::middleware(['can:chart-of-accounts.read'])->group(function () {
        Route::livewire('chart-of-accounts', 'pages::accounting.chart-of-accounts-index')->name('chart-of-accounts.index');
    });

    Route::middleware(['can:ledgers.read'])->group(function () {
        Route::livewire('ledger', 'pages::accounting.ledger-index')->name('ledger.index');
    });

    Route::middleware(['can:financial-statements.read'])->group(function () {
        Route::livewire('statements', 'pages::accounting.statements-index')->name('statements.index');
    });

    Route::middleware(['can:expenses.read'])->name('expenses.')->group(function () {
        Route::livewire('expenses', 'pages::accounting.expenses-index')->name('index');
        Route::livewire('expenses/create', 'pages::accounting.expenses-create')->middleware('can:expenses.create')->name('create');
        Route::livewire('expenses/{expense}/edit', 'pages::accounting.expenses-edit')->middleware('can:expenses.update')->name('edit');

        Route::post('expenses', [ExpenseController::class, 'store'])->middleware('can:expenses.create')->name('store');
        Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->middleware('can:expenses.update')->name('update');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->middleware('can:expenses.delete')->name('destroy');
    });

    Route::middleware(['can:journal-entries.read'])->name('journal-entries.')->group(function () {
        Route::livewire('journal-entries', 'pages::accounting.journal-entries-index')->name('index');
        Route::livewire('journal-entries/create', 'pages::accounting.journal-entries-create')->middleware('can:journal-entries.create')->name('create');
        Route::livewire('journal-entries/{journalEntry}/edit', 'pages::accounting.journal-entries-edit')->middleware('can:journal-entries.update')->name('edit');

        Route::post('journal-entries', [JournalEntryController::class, 'store'])->middleware('can:journal-entries.create')->name('store');
        Route::put('journal-entries/{journalEntry}', [JournalEntryController::class, 'update'])->middleware('can:journal-entries.update')->name('update');
        Route::delete('journal-entries/{journalEntry}', [JournalEntryController::class, 'destroy'])->middleware('can:journal-entries.delete')->name('destroy');
    });

    Route::middleware(['can:payments.read'])->name('payments.')->group(function () {
        Route::livewire('payments', 'pages::accounting.payments-index')->name('index');
        Route::livewire('payments/create', 'pages::accounting.payments-create')->middleware('can:payments.create')->name('create');
        Route::livewire('payments/{payment}/edit', 'pages::accounting.payments-edit')->middleware('can:payments.update')->name('edit');

        Route::post('payments', [PaymentController::class, 'store'])->middleware('can:payments.create')->name('store');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->middleware('can:payments.update')->name('update');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->middleware('can:payments.delete')->name('destroy');
    });

    Route::middleware(['can:tax-rates.read'])->name('tax-rates.')->group(function () {
        Route::livewire('tax-rates', 'pages::accounting.tax-rates-index')->name('index');
        Route::livewire('tax-rates/create', 'pages::accounting.tax-rates-create')->middleware('can:tax-rates.create')->name('create');
        Route::livewire('tax-rates/{taxRate}/edit', 'pages::accounting.tax-rates-edit')->middleware('can:tax-rates.update')->name('edit');

        Route::post('tax-rates', [TaxRateController::class, 'store'])->middleware('can:tax-rates.create')->name('store');
        Route::put('tax-rates/{taxRate}', [TaxRateController::class, 'update'])->middleware('can:tax-rates.update')->name('update');
        Route::delete('tax-rates/{taxRate}', [TaxRateController::class, 'destroy'])->middleware('can:tax-rates.delete')->name('destroy');
    });

    Route::middleware(['can:financial-reports.read'])->name('financial-reports.')->group(function () {
        Route::livewire('financial-reports', 'pages::accounting.financial-reports-index')->name('index');
        Route::livewire('financial-reports/create', 'pages::accounting.financial-reports-create')->middleware('can:financial-reports.create')->name('create');
        Route::livewire('financial-reports/{financialReport}/edit', 'pages::accounting.financial-reports-edit')->middleware('can:financial-reports.update')->name('edit');

        Route::post('financial-reports', [FinancialReportController::class, 'store'])->middleware('can:financial-reports.create')->name('store');
        Route::put('financial-reports/{financialReport}', [FinancialReportController::class, 'update'])->middleware('can:financial-reports.update')->name('update');
        Route::delete('financial-reports/{financialReport}', [FinancialReportController::class, 'destroy'])->middleware('can:financial-reports.delete')->name('destroy');
    });

    Route::middleware(['can:fixed-assets.read'])->name('fixed-assets.')->group(function () {
        Route::livewire('fixed-assets', 'pages::accounting.fixed-assets-index')->name('index');
        Route::livewire('fixed-assets/create', 'pages::accounting.fixed-assets-create')->middleware('can:fixed-assets.create')->name('create');
        Route::livewire('fixed-assets/{fixedAsset}/edit', 'pages::accounting.fixed-assets-edit')->middleware('can:fixed-assets.update')->name('edit');

        Route::post('fixed-assets', [FixedAssetController::class, 'store'])->middleware('can:fixed-assets.create')->name('store');
        Route::put('fixed-assets/{fixedAsset}', [FixedAssetController::class, 'update'])->middleware('can:fixed-assets.update')->name('update');
        Route::delete('fixed-assets/{fixedAsset}', [FixedAssetController::class, 'destroy'])->middleware('can:fixed-assets.delete')->name('destroy');
    });

    Route::middleware(['can:budgeting.read'])->name('budgets.')->group(function () {
        Route::livewire('budgets', 'pages::accounting.budgets-index')->name('index');
        Route::livewire('budgets/create', 'pages::accounting.budgets-create')->middleware('can:budgeting.create')->name('create');
        Route::livewire('budgets/{budget}/edit', 'pages::accounting.budgets-edit')->middleware('can:budgeting.update')->name('edit');

        Route::post('budgets', [BudgetController::class, 'store'])->middleware('can:budgeting.create')->name('store');
        Route::put('budgets/{budget}', [BudgetController::class, 'update'])->middleware('can:budgeting.update')->name('update');
        Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->middleware('can:budgeting.delete')->name('destroy');
    });

    Route::middleware(['can:bank-reconciliation.read'])->name('bank-reconciliations.')->group(function () {
        Route::livewire('bank-reconciliations', 'pages::accounting.bank-reconciliations-index')->name('index');
        Route::livewire('bank-reconciliations/create', 'pages::accounting.bank-reconciliations-create')->middleware('can:bank-reconciliation.create')->name('create');
        Route::livewire('bank-reconciliations/{bankReconciliation}/edit', 'pages::accounting.bank-reconciliations-edit')->middleware('can:bank-reconciliation.update')->name('edit');

        Route::post('bank-reconciliations', [BankReconciliationController::class, 'store'])->middleware('can:bank-reconciliation.create')->name('store');
        Route::put('bank-reconciliations/{bankReconciliation}', [BankReconciliationController::class, 'update'])->middleware('can:bank-reconciliation.update')->name('update');
        Route::delete('bank-reconciliations/{bankReconciliation}', [BankReconciliationController::class, 'destroy'])->middleware('can:bank-reconciliation.delete')->name('destroy');
    });
});
