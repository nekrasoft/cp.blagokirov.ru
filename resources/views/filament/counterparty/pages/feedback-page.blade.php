<x-filament-panels::page>
    <style>
        .feedback-callout .fi-callout-heading {
            font-size: 1.2rem;
            line-height: 1.2rem;
        }
    </style>

    <x-filament::callout
        class="feedback-callout"
        color="success"
        heading="Благодарим за выбор нашей компании!"
        icon="heroicon-m-hand-thumb-up"
        description="Надеемся, вы остались довольны результатом. Пожалуйста, поделитесь впечатлениями, оставив отзыв в онлайн-сервисах."
    >
        <x-slot name="footer">
            <p class="text-sm">
                Вы можете просто поставить нам оценку, а также написать пару слов — ваш отзыв поможет другим клиентам.
            </p>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-filament::button
                    tag="a"
                    href="https://yandex.ru/maps/org/10924053513/?add-review=true"
                    target="_blank"
                    rel="noopener noreferrer"
                    icon="heroicon-m-arrow-top-right-on-square"
                    size="sm"
                    color="success"
                >
                    Оставить отзыв в Яндекс
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="https://g.page/r/CT-lPsP9hSHrEAE/review"
                    target="_blank"
                    rel="noopener noreferrer"
                    icon="heroicon-m-arrow-top-right-on-square"
                    size="sm"
                    color="gray"
                >
                    Оставить отзыв в Google
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::callout>

    <script src="https://res.smartwidgets.ru/app.js" defer></script>
    <div class="sw-app" data-app="43ef9cb9fb4c9709478ff11c9d4efc26"></div>

    @if ($showGoogleReviewsSection)
        <x-filament::section
            class="mt-6"
            heading="Отзывы Google"
            description="Последние отзывы из Google Business Profile."
        >
            @if (filled($googleReviewsError))
                <x-filament::callout
                    color="warning"
                    icon="heroicon-m-exclamation-circle"
                    :description="$googleReviewsError"
                />
            @elseif (blank($googleReviews))
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Пока нет отзывов Google для отображения.
                </p>
            @else
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($googleReviews as $review)
                        <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $review['reviewer_name'] ?? 'Пользователь Google' }}
                                </h3>
                                <span class="shrink-0 text-sm font-semibold text-amber-500">
                                    {{ str_repeat('★', (int) ($review['rating'] ?? 5)) }}<span class="text-gray-300 dark:text-gray-600">{{ str_repeat('★', max(0, 5 - (int) ($review['rating'] ?? 5))) }}</span>
                                </span>
                            </div>

                            @if (filled($review['comment'] ?? null))
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                                    {{ $review['comment'] }}
                                </p>
                            @else
                                <p class="mt-2 text-sm italic text-gray-500 dark:text-gray-400">
                                    Без текстового комментария.
                                </p>
                            @endif

                            @if (filled($review['display_date'] ?? null))
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $review['display_date'] }}
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
