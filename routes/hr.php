<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PerformanceReviewController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('hr')->group(function () {
    Route::middleware(['can:employees.read'])->name('employees.')->group(function () {
        Route::livewire('employees', 'pages::hr.employees-index')->name('index');
        Route::livewire('employees/create', 'pages::hr.employees-create')->middleware('can:employees.create')->name('create');
        Route::livewire('employees/{employee}/edit', 'pages::hr.employees-edit')->middleware('can:employees.update')->name('edit');

        Route::post('employees', [EmployeeController::class, 'store'])->middleware('can:employees.create')->name('store');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->middleware('can:employees.update')->name('update');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('can:employees.delete')->name('destroy');
    });

    Route::middleware(['can:payrolls.read'])->name('payrolls.')->group(function () {
        Route::livewire('payrolls', 'pages::hr.payrolls-index')->name('index');
        Route::livewire('payrolls/create', 'pages::hr.payrolls-create')->middleware('can:payrolls.create')->name('create');
        Route::livewire('payrolls/{payroll}/edit', 'pages::hr.payrolls-edit')->middleware('can:payrolls.update')->name('edit');

        Route::post('payrolls', [PayrollController::class, 'store'])->middleware('can:payrolls.create')->name('store');
        Route::put('payrolls/{payroll}', [PayrollController::class, 'update'])->middleware('can:payrolls.update')->name('update');
        Route::delete('payrolls/{payroll}', [PayrollController::class, 'destroy'])->middleware('can:payrolls.delete')->name('destroy');
    });

    Route::middleware(['can:attendance.read'])->name('attendance.')->group(function () {
        Route::livewire('attendance', 'pages::hr.attendance-index')->name('index');
        Route::livewire('attendance/create', 'pages::hr.attendance-create')->middleware('can:attendance.create')->name('create');
        Route::livewire('attendance/{attendance}/edit', 'pages::hr.attendance-edit')->middleware('can:attendance.update')->name('edit');

        Route::post('attendance', [AttendanceController::class, 'store'])->middleware('can:attendance.create')->name('store');
        Route::put('attendance/{attendance}', [AttendanceController::class, 'update'])->middleware('can:attendance.update')->name('update');
        Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroy'])->middleware('can:attendance.delete')->name('destroy');
    });

    Route::middleware(['can:leave-requests.read'])->name('leave-requests.')->group(function () {
        Route::livewire('leave-requests', 'pages::hr.leave-requests-index')->name('index');
        Route::livewire('leave-requests/create', 'pages::hr.leave-requests-create')->middleware('can:leave-requests.create')->name('create');
        Route::livewire('leave-requests/{leaveRequest}', 'pages::hr.leave-requests-show')->name('show');
        Route::livewire('leave-requests/{leaveRequest}/edit', 'pages::hr.leave-requests-edit')->middleware('can:leave-requests.update')->name('edit');

        Route::post('leave-requests', [LeaveRequestController::class, 'store'])->middleware('can:leave-requests.create')->name('store');
        Route::put('leave-requests/{leaveRequest}', [LeaveRequestController::class, 'update'])->middleware('can:leave-requests.update')->name('update');
        Route::delete('leave-requests/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->middleware('can:leave-requests.delete')->name('destroy');
    });

    Route::middleware(['can:shift-management.read'])->name('shifts.')->group(function () {
        Route::livewire('shifts', 'pages::hr.shifts-index')->name('index');
        Route::livewire('shifts/create', 'pages::hr.shifts-create')->middleware('can:shift-management.create')->name('create');
        Route::livewire('shifts/{shift}/edit', 'pages::hr.shifts-edit')->middleware('can:shift-management.update')->name('edit');

        Route::post('shifts', [ShiftController::class, 'store'])->middleware('can:shift-management.create')->name('store');
        Route::put('shifts/{shift}', [ShiftController::class, 'update'])->middleware('can:shift-management.update')->name('update');
        Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->middleware('can:shift-management.delete')->name('destroy');
    });

    Route::middleware(['can:performance-reviews.read'])->name('performance-reviews.')->group(function () {
        Route::livewire('performance-reviews', 'pages::hr.performance-reviews-index')->name('index');
        Route::livewire('performance-reviews/create', 'pages::hr.performance-reviews-create')->middleware('can:performance-reviews.create')->name('create');
        Route::livewire('performance-reviews/{performanceReview}/edit', 'pages::hr.performance-reviews-edit')->middleware('can:performance-reviews.update')->name('edit');

        Route::post('performance-reviews', [PerformanceReviewController::class, 'store'])->middleware('can:performance-reviews.create')->name('store');
        Route::put('performance-reviews/{performanceReview}', [PerformanceReviewController::class, 'update'])->middleware('can:performance-reviews.update')->name('update');
        Route::delete('performance-reviews/{performanceReview}', [PerformanceReviewController::class, 'destroy'])->middleware('can:performance-reviews.delete')->name('destroy');
    });
});
