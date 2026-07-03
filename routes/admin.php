<?php

use App\Http\Controllers\Admin\CertificatePdfController;
use App\Http\Controllers\Admin\FeeReceiptController;
use App\Http\Controllers\Admin\ReportCardController;
use App\Http\Controllers\Admin\TimetablePdfController;
use App\Livewire\Admin\Home;
use App\Livewire\Admin\Standard;
use App\Livewire\Admin\Student;
use App\Livewire\Admin\Teacher;
use App\Livewire\Admin\Announcement;
use App\Livewire\Admin\TimeTable;
use App\Livewire\Admin\Arrangement;
use App\Livewire\Admin\Fee;
use App\Livewire\Admin\FeeStructure;
use App\Livewire\Admin\Homework;
use App\Livewire\Admin\Attendance;
use App\Livewire\Admin\Syllabus;
use App\Livewire\Admin\Calender;
use App\Livewire\Admin\RulesAndRegulation;
use App\Livewire\Admin\Content;
use App\Livewire\Admin\Performance;
use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\Quiz;
use App\Livewire\Admin\Support;
use App\Livewire\Admin\IdCard;
use App\Livewire\Admin\AdmitCard;
use App\Livewire\Admin\SeatingPlan;
use App\Livewire\Admin\ExamCopy;
use App\Livewire\Admin\ReportCard;
use App\Livewire\Admin\ContactAdmin;
use App\Livewire\Admin\AboutApp;
use App\Livewire\Admin\AddExam;
use App\Livewire\Admin\Book;
use App\Livewire\Admin\Credit;
use App\Livewire\Admin\RateLms;
use App\Livewire\Admin\Enqueries;
use App\Livewire\Admin\Login;
use App\Livewire\Admin\Payroll;
use App\Livewire\Admin\PrivacyPolicy;
use App\Livewire\Admin\QuickLinks;
use App\Livewire\Admin\TcCertificate;
use App\Livewire\Admin\TermOfUse;
use App\Livewire\Admin\TermsAndCondition;
use App\Livewire\Admin\Transport;
use App\Livewire\Admin\AccountUsers;
use App\Livewire\Admin\Admissions;
use App\Livewire\Admin\Ledger;
use App\Livewire\Admin\Lists;
use App\Livewire\Admin\More;
use App\Livewire\Admin\Users;
use App\Livewire\Chat\Messenger;
use App\Livewire\Components\Notification;
use App\Livewire\Components\Profile;
use App\Livewire\ResetPassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest:web'])->group(function () {
    Route::get('login', Login::class)->name('admin.login');
    Route::get('reset-password', ResetPassword::class)->name('reset.password');
});

