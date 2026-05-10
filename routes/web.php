<?php

use App\Http\Controllers\CrossServiceSsoController;
use App\Http\Controllers\GoogleBusinessProfileOAuthController;
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

Route::middleware('auth')
    ->prefix('admin/integrations/google-business-profile/oauth')
    ->group(function (): void {
        Route::get('/start', [GoogleBusinessProfileOAuthController::class, 'redirectToGoogle'])
            ->name('admin.google-business-profile.oauth.start');
        Route::get('/callback', [GoogleBusinessProfileOAuthController::class, 'handleGoogleCallback'])
            ->name('admin.google-business-profile.oauth.callback');
    });
