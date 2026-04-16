<?php

use App\Http\Controllers\CMS\AdminDashboardController;
use App\Http\Controllers\CMS\AdminUsersController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CMS\OrganizationStructureController;
use App\Http\Controllers\CMS\ToolsLogsController;
use App\Http\Controllers\CMS\ZohoClientsController;
use App\Http\Controllers\CMS\ZohoIntegrationController;
use App\Http\Controllers\CMS\ZohoInvoicesController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth', 'role:' . User::ROLE_ADMIN, 'cms.activity'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUsersController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUsersController::class, 'store'])->name('users.store');
        Route::post('/users/{user}/reset-2fa', [AuthController::class, 'adminResetUserTwoFactor'])->name('users.twofactor.reset');

        Route::get('/organization-structure', [OrganizationStructureController::class, 'index'])->name('organization.index');

        Route::get('/tools/logs', [ToolsLogsController::class, 'index'])->name('tools.logs.index');
        Route::get('/tools/zoho', [ZohoIntegrationController::class, 'index'])->name('tools.zoho.index');
        Route::post('/tools/zoho/token', [ZohoIntegrationController::class, 'generateRefreshToken'])->name('tools.zoho.token');
        Route::get('/zoho-clients', [ZohoClientsController::class, 'index'])->name('zoho_clients.index');
        Route::post('/zoho-clients/sync', [ZohoClientsController::class, 'sync'])->name('zoho_clients.sync');
        Route::get('/zoho-invoices', [ZohoInvoicesController::class, 'index'])->name('zoho_invoices.index');
        Route::post('/zoho-invoices/sync', [ZohoInvoicesController::class, 'sync'])->name('zoho_invoices.sync');
        Route::get('/zoho-invoices/{zohoInvoice}/download', [ZohoInvoicesController::class, 'download'])->name('zoho_invoices.download');

        Route::get('/organization-structure/departments', [OrganizationStructureController::class, 'departmentsIndex'])->name('organization.departments.index');
        Route::get('/organization-structure/departments/{department}/edit', [OrganizationStructureController::class, 'departmentsEdit'])->name('organization.departments.edit');
        Route::post('/organization-structure/departments', [OrganizationStructureController::class, 'storeDepartment'])->name('organization.departments.store');
        Route::put('/organization-structure/departments/{department}', [OrganizationStructureController::class, 'updateDepartment'])->name('organization.departments.update');
        Route::delete('/organization-structure/departments/{department}', [OrganizationStructureController::class, 'destroyDepartment'])->name('organization.departments.destroy');

        Route::get('/organization-structure/teams', [OrganizationStructureController::class, 'teamsIndex'])->name('organization.teams.index');
        Route::get('/organization-structure/teams/{team}/edit', [OrganizationStructureController::class, 'teamsEdit'])->name('organization.teams.edit');
        Route::post('/organization-structure/teams', [OrganizationStructureController::class, 'storeTeam'])->name('organization.teams.store');
        Route::put('/organization-structure/teams/{team}', [OrganizationStructureController::class, 'updateTeam'])->name('organization.teams.update');
        Route::delete('/organization-structure/teams/{team}', [OrganizationStructureController::class, 'destroyTeam'])->name('organization.teams.destroy');

        Route::get('/organization-structure/designations', [OrganizationStructureController::class, 'designationsIndex'])->name('organization.designations.index');
        Route::get('/organization-structure/designations/{designation}/edit', [OrganizationStructureController::class, 'designationsEdit'])->name('organization.designations.edit');
        Route::post('/organization-structure/designations', [OrganizationStructureController::class, 'storeDesignation'])->name('organization.designations.store');
        Route::put('/organization-structure/designations/{designation}', [OrganizationStructureController::class, 'updateDesignation'])->name('organization.designations.update');
        Route::delete('/organization-structure/designations/{designation}', [OrganizationStructureController::class, 'destroyDesignation'])->name('organization.designations.destroy');
    });
