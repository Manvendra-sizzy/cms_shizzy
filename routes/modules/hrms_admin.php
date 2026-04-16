<?php

use App\Http\Controllers\HRMS\HRAttendanceAdjustmentsController;
use App\Http\Controllers\HRMS\HRAttendanceController;
use App\Http\Controllers\HRMS\HRDashboardController;
use App\Http\Controllers\HRMS\HRDocumentsController;
use App\Http\Controllers\HRMS\HREmployeeDetailsUpdatesController;
use App\Http\Controllers\HRMS\HREmployeeEmergencyContactsController;
use App\Http\Controllers\HRMS\HREmployeesController;
use App\Http\Controllers\HRMS\HREmployeeUploadedDocumentsController;
use App\Http\Controllers\HRMS\HRHolidayCalendarController;
use App\Http\Controllers\HRMS\HRLeaveApprovalsController;
use App\Http\Controllers\HRMS\HRReimbursementApprovalsController;
use App\Http\Controllers\HRMS\HREmploymentAgreementController;
use App\Http\Controllers\HRMS\HROnboardingsController;
use App\Http\Controllers\HRMS\HRLeavePoliciesController;
use App\Http\Controllers\HRMS\HRPayrollController;
use App\Http\Controllers\HRMS\HRPoliciesGuidelinesController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/hrms')
    ->middleware(['auth', 'role:' . User::ROLE_ADMIN, 'module:hrms', 'cms.activity'])
    ->name('admin.hrms.')
    ->group(function () {
        Route::get('/dashboard', [HRDashboardController::class, 'index'])->name('dashboard');
        Route::get('/onboardings', [HROnboardingsController::class, 'index'])->name('onboardings.index');
        Route::get('/onboardings/create', [HROnboardingsController::class, 'create'])->name('onboardings.create');
        Route::post('/onboardings', [HROnboardingsController::class, 'store'])->name('onboardings.store');
        Route::get('/onboardings/{onboarding}', [HROnboardingsController::class, 'show'])->name('onboardings.show');
        Route::post('/onboardings/{onboarding}/resend-link', [HROnboardingsController::class, 'resendLink'])->name('onboardings.resend_link');
        Route::post('/onboardings/{onboarding}/approve', [HROnboardingsController::class, 'approve'])->name('onboardings.approve');
        Route::post('/onboardings/{onboarding}/reject', [HROnboardingsController::class, 'reject'])->name('onboardings.reject');
        Route::post('/onboardings/{onboarding}/send-agreement', [HROnboardingsController::class, 'sendAgreement'])->name('onboardings.send_agreement');
        Route::post('/onboardings/{onboarding}/send-inbuilt-contract', [HROnboardingsController::class, 'sendInbuiltContract'])->name('onboardings.send_inbuilt_contract');
        Route::get('/onboardings/{onboarding}/inbuilt-signed-contract', [HROnboardingsController::class, 'downloadInbuiltSignedContract'])->name('onboardings.inbuilt_signed_contract');
        Route::post('/onboardings/{onboarding}/agreement-details', [HROnboardingsController::class, 'saveAgreementDetails'])->name('onboardings.agreement_details');
        Route::post('/onboardings/{onboarding}/sync-zoho', [HROnboardingsController::class, 'syncZohoStatus'])->name('onboardings.sync_zoho');
        Route::get('/onboardings/{onboarding}/signed-agreement', [HROnboardingsController::class, 'downloadSignedAgreement'])->name('onboardings.signed_agreement');

        Route::get('/employment-agreement', [HREmploymentAgreementController::class, 'edit'])->name('employment_agreement.edit');
        Route::put('/employment-agreement', [HREmploymentAgreementController::class, 'update'])->name('employment_agreement.update');

        Route::get('/employees', [HREmployeesController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [HREmployeesController::class, 'create'])->name('employees.create');
        Route::post('/employees', [HREmployeesController::class, 'store'])->name('employees.store');
        // Static paths must be registered before /employees/{employeeProfile} or "attendance-locks" / "uploaded-documents" are captured as route-model keys.
        Route::get('/employees/attendance-locks', [HREmployeesController::class, 'lockedAttendanceUsers'])->name('employees.attendance_locks.index');
        Route::get('/employees/uploaded-documents/{employeeUploadedDocument}/download', [HREmployeeUploadedDocumentsController::class, 'download'])->name('employees.uploaded_documents.download');
        Route::get('/employees/{employeeProfile}', [HREmployeesController::class, 'show'])->name('employees.show');
        Route::post('/employees/{employeeProfile}/reset-password', [HREmployeesController::class, 'resetPassword'])->name('employees.password.reset');
        Route::post('/employees/{employeeProfile}/convert-to-permanent', [HREmployeesController::class, 'convertToPermanent'])->name('employees.convert_to_permanent');
        // Employee edit is replaced by per-field updates + logs.
        Route::get('/employees/{employeeProfile}/update-details', [HREmployeeDetailsUpdatesController::class, 'index'])->name('employees.update_details.index');
        Route::post('/employees/{employeeProfile}/update-details', [HREmployeeDetailsUpdatesController::class, 'update'])->name('employees.update_details.update');
        Route::get('/employees/{employeeProfile}/status', [HREmployeesController::class, 'showStatus'])->name('employees.status.show');
        Route::put('/employees/{employeeProfile}/status', [HREmployeesController::class, 'updateStatus'])->name('employees.status.update');
        Route::get('/employees/{employeeProfile}/salary', [HREmployeesController::class, 'showSalary'])->name('employees.salary.show');
        Route::post('/employees/{employeeProfile}/salary', [HREmployeesController::class, 'amendSalary'])->name('employees.salary.amend');
        Route::get('/employees/{employeeProfile}/salary-slips', [HREmployeesController::class, 'salarySlipsIndex'])->name('employees.salary_slips.index');
        Route::post('/employees/{employeeProfile}/attendance-locks/unlock', [HREmployeesController::class, 'unlockAttendanceLock'])->name('employees.attendance_locks.unlock');
        Route::get('/employees/{employeeProfile}/emergency-contacts', [HREmployeeEmergencyContactsController::class, 'edit'])->name('employees.emergency_contacts.edit');
        Route::put('/employees/{employeeProfile}/emergency-contacts', [HREmployeeEmergencyContactsController::class, 'update'])->name('employees.emergency_contacts.update');
        Route::post('/employees/{employeeProfile}/uploaded-documents', [HREmployeeUploadedDocumentsController::class, 'store'])->name('employees.uploaded_documents.store');

        Route::get('/documents', [HRDocumentsController::class, 'index'])->name('documents.index');
        Route::get('/documents/create', [HRDocumentsController::class, 'create'])->name('documents.create');
        Route::post('/documents', [HRDocumentsController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}/download', [HRDocumentsController::class, 'download'])->name('documents.download');

        Route::get('/leave-policies', [HRLeavePoliciesController::class, 'index'])->name('leave_policies.index');
        Route::get('/leave-policies/create', [HRLeavePoliciesController::class, 'create'])->name('leave_policies.create');
        Route::post('/leave-policies', [HRLeavePoliciesController::class, 'store'])->name('leave_policies.store');
        Route::get('/policies-guidelines', [HRPoliciesGuidelinesController::class, 'index'])->name('policies_guidelines.index');
        Route::post('/policies-guidelines', [HRPoliciesGuidelinesController::class, 'store'])->name('policies_guidelines.store');

        Route::get('/leave-approvals', [HRLeaveApprovalsController::class, 'index'])->name('leave_approvals.index');
        Route::post('/leave-approvals/{leaveRequest}/approve', [HRLeaveApprovalsController::class, 'approve'])->name('leave_approvals.approve');
        Route::post('/leave-approvals/{leaveRequest}/reject', [HRLeaveApprovalsController::class, 'reject'])->name('leave_approvals.reject');

        Route::get('/reimbursement-approvals', [HRReimbursementApprovalsController::class, 'index'])->name('reimbursement_approvals.index');
        Route::get('/reimbursement-approvals/{reimbursementRequest}', [HRReimbursementApprovalsController::class, 'show'])->name('reimbursement_approvals.show');
        Route::post('/reimbursement-approvals/{reimbursementRequest}/pay', [HRReimbursementApprovalsController::class, 'payPartial'])->name('reimbursement_approvals.pay');
        Route::post('/reimbursement-approvals/{reimbursementRequest}/approve', [HRReimbursementApprovalsController::class, 'approve'])->name('reimbursement_approvals.approve');
        Route::post('/reimbursement-approvals/{reimbursementRequest}/reject', [HRReimbursementApprovalsController::class, 'reject'])->name('reimbursement_approvals.reject');

        Route::get('/calendar', [HRHolidayCalendarController::class, 'index'])->name('calendar.index');
        Route::post('/calendar', [HRHolidayCalendarController::class, 'store'])->name('calendar.store');
        Route::delete('/calendar/{holiday}', [HRHolidayCalendarController::class, 'destroy'])->name('calendar.destroy');

        Route::get('/payroll', [HRPayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/create', [HRPayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [HRPayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{payrollRun}', [HRPayrollController::class, 'show'])->name('payroll.show');
        Route::post('/payroll/{payrollRun}/slips', [HRPayrollController::class, 'generateSlips'])->name('payroll.slips.generate');
        Route::get('/payroll/slips/{salarySlip}/download', [HRPayrollController::class, 'downloadSlip'])->name('payroll.slips.download');

        Route::get('/attendance-adjustments', [HRAttendanceAdjustmentsController::class, 'index'])->name('attendance_adjustments.index');
        Route::post('/attendance-adjustments/bulk', [HRAttendanceAdjustmentsController::class, 'bulkStore'])->name('attendance_adjustments.bulk_store');
        Route::post('/attendance-adjustments', [HRAttendanceAdjustmentsController::class, 'store'])->name('attendance_adjustments.store');
        Route::get('/attendance', [HRAttendanceController::class, 'index'])->name('attendance.index');
    });
