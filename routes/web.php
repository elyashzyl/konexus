<?php

use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| KONEXUS runs as a single-page application. Every non-API route is handled
| by the Vue Router on the client, so all web requests render the SPA shell.
|
*/

Route::get('/', function () {
    return view('app');
})->name('home');

// Named route used by the authentication middleware for web redirects.
Route::get('/login', function () {
    return view('app');
})->name('login');

Route::get('/{any}', function (string $any) {
    // Unmatched API paths must return a structured JSON 404 instead of the SPA shell.
    if (str_starts_with($any, 'api/')) {
        return ApiResponse::error('Route not found.', null, 404);
    }

    return view('app');
})->where('any', '.*');
