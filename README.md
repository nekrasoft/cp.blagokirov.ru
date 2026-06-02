# cp.blagokirov.ru

Внутренняя панель управления и кабинет контрагента для сервиса «БлагоСервис».
Приложение показывает бункеры, заявки на вывоз, выполненные работы, счета и
аналитику. Этот README предназначен в первую очередь для агентов, которые
вносят изменения в проект.

## Перед началом работы

1. Прочитайте [`AGENTS.md`](AGENTS.md). В нем зафиксированы требования к
   точечным изменениям, security by design, SQL-индексам и проверкам.
2. Проверьте `git status --short`: в рабочем дереве могут быть изменения
   пользователя, их нельзя откатывать или перезаписывать без явного запроса.
3. Для задач в `/billing` отдельно проверьте tenant isolation: пользователь
   контрагента не должен увидеть или изменить данные другого контрагента.
4. При изменениях Filament, Blade, CSS или JS после тестов выполните
   `npm run build`. Собранный `public/build/` хранится в Git и проверяется при
   deploy.

## Стек

- PHP `^8.2`, Laravel `^12.0`
- Filament `^5.4`
- PHPUnit `^11.5`
- Vite `^7`, Tailwind CSS `^4`
- Node.js `22+` для локальной сборки
- SQLite по умолчанию в `.env.example`; production может использовать MySQL

## Точки входа

| URL | Назначение | Авторизация |
| --- | --- | --- |
| `/admin` | Админ-панель: управление справочниками, биллинг, аналитика | guard `web`, модель `App\Models\User` |
| `/billing` | Кабинет контрагента: его работы, счета, бункеры, заявки, отзывы | guard `counterparty`, модель `App\Models\CounterpartyUser` |
| `/billing/sso/map` | Переход из кабинета на карту бункеров | `auth:counterparty` |
| `/billing/sso-login` | Вход в кабинет по токену от карты | HMAC SSO-токен |
| `/admin/integrations/google-business-profile/oauth/*` | Получение Google refresh token | активный администратор с правом записи |
| `/` | Стандартная стартовая страница Laravel | без авторизации |

Регистрация панелей находится в:

- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Providers/Filament/CounterpartyPanelProvider.php`

Маршруты вне Filament находятся в `routes/web.php`.

## Предметная область

Основные таблицы и модели:

| Таблица | Модель | Назначение |
| --- | --- | --- |
| `users` | `App\Models\User` | Администраторы панели |
| `counterparties` | `App\Models\Counterparty` | Контрагенты |
| `counterparty_users` | `App\Models\CounterpartyUser` | Учетные записи кабинета контрагента |
| `bunkers` | `App\Models\Bunker` | Бункеры и текущая заполненность |
| `bunker_fill_requests` | `App\Models\BunkerFillRequest` | История заявок и событий заполненности |
| `works` | `App\Models\Work` | Выполненные работы |
| `invoices` | `App\Models\Invoice` | Счета и оплаты |
| `driver_work_time` | `App\Models\DriverWorkTime` | Рабочее время водителей |

Важно: репозиторий не содержит базовых миграций для части бизнес-таблиц:
`counterparties`, `bunkers`, `bunker_fill_requests`, `works`, `invoices` и
`driver_work_time`. Они существуют во внешней рабочей схеме. Чистый
`php artisan migrate` создает Laravel-таблицы и применяет локальные
инкрементальные изменения, но не разворачивает полноценную бизнес-БД.

Filament resources и dashboard намеренно проверяют наличие таблиц и колонок
через `Schema::hasTable()` и `Schema::hasColumn()`. Это позволяет работать с
разными версиями схемы. Не удаляйте эти проверки как «лишние» без отдельного
анализа production и demo схем.

## Инварианты доступа

### Администраторы

`App\Models\User` поддерживает роли:

- `admin`: чтение и запись;
- `readonly_admin`: только чтение.

Общий запрет записи для readonly-пользователей реализован через
`app/Filament/Resources/Concerns/AuthorizesAdminWrites.php`. Новые actions,
страницы и endpoints с мутациями должны проверять право записи явно или
переиспользовать этот механизм.

### Контрагенты

Обе панели используют общие Filament resources. Для кабинета ресурсы должны
оставаться read-only и фильтровать данные:

- `bunkers` и `bunker_fill_requests`: по `counterparty_id`, затем при наличии
  `district_scope` по району;
- `invoices`: по `counterparty_id`; район намеренно не ограничивает счета;
- `works`: через связанный счет с тем же `counterparty_id` или, для работ без
  счета, через `counterparty_name`; при `district_scope` район ищется в
  `works.note`.

`district_scope` поддерживает несколько значений через запятую, точку с
запятой или новую строку. Логика dashboard находится в
`app/Filament/Support/DashboardMetrics.php`, а списки ресурсов имеют свою
защитную фильтрацию в `getEloquentQuery()`. При изменении scope-логики
обновляйте оба слоя и тесты.

Если необходимые колонки для ограничения отсутствуют, код должен закрывать
доступ к данным через `whereRaw('1 = 0')`, а не показывать все строки.

### Demo-база

У `counterparty_users.is_demo` есть отдельный режим. Middleware
`app/Http/Middleware/UseCounterpartyDemoDatabase.php` на время billing-запроса
переключает default connection на `database.demo_connection`, затем
восстанавливает основное соединение и очищает runtime-кеш схемы через
`App\Filament\Support\RuntimeSchemaCache`.

При добавлении статического кеша информации о схеме в Filament resource или
dashboard зарегистрируйте его в `RuntimeSchemaCache`, иначе demo-пользователь
может получить метаданные основной БД.

## Основные модули

| Путь | Ответственность |
| --- | --- |
| `app/Filament/Resources/` | CRUD и read-only списки Filament |
| `app/Filament/Dashboard/Widgets/` | Виджеты обеих панелей |
| `app/Filament/Support/DashboardMetrics.php` | Общие запросы, tenant scope и агрегации dashboard |
| `app/Filament/Support/DriverWorkTimeSummary.php` | Помесячные итоги рабочего времени по водителям |
| `app/Filament/Support/TailAdminTheme.php` | Общая конфигурация темы двух панелей |
| `app/Services/BunkerFillLevelForecastService.php` | Автопрогноз заполненности бункеров |
| `app/Services/GoogleBusinessProfileReviewsService.php` | Загрузка и кеширование Google-отзывов |
| `app/Support/CrossServiceSsoToken.php` | Выпуск и валидация HMAC SSO-токенов |
| `resources/css/filament/tailadmin/theme.css` | Кастомная TailAdmin-подобная тема Filament |
| `routes/console.php` | Artisan-команды и scheduler |
| `deploy.sh` | Deploy в split-layout shared hosting |

## Интеграции и фоновые задачи

### Время водителей

Раздел `/admin/driver-work-times` содержит исходные записи времени и фильтры
по месяцу или произвольному периоду. Отчет `/admin/driver-work-time-summary`
показывает помесячные итоги отдельно по каждому водителю, включая суммарное
время и десятичные часы для дальнейшего расчета зарплаты.

### Карта бункеров

Кабинет и `map.blagokirov.ru` обмениваются короткоживущими HMAC SHA-256
токенами. Настройки: `MAP_SERVICE_URL`, `CROSS_SERVICE_SSO_SECRET`,
`CROSS_SERVICE_SSO_TTL_SECONDS`. В payload проверяется направление
`cp_to_map` или `map_to_cp`.

### Google Business Profile

Страница `/billing/feedback` показывает Google-отзывы, если интеграция
включена и заполнены credentials. Настройки перечислены в `.env.example` с
префиксом `GOOGLE_BUSINESS_PROFILE_`. Получить refresh token можно через
`/admin/integrations/google-business-profile/oauth/start`.

### Автопрогноз заполненности

Команда прогнозирует промежуточные уровни `25`, `50`, `75`, `90` процентов по
истории каждого бункера:

```bash
php artisan bunkers:forecast-fill-levels --dry-run
php artisan bunkers:forecast-fill-levels
```

Scheduler запускает ее ежечасно. На сервере должен быть настроен стандартный
вызов Laravel scheduler.

## Локальный запуск

Минимальный запуск с SQLite:

```bash
cp .env.example .env
touch database/database.sqlite
composer install
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

