<?php

use App\Http\Controllers\CrossServiceSsoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/billing/sso-login', [CrossServiceSsoController::class, 'loginFromMap'])
    ->name('billing.sso.login');

Route::middleware('auth:counterparty')
    ->group(function (): void {
        Route::get('/billing/sso/map', [CrossServiceSsoController::class, 'redirectToMap'])
            ->name('billing.sso.map');
    });
