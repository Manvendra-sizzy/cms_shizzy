<?php

use App\Http\Controllers\Assets\AssetAssignmentsController;
use App\Http\Controllers\Assets\AssetCategoriesController;
use App\Http\Controllers\Assets\AssetsController;
use Illuminate\Support\Facades\Route;

Route::prefix('assets')
    ->middleware(['auth', 'module:assets', 'cms.activity'])
    ->name('assets.')
    ->group(function () {
        Route::get('/categories', [AssetCategoriesController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [AssetCategoriesController::class, 'create'])->name('categories.create');
        Route::post('/categories', [AssetCategoriesController::class, 'store'])->name('categories.store');
        Route::get('/categories/{assetCategory}/edit', [AssetCategoriesController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{assetCategory}', [AssetCategoriesController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{assetCategory}', [AssetCategoriesController::class, 'destroy'])->name('categories.destroy');

        Route::get('/', [AssetsController::class, 'index'])->name('index');
        Route::get('/create', [AssetsController::class, 'create'])->name('create');
        Route::post('/', [AssetsController::class, 'store'])->name('store');
        Route::get('/{asset}', [AssetsController::class, 'show'])->name('show');
        Route::get('/{asset}/edit', [AssetsController::class, 'edit'])->name('edit');
        Route::put('/{asset}', [AssetsController::class, 'update'])->name('update');

        Route::post('/{asset}/assign', [AssetAssignmentsController::class, 'assign'])->name('assign');
        Route::post('/{asset}/transfer', [AssetAssignmentsController::class, 'transfer'])->name('transfer');
        Route::post('/{asset}/return', [AssetAssignmentsController::class, 'returnAsset'])->name('return');
    });
