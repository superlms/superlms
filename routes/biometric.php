<?php

use App\Http\Controllers\BiometricPushController;
use Illuminate\Support\Facades\Route;

/*
| The schools' attendance machines (ADMS / iclock push) — App\Http\Controllers\BiometricPushController.
| Loaded from bootstrap/app.php outside the web group: a machine has no
| session, cookies or CSRF token. Some firmware adds ".aspx" to the names.
*/

foreach (['', '.aspx'] as $ext) {
    Route::get("/iclock/cdata{$ext}", [BiometricPushController::class, 'handshake']);
    Route::post("/iclock/cdata{$ext}", [BiometricPushController::class, 'receive']);
    Route::get("/iclock/getrequest{$ext}", [BiometricPushController::class, 'commands']);
    Route::post("/iclock/devicecmd{$ext}", [BiometricPushController::class, 'results']);
    Route::get("/iclock/ping{$ext}", [BiometricPushController::class, 'ping']);
}