Route::middleware(['auth:web', 'admin', 'module'])->group(function () {
    Route::prefix('/{organization}')->group(function () {
        Route::get('/home', Home::class)->name('admin.home');
        Route::get('/quick-links', QuickLinks::class)->name('admin.quick-links');
        Route::get('/standard', Standard::class)->name('admin.standard');
        Route::get('/add-exam', AddExam::class)->name('admin.add-exam');
        Route::get('/student', Student::class)->name('admin.student');
        Route::get('/admissions', Admissions::class)->name('admin.admissions');
        Route::get('/lists',      Lists::class)->name('admin.lists');
        Route::get('/teacher', Teacher::class)->name('admin.teacher');
        Route::get('/announcement', Announcement::class)->name('admin.announcement');
        Route::get('/timetable', TimeTable::class)->name('admin.timetable');
        Route::get('/timetable/{standard}/{section}/pdf', [TimetablePdfController::class, 'download'])
            ->whereNumber(['standard', 'section'])
            ->name('admin.timetable.pdf');
        Route::get('/timetable/teacher/{teacher}/pdf', [TimetablePdfController::class, 'downloadTeacher'])
            ->whereNumber('teacher')
            ->name('admin.timetable.teacher.pdf');
        Route::get('/arrangement', Arrangement::class)->name('admin.arrangement');
        Route::get('/fee', Fee::class)->name('admin.fee');
        Route::get('/fee-structure', FeeStructure::class)->name('admin.fee-structure');
        Route::get('/ledger', Ledger::class)->name('admin.ledger');
        Route::get('/more', More::class)->name('admin.more');
        Route::get('/ledger/statement', [\App\Http\Controllers\Admin\LedgerStatementController::class, 'download'])
            ->name('admin.ledger.statement');
        Route::get('/fee-structure/pdf', [\App\Http\Controllers\Admin\FeeStructurePdfController::class, 'show'])
            ->name('admin.fee-structure.pdf');
        Route::get('/fee/receipt/{id}', [FeeReceiptController::class, 'show'])->name('admin.fee.receipt');
        Route::get('/homework', Homework::class)->name('admin.homework');
        Route::get('/attendance', Attendance::class)->name('admin.attendance');
        Route::get('/syllabus', Syllabus::class)->name('admin.syllabus');
        Route::get('/calender', Calender::class)->name('admin.calender');
        Route::get('/rules-and-regulation', RulesAndRegulation::class)->name('admin.rules-and-regulation');
        Route::get('/content', Content::class)->name('admin.content');
        Route::get('/performance', Performance::class)->name('admin.performance');
        Route::get('/analytics', Analytics::class)->name('admin.analytics');
        Route::get('/users', Users::class)->name('admin.users');
        Route::get('/messages', Messenger::class)->name('admin.messages');
        Route::get('/quiz', Quiz::class)->name('admin.quiz');
        Route::get('/book', Book::class)->name('admin.book');
        Route::get('/support', Support::class)->name('admin.support');
        Route::get('/id-card', IdCard::class)->name('admin.id-card');
        Route::get('/admit-card', AdmitCard::class)->name('admin.admit-card');
        Route::get('/seating-plan', SeatingPlan::class)->name('admin.seating-plan');
        Route::get('/exam-copy', ExamCopy::class)->name('admin.exam-copy');
        Route::get('/report-card', ReportCard::class)->name('admin.report-card');
        Route::get('/contact-admin', ContactAdmin::class)->name('admin.contact-admin');
        Route::get('/about-app', AboutApp::class)->name('admin.about-app');
        Route::get('/rate-lms', RateLms::class)->name('admin.rate-lms');
        Route::get('/enqueries', Enqueries::class)->name('admin.enqueries');
        Route::get('/terms-and-condition', TermsAndCondition::class)->name('admin.terms-and-condition');
        Route::get('/payroll', Payroll::class)->name('admin.payroll');
        Route::get('/transport', Transport::class)->name('admin.transport');
        Route::get('/tc-certificate', TcCertificate::class)->name('admin.tc-certificate');
        Route::get('/privacy-policy', PrivacyPolicy::class)->name('admin.privacy-policy');
        Route::get('/credit', Credit::class)->name('admin.credit');
        Route::get('/terms-of-use', TermOfUse::class)->name('admin.terms-of-use');
        Route::get('/account-users', AccountUsers::class)->name('admin.account-users');

        //Navbar Route
        Route::get('/profile', Profile::class)->name('admin.profile');
        Route::get('/notification', Notification::class)->name('admin.notification');

        //Certificate PDF Download Routes
        Route::get('/certificates/{id}/download', [CertificatePdfController::class, 'downloadCert'])
            ->name('admin.cert.download');

        Route::get('/tc/{id}/download', [CertificatePdfController::class, 'downloadTc'])
            ->name('admin.tc.download');

        // Report Card PDF Routes
        Route::get('/report-card/{id}/download', [ReportCardController::class, 'download'])
            ->name('admin.report-card.download');
        Route::get('/report-card/{id}/print', [ReportCardController::class, 'print'])
            ->name('admin.report-card.print');

        // Seating Plan printable chart
        Route::get('/seating-plan/{id}/print', [\App\Http\Controllers\Admin\SeatingPlanPrintController::class, 'print'])
            ->name('admin.seating-plan.print');

        // ID Card printable / download
        Route::get('/id-card/{type}/{id}/print', [\App\Http\Controllers\Admin\IdCardPrintController::class, 'print'])
            ->name('admin.id-card.print');

        // Transport fee receipt
        Route::get('/transport/receipt/{id}', [\App\Http\Controllers\Admin\TransportReceiptController::class, 'show'])
            ->name('admin.transport.receipt');

        // Admit Card view / download / print-all
        Route::get('/admit-card/{id}/view', [\App\Http\Controllers\Admin\AdmitCardController::class, 'view'])
            ->name('admin.admit-card.view');
        Route::get('/admit-card/{id}/download', [\App\Http\Controllers\Admin\AdmitCardController::class, 'download'])
            ->name('admin.admit-card.download');
        Route::get('/admit-card/print-all', [\App\Http\Controllers\Admin\AdmitCardController::class, 'printAll'])
            ->name('admin.admit-card.print-all');
    });
});
