<?php

use App\Modules\Auth\Controllers\GoogleTechnicianAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/technician/google', [
    GoogleTechnicianAuthController::class,
    'redirect',
]);

Route::get('/auth/technician/google/callback', [
    GoogleTechnicianAuthController::class,
    'callback',
]);

Route::get('/', function () {
    return view('welcome');
});
