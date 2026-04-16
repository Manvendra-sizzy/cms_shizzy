<?php

use App\Http\Controllers\Projects\ProjectCategoriesController;
use App\Http\Controllers\Projects\ProjectClientsController;
use App\Http\Controllers\Projects\ProjectFinancesController;
use App\Http\Controllers\Projects\ProjectRevenueController;
use App\Http\Controllers\Projects\ProjectsController;
use App\Http\Controllers\Projects\ProjectStatusController;
use App\Http\Controllers\Projects\ProjectTeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('projects')
    ->middleware(['auth', 'module:projects', 'cms.activity'])
    ->name('projects.')
    ->group(function () {
        Route::get('/', [ProjectsController::class, 'index'])->name('index');

        Route::get('/clients', [ProjectClientsController::class, 'index'])->name('clients.index');

        Route::get('/categories', [ProjectCategoriesController::class, 'index'])->name('categories.index');
        Route::post('/categories', [ProjectCategoriesController::class, 'store'])->name('categories.store');
        Route::put('/categories/{projectCategory}', [ProjectCategoriesController::class, 'update'])->name('categories.update');

        Route::get('/create', [ProjectsController::class, 'create'])->name('create');
        Route::post('/', [ProjectsController::class, 'store'])->name('store');
        Route::get('/finance-radar', [ProjectFinancesController::class, 'radar'])->name('finances.radar');
        Route::get('/{project}', [ProjectsController::class, 'show'])->name('show');
        Route::get('/{project}/edit', [ProjectsController::class, 'edit'])->name('edit');
        Route::put('/{project}', [ProjectsController::class, 'update'])->name('update');
        Route::post('/{project}/status', [ProjectStatusController::class, 'store'])->name('status.store');
        Route::post('/{project}/team', [ProjectTeamController::class, 'store'])->name('team.store');
        Route::delete('/{project}/team/{projectTeamMember}', [ProjectTeamController::class, 'destroy'])->name('team.destroy');

        Route::get('/{project}/finances', [ProjectFinancesController::class, 'show'])->name('finances.show');
        Route::get('/{project}/zoho-invoices/{zohoInvoice}/open', [ProjectFinancesController::class, 'openZohoInvoice'])->name('finances.zoho_invoices.open');

        Route::post('/{project}/revenue/streams', [ProjectRevenueController::class, 'storeStream'])->name('revenue.streams.store');
        Route::get('/{project}/revenue/streams/{stream}/edit', [ProjectRevenueController::class, 'editStream'])->name('revenue.streams.edit');
        Route::put('/{project}/revenue/streams/{stream}', [ProjectRevenueController::class, 'updateStream'])->name('revenue.streams.update');
        Route::post('/{project}/revenue/streams/{stream}/close', [ProjectRevenueController::class, 'closeStream'])->name('revenue.streams.close');
        Route::post('/{project}/revenue/streams/{stream}/invoices', [ProjectRevenueController::class, 'storeInvoice'])->name('revenue.invoices.store');
        Route::get('/{project}/revenue/streams/{stream}/invoices/{invoice}/edit', [ProjectRevenueController::class, 'editInvoice'])->name('revenue.invoices.edit');
        Route::put('/{project}/revenue/streams/{stream}/invoices/{invoice}', [ProjectRevenueController::class, 'updateInvoice'])->name('revenue.invoices.update');
        Route::post('/{project}/revenue/streams/{stream}/payments', [ProjectRevenueController::class, 'storePayment'])->name('revenue.payments.store');
        Route::post('/{project}/revenue/reimbursements', [ProjectRevenueController::class, 'storeReimbursement'])->name('revenue.reimbursements.store');
        Route::get('/{project}/revenue/reimbursements/{reimbursement}/edit', [ProjectRevenueController::class, 'editReimbursement'])->name('revenue.reimbursements.edit');
        Route::put('/{project}/revenue/reimbursements/{reimbursement}', [ProjectRevenueController::class, 'updateReimbursement'])->name('revenue.reimbursements.update');
    });
