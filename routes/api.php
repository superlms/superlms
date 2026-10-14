<?php

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public website API
Route::prefix('website')->group(function () {
    Route::get('/stats',            [WebsiteController::class, 'stats']);
    Route::get('/schools',          [WebsiteController::class, 'schools']);
    Route::get('/testimonials',     [WebsiteController::class, 'testimonials']);
    Route::get('/privacy-policy',   [WebsiteController::class, 'privacyPolicy']);
    Route::get('/terms-conditions', [WebsiteController::class, 'termsConditions']);
    Route::get('/terms-of-use',     [WebsiteController::class, 'termsOfUse']);

    // Dynamic marketing pages (why-us, services, careers, become-executive, blogs, faqs)
    Route::get('/page/{slug}',      [WebsiteController::class, 'page']);

    // Anti-spam: 5 submissions per minute per IP on form endpoints.
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/contact',        [WebsiteController::class, 'contact']);
        Route::post('/demo',           [WebsiteController::class, 'demo']);
        Route::post('/schedule-call',  [WebsiteController::class, 'scheduleCall']);
        Route::post('/school-contact', [WebsiteController::class, 'schoolContact']);
        Route::post('/career-apply',   [WebsiteController::class, 'careerApply']);
        Route::post('/executive-apply', [WebsiteController::class, 'executiveApply']);
    });
});

// Meta's WhatsApp webhook (public): the callback check, then message statuses
// signed with the app secret — a failed message is not charged to its school.
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);

//v1 api
require __DIR__ . '/v1.php';
