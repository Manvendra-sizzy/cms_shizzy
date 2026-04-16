<?php

use App\Http\Controllers\Systems\InfrastructureResourcesController;
use App\Http\Controllers\Systems\SupportScopesController;
use App\Http\Controllers\Systems\SystemDevelopmentLogsController;
use App\Http\Controllers\Systems\SystemDocumentationController;
use App\Http\Controllers\Systems\SystemsController;
use App\Http\Controllers\Systems\SystemSupportExtensionsController;
use Illuminate\Support\Facades\Route;

Route::prefix('systems')
    ->middleware(['auth', 'module:systems', 'cms.activity'])
    ->name('systems.')
    ->group(function () {
        Route::get('/', [SystemsController::class, 'index'])->name('index');
        Route::get('/create', [SystemsController::class, 'create'])->name('create');
        Route::post('/', [SystemsController::class, 'store'])->name('store');
        Route::middleware('system.access')->group(function () {
            Route::get('/{system}', [SystemsController::class, 'show'])->name('show');
            Route::get('/{system}/edit', [SystemsController::class, 'edit'])->name('edit');
            Route::put('/{system}', [SystemsController::class, 'update'])->name('update');

            Route::post('/{system}/support-extensions', [SystemSupportExtensionsController::class, 'store'])->name('support_extensions.store');
            Route::put('/{system}/documentation', [SystemDocumentationController::class, 'update'])->name('documentation.update');

            Route::post('/{system}/development-logs', [SystemDevelopmentLogsController::class, 'store'])->name('development_logs.store');
            Route::put('/{system}/development-logs/{log}', [SystemDevelopmentLogsController::class, 'update'])->name('development_logs.update');
            Route::delete('/{system}/development-logs/{log}', [SystemDevelopmentLogsController::class, 'destroy'])->name('development_logs.destroy');
        });

        Route::get('/infrastructure/resources', [InfrastructureResourcesController::class, 'index'])->name('infrastructure.index');
        Route::get('/infrastructure/resources/create', [InfrastructureResourcesController::class, 'create'])->name('infrastructure.create');
        Route::post('/infrastructure/resources', [InfrastructureResourcesController::class, 'store'])->name('infrastructure.store');
        Route::get('/infrastructure/resources/{resource}/edit', [InfrastructureResourcesController::class, 'edit'])->name('infrastructure.edit');
        Route::put('/infrastructure/resources/{resource}', [InfrastructureResourcesController::class, 'update'])->name('infrastructure.update');

        Route::get('/support-scopes', [SupportScopesController::class, 'index'])->name('support_scopes.index');
        Route::get('/support-scopes/create', [SupportScopesController::class, 'create'])->name('support_scopes.create');
        Route::post('/support-scopes', [SupportScopesController::class, 'store'])->name('support_scopes.store');
        Route::get('/support-scopes/{scope}/edit', [SupportScopesController::class, 'edit'])->name('support_scopes.edit');
        Route::put('/support-scopes/{scope}', [SupportScopesController::class, 'update'])->name('support_scopes.update');
    });
