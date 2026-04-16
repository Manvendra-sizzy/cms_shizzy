<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HRMS\PublicOnboardingController;
use App\Http\Controllers\HRMS\PublicContractSigningController;
use App\Http\Controllers\HRMS\ZohoSignWebhookController;
use App\Http\Controllers\PublicFilesController;
use App\Modules\HRMS\Documents\Models\HRDocument;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
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

Route::middleware(['throttle:20,1'])->group(function () {
    Route::get('/onboarding/{token}', [PublicOnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/{token}', [PublicOnboardingController::class, 'submit'])->name('onboarding.submit');
    Route::post('/onboarding/{token}/sign-contract', [PublicOnboardingController::class, 'submitContract'])->name('onboarding.sign-contract');
    Route::get('/onboarding-contract/{token}', [PublicContractSigningController::class, 'show'])->name('onboarding.contract.show');
    Route::post('/onboarding-contract/{token}', [PublicContractSigningController::class, 'submit'])->name('onboarding.contract.submit');
});
Route::post('/webhooks/zoho-sign', ZohoSignWebhookController::class)->name('webhooks.zoho_sign');

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

    Route::get('/admin/hrms/documents/template-preview/{document?}', function (?HRDocument $document = null) {
        if (! $document) {
            $employee = EmployeeProfile::query()
                ->with(['user', 'orgDepartment', 'orgDesignation'])
                ->orderBy('id')
                ->first();

            $document = new HRDocument([
                'type' => HRDocument::TYPE_APPRECIATION_LETTER,
                'title' => 'Appreciation Letter',
                'body' => '<p>We are pleased to recognize your consistent ownership, reliability, and strong contribution to the team.</p><p>Your efforts have made a meaningful impact on delivery quality and collaboration.</p><p>Thank you for your continued dedication and commitment.</p>',
                'issued_at' => now(),
                'document_hash' => strtoupper(hash('sha256', 'preview-hr-document-template')),
            ]);

            if ($employee) {
                $document->setRelation('employeeProfile', $employee);
            }
        } else {
            $document->loadMissing(['employeeProfile.user', 'employeeProfile.orgDepartment', 'employeeProfile.orgDesignation']);
        }

        return view('hrms.shared.document', ['document' => $document]);
    })->name('admin.hrms.documents.template_preview');
});

Route::get('/zoho/callback', function (Request $request) {
    return redirect()->route('admin.tools.zoho.index', [
        'code' => $request->query('code'),
        'location' => $request->query('location'),
        'accounts-server' => $request->query('accounts-server'),
    ]);
});