<?php

use App\Livewire\Accounts\Login;
use App\Livewire\Accounts\ResetPassword;
use App\Livewire\Accounts\VerifyOtp;
use App\Livewire\Accounts\Dashboard;
use App\Livewire\Accounts\Payroll;
use App\Livewire\Accounts\Credit;
use App\Livewire\Accounts\Admissions;
use App\Livewire\Accounts\FeeSubmission;
use App\Livewire\Accounts\ViewFee;
use App\Livewire\Accounts\FeeStructure;
use App\Livewire\Accounts\Payments;
use App\Livewire\Accounts\Penalties;
use App\Livewire\Accounts\FeeCycles;
use App\Livewire\Accounts\Attendance;
use App\Livewire\Accounts\Transport;
use App\Livewire\Accounts\Calendar;
use App\Livewire\Accounts\IdCard;
use App\Livewire\Accounts\AdmitCard;
use App\Livewire\Accounts\ReportCard;
use App\Livewire\Accounts\TcCertificate;
use App\Livewire\Accounts\Profile;
use App\Livewire\Accounts\Notification;
use App\Livewire\Chat\Messenger;
use Illuminate\Support\Facades\Route;

Route::prefix('accounts')->group(function () {

    // Guest routes
    Route::middleware(['guest:web'])->group(function () {
        Route::get('/', Login::class)->name('accounts.login');
        Route::get('/reset-password', ResetPassword::class)->name('accounts.reset-password');
    });

    // OTP verification (auth but not fully verified)
    Route::middleware(['auth:web'])->group(function () {
        Route::get('/verify-otp', VerifyOtp::class)->name('accounts.verify-otp');
    });

    // Protected routes
    Route::middleware(['auth:web', 'accounts', 'module'])->group(function () {
        Route::prefix('/{organization}')->group(function () {
            Route::get('/dashboard', Dashboard::class)->name('accounts.dashboard');
            Route::get('/payroll', Payroll::class)->name('accounts.payroll');
            Route::get('/credit', Credit::class)->name('accounts.credit');
            Route::get('/admissions', Admissions::class)->name('accounts.admissions');
            Route::get('/fee-submission', FeeSubmission::class)->name('accounts.fee-submission');
            Route::get('/view-fee', ViewFee::class)->name('accounts.view-fee');
            Route::get('/fee-structure', FeeStructure::class)->name('accounts.fee-structure');
            Route::get('/payments', Payments::class)->name('accounts.payments');
            Route::get('/penalties', Penalties::class)->name('accounts.penalties');
            Route::get('/fee-cycles', FeeCycles::class)->name('accounts.fee-cycles');
            Route::get('/attendance', Attendance::class)->name('accounts.attendance');
            Route::get('/transport', Transport::class)->name('accounts.transport');
            Route::get('/transport/receipt/{id}', [\App\Http\Controllers\Admin\TransportReceiptController::class, 'show'])->name('accounts.transport.receipt');
            Route::get('/calendar', Calendar::class)->name('accounts.calendar');
            Route::get('/id-card', IdCard::class)->name('accounts.id-card');
            Route::get('/admit-card', AdmitCard::class)->name('accounts.admit-card');
            Route::get('/admit-card/{id}/view', [\App\Http\Controllers\Admin\AdmitCardController::class, 'view'])->name('accounts.admit-card.view');
            Route::get('/admit-card/{id}/download', [\App\Http\Controllers\Admin\AdmitCardController::class, 'download'])->name('accounts.admit-card.download');
            Route::get('/admit-card/print-all', [\App\Http\Controllers\Admin\AdmitCardController::class, 'printAll'])->name('accounts.admit-card.print-all');
            Route::get('/report-card', ReportCard::class)->name('accounts.report-card');
            Route::get('/report-card/{id}/download', [\App\Http\Controllers\Admin\ReportCardController::class, 'download'])->name('accounts.report-card.download');
            Route::get('/report-card/{id}/print', [\App\Http\Controllers\Admin\ReportCardController::class, 'print'])->name('accounts.report-card.print');
            Route::get('/tc-certificate', TcCertificate::class)->name('accounts.tc-certificate');
            Route::get('/certificates/{id}/download', [\App\Http\Controllers\Admin\CertificatePdfController::class, 'downloadCert'])->name('accounts.cert.download');
            Route::get('/tc/{id}/download', [\App\Http\Controllers\Admin\CertificatePdfController::class, 'downloadTc'])->name('accounts.tc.download');
            Route::get('/profile', Profile::class)->name('accounts.profile');
            Route::get('/messages', Messenger::class)->name('accounts.messages');
            Route::get('/notification', Notification::class)->name('accounts.notification');
        });
    });
});