После этого доступны `/admin` и `/billing`, но бизнес-разделы будут пустыми
или скрытыми, пока в локальной БД нет бизнес-таблиц и данных. Seeder создает
только тестового администратора:

```bash
php artisan db:seed
```

Для одновременного запуска web-сервера, queue listener, логов и Vite:

```bash
composer run dev
```

## Проверки

Перед проверками в WSL используйте `/tmp` для временных файлов и Node.js 22+:

```bash
export TMPDIR=/tmp TEMP=/tmp TMP=/tmp
source ~/.nvm/nvm.sh
nvm use 22
```

Основные команды:

```bash
composer test
./vendor/bin/pint --test
npm run build
git status --short
```

Тесты находятся в `tests/Unit` и `tests/Feature`. В первую очередь они
покрывают tenant scope, demo-переключение БД, права администраторов,
dashboard-агрегации и прогноз заполненности.

## Frontend и сборка

Vite собирает три entrypoint:

- `resources/css/app.css`
- `resources/js/app.js`
- `resources/css/filament/tailadmin/theme.css`

`npm run build` также запускает `scripts/write-build-fingerprint.mjs`.
Скрипт записывает `public/build/build-fingerprint.json`; deploy сравнивает
fingerprint с текущими Filament, Blade, CSS, JS и Vite-входами. Если менялись
эти файлы, коммитьте обновленный `public/build/` вместе с исходниками.

## Deploy

`deploy.sh` рассчитан на shared hosting со split layout:

- Laravel-приложение находится в каталоге `laravel`;
- document root находится рядом в `public_html`;
- `public/` синхронизируется в `public_html/` через `rsync`;
- `public_html/index.php` автоматически патчится для загрузки
  `../laravel/vendor/autoload.php` и `../laravel/bootstrap/app.php`;
- зависимости ставятся через Composer без dev-пакетов;
- по умолчанию выполняются миграции, очистка и прогрев Laravel cache.

Обычный запуск на сервере:

```bash
./deploy.sh
```

Полезные флаги:

```bash
./deploy.sh --no-migrate
./deploy.sh --php php8.4
./deploy.sh --skip-asset-check
```

`--skip-asset-check` предназначен только для аварийного PHP-only deploy:
обычно нужно пересобрать и закоммитить `public/build/`.

## Чек-лист для изменений

1. Найдите затронутые Filament resource, dashboard query и тесты.
2. Для billing-функциональности проверьте `counterparty_id`,
   `district_scope`, read-only режим и demo connection.
3. Для новых или измененных SQL-условий `WHERE` проверьте индексы. Если
   индекса нет, добавьте миграцию или зафиксируйте необходимость изменения
   внешней схемы.
4. При изменении схемы сохраните совместимость с неполной production/demo
   схемой, если задача явно не требует обратного.
5. Запустите релевантные тесты, затем полный `composer test`.
6. Если затронуты build inputs, выполните `npm run build` и проверьте
   изменения в `public/build/`.
