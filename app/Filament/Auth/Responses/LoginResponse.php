<?php

namespace App\Filament\Auth\Responses;

use App\Filament\Resources\WorkResource;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $panel || $panel->getId() !== 'counterparty') {
            return redirect()->intended(Filament::getUrl());
        }

        $fallbackUrl = WorkResource::getUrl(panel: 'counterparty');
        $intendedUrl = (string) session()->pull('url.intended', '');

        if ($this->isSafeCounterpartyUrl($intendedUrl)) {
            return redirect()->to($intendedUrl);
        }

        return redirect()->to($fallbackUrl);
    }

    private function isSafeCounterpartyUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');

        if ($host !== '' && strcasecmp($host, (string) request()->getHost()) !== 0) {
            return false;
        }

        if ($path === '' || $path === '/billing' || $path === '/billing/login') {
            return false;
        }

        if ($path === '/billing/sso/map' || $path === '/billing/sso-login') {
            return false;
        }

        return str_starts_with($path, '/billing/');
    }
}

