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
                Вы можете просто поставить нам оценку, а также написать что-то полезное — ваше мнение поможет другим покупателям.
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
</x-filament-panels::page>
