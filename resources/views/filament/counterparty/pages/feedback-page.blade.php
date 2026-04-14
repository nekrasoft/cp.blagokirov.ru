<x-filament-panels::page>
    <x-filament::callout
        color="success"
        heading="Благодарим за выбор нашей компании!"
        icon="heroicon-m-hand-thumb-up"
        description="Надеемся, вы остались довольны результатом. Пожалуйста, поделитесь впечатлениями, оставив отзыв в онлайн-сервисах."
    >
        <x-slot name="footer">
            <ol class="list-decimal space-y-1 ps-5 text-sm">
                <li>
                    Яндекс:
                    <a
                        class="underline"
                        href="https://yandex.ru/maps/org/10924053513/?add-review=true"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        yandex.ru/maps/org/10924053513/?add-review=true
                    </a>
                </li>
                <li>
                    Google:
                    <a
                        class="underline"
                        href="https://g.page/r/CT-lPsP9hSHrEAE/review"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        g.page/r/CT-lPsP9hSHrEAE/review
                    </a>
                </li>
            </ol>

            <p class="mt-3 text-sm">
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
