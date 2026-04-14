<?php

namespace App\Http\Controllers;

use App\Filament\Resources\WorkResource;
use App\Models\CounterpartyUser;
use App\Support\CrossServiceSsoToken;
use InvalidArgumentException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CrossServiceSsoController extends Controller
{
    public function redirectToMap(Request $request): RedirectResponse
    {
        $user = Auth::guard('counterparty')->user();

        if (! $user instanceof CounterpartyUser || ! $user->is_active || $user->counterparty_id <= 0) {
            return $this->redirectToBillingLoginWithSsoError();
        }

        $mapServiceUrl = rtrim((string) config('services.cross_service_sso.map_service_url'), '/');
        if ($mapServiceUrl === '') {
            throw new RuntimeException('MAP_SERVICE_URL is not configured.');
        }

        $token = CrossServiceSsoToken::issue(
            [
                'direction' => 'cp_to_map',
                'uid' => (int) $user->id,
                'counterparty_id' => (int) $user->counterparty_id,
            ],
            (string) config('services.cross_service_sso.secret'),
            (int) config('services.cross_service_sso.ttl_seconds', 90),
        );

        return redirect()->away($mapServiceUrl . '/sso-login?token=' . rawurlencode($token));
    }

    public function loginFromMap(Request $request): RedirectResponse
    {
        $token = trim((string) $request->query('token'));
        if ($token === '') {
            return $this->redirectToBillingLoginWithSsoError();
        }

        try {
            $payload = CrossServiceSsoToken::validate($token, (string) config('services.cross_service_sso.secret'));
        } catch (InvalidArgumentException $e) {
            return $this->redirectToBillingLoginWithSsoError();
        }

        if (($payload['direction'] ?? null) !== 'map_to_cp') {
            return $this->redirectToBillingLoginWithSsoError();
        }

        $userId = (int) ($payload['uid'] ?? 0);
        if ($userId <= 0) {
            return $this->redirectToBillingLoginWithSsoError();
        }

        $user = CounterpartyUser::query()
            ->whereKey($userId)
            ->where('is_active', true)
            ->first();

        if (! $user instanceof CounterpartyUser || (int) $user->counterparty_id <= 0) {
            return $this->redirectToBillingLoginWithSsoError();
        }

        $payloadCounterpartyId = (int) ($payload['counterparty_id'] ?? 0);
        if ($payloadCounterpartyId > 0 && $payloadCounterpartyId !== (int) $user->counterparty_id) {
            return $this->redirectToBillingLoginWithSsoError();
        }

        Auth::guard('counterparty')->login($user);
        $request->session()->regenerate();

        return redirect()->to(WorkResource::getUrl(panel: 'counterparty'));
    }

    private function redirectToBillingLoginWithSsoError(): RedirectResponse
    {
        return redirect()->to('/billing/login?sso_error=403');
    }
}
