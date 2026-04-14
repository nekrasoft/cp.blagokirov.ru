<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleBusinessProfileReviewsService
{
    /**
     * @return array{enabled: bool, configured: bool, reviews: array<int, array<string, mixed>>, error: ?string}
     */
    public function getReviews(): array
    {
        $config = config('services.google_business_profile', []);

        if (! (bool) ($config['enabled'] ?? false)) {
            return [
                'enabled' => false,
                'configured' => false,
                'reviews' => [],
                'error' => null,
            ];
        }

        $accountPath = $this->normalizeResourcePath((string) ($config['account_id'] ?? ''), 'accounts');
        $locationPath = $this->normalizeResourcePath((string) ($config['location_id'] ?? ''), 'locations');
        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = trim((string) ($config['client_secret'] ?? ''));
        $refreshToken = trim((string) ($config['refresh_token'] ?? ''));

        if ($accountPath === '' || $locationPath === '' || $clientId === '' || $clientSecret === '' || $refreshToken === '') {
            return [
                'enabled' => true,
                'configured' => false,
                'reviews' => [],
                'error' => 'Google-отзывы пока не настроены.',
            ];
        }

        $limit = max(1, min(50, (int) ($config['reviews_limit'] ?? 6)));
        $cacheMinutes = max(1, (int) ($config['cache_minutes'] ?? 30));
        $cacheKey = sprintf(
            'google_business_profile_reviews:%s:%s:%d',
            str_replace('/', '_', $accountPath),
            str_replace('/', '_', $locationPath),
            $limit
        );

        try {
            $reviews = Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use (
                $config,
                $clientId,
                $clientSecret,
                $refreshToken,
                $accountPath,
                $locationPath,
                $limit
            ): array {
                $accessToken = $this->fetchAccessToken(
                    tokenUrl: (string) ($config['token_url'] ?? ''),
                    clientId: $clientId,
                    clientSecret: $clientSecret,
                    refreshToken: $refreshToken,
                    timeoutSeconds: max(3, (int) ($config['timeout_seconds'] ?? 10)),
                );

                return $this->fetchReviews(
                    apiBaseUrl: (string) ($config['api_base_url'] ?? ''),
                    accessToken: $accessToken,
                    accountPath: $accountPath,
                    locationPath: $locationPath,
                    limit: $limit,
                    timeoutSeconds: max(3, (int) ($config['timeout_seconds'] ?? 10)),
                );
            });

            return [
                'enabled' => true,
                'configured' => true,
                'reviews' => $reviews,
                'error' => null,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'enabled' => true,
                'configured' => true,
                'reviews' => [],
                'error' => 'Не удалось загрузить отзывы Google. Попробуйте позже.',
            ];
        }
    }

    private function fetchAccessToken(
        string $tokenUrl,
        string $clientId,
        string $clientSecret,
        string $refreshToken,
        int $timeoutSeconds
    ): string {
        $tokenUrl = trim($tokenUrl);
        if ($tokenUrl === '') {
            $tokenUrl = 'https://oauth2.googleapis.com/token';
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout($timeoutSeconds)
            ->post($tokenUrl, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Google OAuth token request failed with status ' . $response->status());
        }

        $accessToken = trim((string) $response->json('access_token'));
        if ($accessToken === '') {
            throw new RuntimeException('Google OAuth access_token is empty.');
        }

        return $accessToken;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchReviews(
        string $apiBaseUrl,
        string $accessToken,
        string $accountPath,
        string $locationPath,
        int $limit,
        int $timeoutSeconds
    ): array {
        $apiBaseUrl = rtrim(trim($apiBaseUrl), '/');
        if ($apiBaseUrl === '') {
            $apiBaseUrl = 'https://mybusiness.googleapis.com/v4';
        }

        $url = sprintf('%s/%s/%s/reviews', $apiBaseUrl, $accountPath, $locationPath);
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout($timeoutSeconds)
            ->get($url, [
                'pageSize' => $limit,
                'orderBy' => 'updateTime desc',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Google Business Profile reviews request failed with status ' . $response->status());
        }

        $rawReviews = $response->json('reviews', []);
        if (! is_array($rawReviews)) {
            return [];
        }

        $reviews = [];

        foreach (array_slice($rawReviews, 0, $limit) as $review) {
            if (! is_array($review)) {
                continue;
            }

            $reviews[] = $this->normalizeReview($review);
        }

        return $reviews;
    }

    /**
     * @param array<string, mixed> $review
     * @return array<string, mixed>
     */
    private function normalizeReview(array $review): array
    {
        $updatedAt = (string) Arr::get($review, 'updateTime', '');
        $createdAt = (string) Arr::get($review, 'createTime', '');
        $dateSource = $updatedAt !== '' ? $updatedAt : $createdAt;

        return [
            'reviewer_name' => trim((string) Arr::get($review, 'reviewer.displayName', '')) ?: 'Пользователь Google',
            'rating' => $this->normalizeRating((string) Arr::get($review, 'starRating', '')),
            'comment' => trim((string) Arr::get($review, 'comment', '')),
            'updated_at' => $updatedAt,
            'created_at' => $createdAt,
            'display_date' => $this->formatDate($dateSource),
        ];
    }

    private function normalizeRating(string $rawRating): int
    {
        $rawRating = strtoupper(trim($rawRating));

        return match ($rawRating) {
            'ONE' => 1,
            'TWO' => 2,
            'THREE' => 3,
            'FOUR' => 4,
            'FIVE' => 5,
            default => max(1, min(5, (int) $rawRating ?: 5)),
        };
    }

    private function formatDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->timezone(config('app.timezone', 'UTC'))->format('d.m.Y');
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeResourcePath(string $value, string $prefix): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, $prefix . '/')) {
            return $value;
        }

        return $prefix . '/' . $value;
    }
}
