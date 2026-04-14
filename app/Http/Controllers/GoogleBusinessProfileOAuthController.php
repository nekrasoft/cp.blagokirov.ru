<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GoogleBusinessProfileOAuthController extends Controller
{
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $clientId = trim((string) config('services.google_business_profile.client_id'));

        if ($clientId === '') {
            abort(500, 'GOOGLE_BUSINESS_PROFILE_CLIENT_ID is not configured.');
        }

        $redirectUri = $this->resolveRedirectUri($request);
        $authorizeUrl = trim((string) config('services.google_business_profile.oauth_authorize_url'));
        if ($authorizeUrl === '') {
            $authorizeUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
        }

        $state = Str::random(40);
        $request->session()->put('google_business_profile_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            // Force account picker to avoid accidental login with a non-tester Google account.
            'prompt' => 'consent select_account',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away(rtrim($authorizeUrl, '?') . '?' . $query);
    }

    public function handleGoogleCallback(Request $request): View
    {
        $expectedState = (string) $request->session()->pull('google_business_profile_oauth_state', '');
        $actualState = trim((string) $request->query('state'));

        if ($expectedState === '' || $actualState === '' || ! hash_equals($expectedState, $actualState)) {
            return view('admin.google-business-profile.oauth-result', [
                'success' => false,
                'message' => 'Не удалось подтвердить состояние OAuth (state). Попробуйте снова.',
                'refreshToken' => null,
                'accessToken' => null,
                'rawResponse' => null,
            ]);
        }

        if ($request->filled('error')) {
            return view('admin.google-business-profile.oauth-result', [
                'success' => false,
                'message' => 'Google вернул ошибку: ' . trim((string) $request->query('error')),
                'refreshToken' => null,
                'accessToken' => null,
                'rawResponse' => null,
            ]);
        }

        $code = trim((string) $request->query('code'));
        if ($code === '') {
            return view('admin.google-business-profile.oauth-result', [
                'success' => false,
                'message' => 'В callback отсутствует параметр code.',
                'refreshToken' => null,
                'accessToken' => null,
                'rawResponse' => null,
            ]);
        }

        $clientId = trim((string) config('services.google_business_profile.client_id'));
        $clientSecret = trim((string) config('services.google_business_profile.client_secret'));
        $tokenUrl = trim((string) config('services.google_business_profile.token_url'));
        $timeout = max(3, (int) config('services.google_business_profile.timeout_seconds', 10));
        $redirectUri = $this->resolveRedirectUri($request);

        if ($clientId === '' || $clientSecret === '') {
            return view('admin.google-business-profile.oauth-result', [
                'success' => false,
                'message' => 'GOOGLE_BUSINESS_PROFILE_CLIENT_ID/SECRET не настроены.',
                'refreshToken' => null,
                'accessToken' => null,
                'rawResponse' => null,
            ]);
        }

        if ($tokenUrl === '') {
            $tokenUrl = 'https://oauth2.googleapis.com/token';
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout($timeout)
            ->post($tokenUrl, [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

        $payload = $response->json();
        $refreshToken = trim((string) ($payload['refresh_token'] ?? ''));
        $accessToken = trim((string) ($payload['access_token'] ?? ''));

        if ($response->failed()) {
            return view('admin.google-business-profile.oauth-result', [
                'success' => false,
                'message' => 'Ошибка обмена code на токены (HTTP ' . $response->status() . ').',
                'refreshToken' => null,
                'accessToken' => null,
                'rawResponse' => $payload,
            ]);
        }

        if ($refreshToken === '') {
            return view('admin.google-business-profile.oauth-result', [
                'success' => false,
                'message' => 'refresh_token не получен. Обычно помогает повторная авторизация с prompt=consent.',
                'refreshToken' => null,
                'accessToken' => $accessToken !== '' ? $accessToken : null,
                'rawResponse' => $payload,
            ]);
        }

        return view('admin.google-business-profile.oauth-result', [
            'success' => true,
            'message' => 'refresh_token успешно получен. Скопируйте его в .env.',
            'refreshToken' => $refreshToken,
            'accessToken' => $accessToken !== '' ? $accessToken : null,
            'rawResponse' => $payload,
        ]);
    }

    private function resolveRedirectUri(Request $request): string
    {
        $configured = trim((string) config('services.google_business_profile.oauth_redirect_uri'));

        if ($configured !== '') {
            return $configured;
        }

        return route('admin.google-business-profile.oauth.callback');
    }
}
