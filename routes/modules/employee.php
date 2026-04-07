<?php

use App\Http\Controllers\HRMS\EmployeeAttendanceController;
use App\Http\Controllers\HRMS\EmployeeDashboardController;
use App\Http\Controllers\HRMS\EmployeeDownloadsController;
use App\Http\Controllers\HRMS\EmployeeLeavesController;
use App\Http\Controllers\HRMS\EmployeePasswordController;
use App\Http\Controllers\HRMS\EmployeePoliciesGuidelinesController;
use App\Http\Controllers\HRMS\EmployeeProfileController;
use App\Http\Controllers\HRMS\EmployeeReimbursementsController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('employee')
    ->middleware(['auth', 'role:' . User::ROLE_EMPLOYEE, 'cms.activity'])
    ->name('employee.')
    ->group(function () {
        Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [EmployeeProfileController::class, 'show'])->name('profile.show');
        Route::post('/profile/photo', [EmployeeProfileController::class, 'updatePhoto'])->name('profile.photo.update');

        Route::get('/password', [EmployeePasswordController::class, 'edit'])->name('password.edit');
        Route::put('/password', [EmployeePasswordController::class, 'update'])->name('password.update');

        Route::get('/leaves', [EmployeeLeavesController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/apply', [EmployeeLeavesController::class, 'create'])->name('leaves.create');
        Route::post('/leaves', [EmployeeLeavesController::class, 'store'])->name('leaves.store');

        Route::get('/reimbursements', [EmployeeReimbursementsController::class, 'index'])->name('reimbursements.index');
        Route::get('/reimbursements/apply', [EmployeeReimbursementsController::class, 'create'])->name('reimbursements.create');
        Route::post('/reimbursements', [EmployeeReimbursementsController::class, 'store'])->name('reimbursements.store');
        Route::get('/reimbursements/{reimbursementRequest}', [EmployeeReimbursementsController::class, 'show'])->name('reimbursements.show');

        Route::get('/attendance', [EmployeeAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/punch-in', [EmployeeAttendanceController::class, 'punchIn'])->name('attendance.punch_in');
        Route::post('/attendance/punch-out', [EmployeeAttendanceController::class, 'punchOut'])->name('attendance.punch_out');

        Route::get('/downloads', [EmployeeDownloadsController::class, 'index'])->name('downloads.index');
        Route::get('/policies-guidelines', [EmployeePoliciesGuidelinesController::class, 'index'])->name('policies_guidelines.index');
        Route::get('/documents/{document}/download', [EmployeeDownloadsController::class, 'downloadDocument'])->name('documents.download');
        Route::get('/uploaded-documents/{employeeUploadedDocument}/download', [EmployeeDownloadsController::class, 'downloadUploadedDocument'])->name('uploaded_documents.download');
        Route::get('/salary-slips/{salarySlip}/download', [EmployeeDownloadsController::class, 'downloadSlip'])->name('salary_slips.download');
    });
