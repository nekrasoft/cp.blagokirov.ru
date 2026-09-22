<?php

use App\Http\Controllers\BunkerPickupFileController;
use App\Http\Controllers\CrossServiceSsoController;
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
        Route::get('/billing/pickup-files/{file}', [BunkerPickupFileController::class, 'counterparty'])
            ->middleware(UseCounterpartyDemoDatabase::class)
            ->whereNumber('file')
            ->name('billing.pickup-files.show');
    });

Route::middleware('auth')->group(function (): void {
    Route::get('/admin/pickup-files/{file}', [BunkerPickupFileController::class, 'admin'])
        ->whereNumber('file')
        ->name('admin.pickup-files.show');
});

Route::middleware('auth')
    ->prefix('admin/integrations/google-business-profile/oauth')
    ->group(function (): void {
        Route::get('/start', [GoogleBusinessProfileOAuthController::class, 'redirectToGoogle'])
            ->name('admin.google-business-profile.oauth.start');
        Route::get('/callback', [GoogleBusinessProfileOAuthController::class, 'handleGoogleCallback'])
            ->name('admin.google-business-profile.oauth.callback');
    });
