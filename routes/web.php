<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicFilesController;
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/files/{path}', [PublicFilesController::class, 'show'])
    ->where('path', '.*')
    ->name('files.public');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/two-factor/setup', [AuthController::class, 'showTwoFactorSetup'])->name('twofactor.setup.show');
    Route::post('/two-factor/setup', [AuthController::class, 'completeTwoFactorSetup'])->name('twofactor.setup.complete');
    Route::get('/two-factor', [AuthController::class, 'showTwoFactorChallenge'])->name('twofactor.challenge.show');
    Route::post('/two-factor', [AuthController::class, 'verifyTwoFactorChallenge'])->name('twofactor.challenge.verify');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth', 'cms.activity'])->name('logout');
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
Route::middleware(['auth', 'cms.activity'])->group(function () {
    Route::get('/security/two-factor', [AuthController::class, 'showTwoFactorSettings'])->name('security.twofactor.show');
    Route::post('/security/two-factor/enable', [AuthController::class, 'enableTwoFactor'])->name('security.twofactor.enable');
    Route::post('/security/two-factor/disable', [AuthController::class, 'disableTwoFactor'])->name('security.twofactor.disable');
});

Route::get('/zoho/callback', function (Request $request) {
    return redirect()->route('admin.tools.zoho.index', [
        'code' => $request->query('code'),
        'location' => $request->query('location'),
        'accounts-server' => $request->query('accounts-server'),
    ]);
});