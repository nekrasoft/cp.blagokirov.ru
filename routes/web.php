<?php

use App\Http\Controllers\CrossServiceSsoController;
use App\Http\Controllers\DemoDatabaseDebugController;
use App\Http\Controllers\GoogleBusinessProfileOAuthController;
use App\Http\Middleware\UseCounterpartyDemoDatabase;
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

Route::middleware(['auth:counterparty', UseCounterpartyDemoDatabase::class])
    ->get('/billing/demo-debug', DemoDatabaseDebugController::class)
    ->name('billing.demo-debug');

Route::middleware('auth')
    ->prefix('admin/integrations/google-business-profile/oauth')
    ->group(function (): void {
        Route::get('/start', [GoogleBusinessProfileOAuthController::class, 'redirectToGoogle'])
            ->name('admin.google-business-profile.oauth.start');
        Route::get('/callback', [GoogleBusinessProfileOAuthController::class, 'handleGoogleCallback'])
            ->name('admin.google-business-profile.oauth.callback');
    });
