<?php

use App\Http\Controllers\PhonePeController;
use Illuminate\Support\Facades\Route;

// PhonePe browser redirect target (registered early so the website's
// {organization} wildcard doesn't swallow it).
Route::get('/payment/return/{merchantOrderId}', [PhonePeController::class, 'paymentReturn'])
    ->name('phonepe.return');

//SuperLMS Website (must be before admin — avoids {organization} wildcard swallowing /web/* routes)
require __DIR__.'/website.php';

// Super Admin
require __DIR__.'/super-admin.php';

//Admin School
require __DIR__.'/admin.php';

//Accounts Panel
require __DIR__.'/accounts.php';



