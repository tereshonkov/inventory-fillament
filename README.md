# Inventory-App

Облік матеріальних цінностей (МВО — матеріально відповідальна особа) на **Laravel 13 / PHP 8.3 + Filament 5**. Панель керування — `/admin`, інтерфейс українською. Головна сутність — актив (`Asset`), навколо неї довідники (локації, типи, підрозділи, співробітники), документи руху (надходження, передача, списання) і ролі користувачів (`admin`, `editor`, `viewer`).

## Швидкий старт

```bash
composer setup   # install, .env, key, migrate, npm install + build, git-хуки, браузер для Playwright
composer dev     # сервер + черга + vite одночасно
```

- Розробка локально на **SQLite**, прод — **MySQL**: код і міграції мають працювати на обох.
- Черга (`QUEUE_CONNECTION=database`) потрібна для експорту та сповіщень — у `composer dev` вона вже запускається.
- На Windows-машині розробки PHP може бути не в `PATH` (Laragon) — для скриптів є обгортка `node scripts/php.mjs <args>`.

## Перевірки та тести

Перед кожним комітом git-хук `.githooks/pre-commit` (підключається `npm run hooks:install`) запускає по порядку: скидання кешу конфігурації → Pint (лише змінені файли) → ESLint → PHPUnit → збірка фронта → Playwright. Помилка на будь-якому кроці зупиняє коміт.

| Що | Команда |
| --- | --- |
| PHP-тести | `composer test` або `php artisan test` |
| e2e (Playwright) | `npm run build && npm run test:e2e` |
| JS-лінтер | `npm run lint` |
| Стиль PHP | `vendor/bin/pint <змінені файли>` |

**Тести не повинні торкатися справжньої бази.** Якщо існує `bootstrap/cache/config.php`, Laravel ігнорує змінні оточення тестів і бере БД з `.env` — тоді `RefreshDatabase` та `migrate:fresh` знищили б її. `tests/TestCase.php` і e2e-скрипти тепер зупиняються з поясненням; лікується `php artisan config:clear`. На сервері тести та `composer setup` не запускаємо.

## Документація

- `CLAUDE.md` — архітектура, ролі, команди, угоди й журнал змін проєкту (англійською).
- `docs/` — плани та PRD: `plan-*.md`, `prd-*.md`. Для нових фіч є скіли `/prd` (описати фічу) і `/to-plan` (розбити PRD на фази).
- Нижче — шпаргалка з робочими рішеннями: не просто код, а **чому** він написаний саме так.

---

# Шпаргалка по паттернам Filament

Этот раздел собирает рабочие решения, к которым мы пришли по ходу разработки — не просто код, а **почему** он написан именно так. Полезно, когда через месяц откроешь похожую задачу и не будешь помнить, зачем тут вообще эта строчка.

---

## 1. Ролевая видимость опций в `Select`

**Задача:** editor должен видеть только 4 "простых" статуса актива, admin — все 9 (на случай ручной коррекции данных).

```php
Select::make('status')
    ->options(function () {
        if (auth()->user()->role === UserRole::ADMIN) {
            return collect(AssetStatus::cases())
                ->mapWithKeys(fn (AssetStatus $status) => [$status->value => $status->getLabel()]);
        }

        return [
            AssetStatus::BALANCE->value => AssetStatus::BALANCE->getLabel(),
            AssetStatus::NOT_PUT_IN_TO_OPERATION->value => AssetStatus::NOT_PUT_IN_TO_OPERATION->getLabel(),
            AssetStatus::REPAIR->value => AssetStatus::REPAIR->getLabel(),
            AssetStatus::LOST->value => AssetStatus::LOST->getLabel(),
        ];
    })
    ->required(),
```

**Почему так:** `->options()` принимает не только готовый массив, но и функцию — она выполняется каждый раз при открытии формы, и внутри можно смотреть на текущего юзера. `->options(EnumClass::class)` — удобный "магический" синтаксис, но разворачивает **все** case'ы разом; как только нужно подмножество — приходится строить массив `['значение' => 'подпись']` руками через `->getLabel()`.

---

## 2. Row-level scoping — `getEloquentQuery()`

**Задача:** editor (кладовщик/МВО) должен видеть в списке только те записи, где он custodian — не просто не редактировать чужое, а физически не видеть его в таблице.

```php
public static function getEloquentQuery(): Builder
{
    $user = auth()->user();
    $query = parent::getEloquentQuery();

    return match ($user->role) {
        UserRole::EDITOR => $query->where('custodian_id', $user->employee_id),
        default => $query,
    };
}
```

Для дочерних сущностей (`AssetIncoming`, `AssetTransfer`, `AssetWriteOff`), у которых `custodian_id` лежит не на них самих, а на связанном `Asset` — тот же принцип через `whereHas`:

```php
UserRole::EDITOR => $query->whereHas('asset', fn ($q) => $q->where('custodian_id', $user->employee_id)),
```

**Почему так:** переопределяет метод из родительского класса `Resource`. Это фильтр на уровне самого SQL-запроса, срабатывает раньше, чем Policy — Policy решает "может ли юзер сделать действие над конкретной записью", а это решает "какие записи вообще должны попасть в список".

---

## 3. Условная фильтрация `relationship()` по операции (create vs edit)

**Задача:** при **создании** передачи/списания можно выбрать только актив со статусом "на балансі". Но при **редактировании** той же записи (когда заполняешь `completed_at`) актив уже сменил статус на "проводиться передача/списання" — и с жёстким фильтром форма перестаёт находить его вообще, показывая "The selected asset is invalid".

```php
Select::make('asset_id')
    ->relationship(
        'asset',
        'name',
        modifyQueryUsing: fn ($query, string $operation) => $operation === 'create'
            ? $query->where('status', AssetStatus::BALANCE->value)
            : $query,
    )
    ->searchable(['name', 'inventory_number', 'serial_number'])
    ->required(),
```

**Почему так:** `$operation` — специальный параметр, который Filament сам подставляет в замыкание, если он там объявлен (внедрение зависимостей по имени параметра, через reflection). Значение — `'create'` или `'edit'`, в зависимости от текущей страницы. Ограничение по статусу имеет смысл только при **старте** нового процесса, а не при его завершении.

`->searchable([...])` — отдельный, независимый механизм: определяет **как** искать внутри уже отфильтрованного пула (AJAX-поиск по нескольким колонкам сразу, без предзагрузки всех записей — критично при больших объёмах техники).

---

## 4. Автоматическая смена статуса через lifecycle hooks

**Задача:** пользователь работает только с `AssetTransfer`/`AssetWriteOff` — статус на самом `Asset` не редактируется руками, а меняется как побочный эффект.

Создание записи → статус переходит в промежуточное состояние:

```php
protected function afterCreate(): void
{
    $this->record->asset->update([
        'status' => AssetStatus::TRANSFERRING,
    ]);
}
```

Завершение процесса (заполнили `completed_at`) → статус становится финальным:

```php
protected function afterSave(): void
{
    if ($this->record->wasChanged('completed_at') && $this->record->completed_at !== null) {
        $this->record->asset->update([
            'status' => AssetStatus::TRANSFERRED,
        ]);
    }
}
```

**Почему так:** `afterCreate()`/`afterSave()` — хуки жизненного цикла страниц `CreateRecord`/`EditRecord`, вызываются автоматически после штатного сохранения. `wasChanged('completed_at')` — метод Eloquent, отслеживающий, изменилось ли поле именно в этом сохранении, а не просто "заполнено ли оно вообще" — без этой проверки статус бы переустанавливался на то же значение при каждом повторном сохранении формы (например, поправил `notes` спустя месяц после завершения).

---

## 5. Фильтры в таблице — enum vs relationship

```php
->filters([
    SelectFilter::make('status')->options(AssetStatus::class),
    SelectFilter::make('location')->relationship('location', 'name'),
    SelectFilter::make('type')->relationship('type', 'name'),
    SelectFilter::make('holder')->relationship('holder', 'full_name'),
])
```

**Почему так:** для enum-поля (`status`) — `->options(EnumClass::class)`, разворачивает все case'ы через `HasLabel`. Для полей-связей (`location`, `type`, `holder`) — `->relationship(имя_связи, title_attribute)`, тот же принцип, что и в форме: Filament сам построит список вариантов, подтягивая указанное поле как подпись.

---

## 6. Комбинированное создание двух моделей одной формой

**Задача:** поступление техники (`AssetIncoming`) не должно требовать сначала отдельно создать `Asset`, а потом отдельно указать поступление — актив физически ещё не существует на момент заведения. Нужна **одна** форма, которая создаёт **обе** записи сразу.

Форма — плоский список полей, визуально сгруппированный в два `Section` (это только оформление, на структуру данных не влияет):

```php
Section::make('Asset Fileds')
    ->columns(2)
    ->schema([
        TextInput::make('name')->required(),
        TextInput::make('inventory_number'),
        TextInput::make('serial_number'),
        Textarea::make('asset_notes')->columnSpanFull(), // не 'notes'! см. ниже
        TextInput::make('year')->numeric(),
        Select::make('location_id')->options(fn () => Location::pluck('name', 'id')),
        Select::make('type_id')->options(fn () => AssetType::pluck('name', 'id')),
        Select::make('custodian_id')
            ->options(fn () => Employee::pluck('full_name', 'id'))
            ->default(fn () => auth()->user()->role === UserRole::EDITOR ? auth()->user()->employee_id : null)
            ->required(),
        Select::make('holder_id')->options(fn () => Employee::pluck('full_name', 'id')),
        Hidden::make('status')->default('capitalize'),
    ]),
```

А фактическое разделение на две записи происходит не в форме, а на странице создания — переопределяем `handleRecordCreation()`:

```php
protected function handleRecordCreation(array $data): Model
{
    return DB::transaction(function () use ($data) {
        $assetData = [
            'name' => $data['name'],
            'inventory_number' => $data['inventory_number'],
            'serial_number' => $data['serial_number'],
            'notes' => $data['asset_notes'], // переименование обратно в 'notes'
            'year' => $data['year'],
            'location_id' => $data['location_id'],
            'type_id' => $data['type_id'],
            'custodian_id' => $data['custodian_id'],
            'holder_id' => $data['holder_id'],
            'status' => $data['status'],
        ];

        $asset = Asset::create($assetData);

        $incomingData = [
            'incoming_type' => $data['incoming_type'],
            'source' => $data['source'],
            'document_number' => $data['document_number'],
            'received_at' => $data['received_at'],
            'completed_at' => $data['completed_at'],
            'notes' => $data['notes'],
        ];
        $incomingData['asset_id'] = $asset->id;

        return AssetIncoming::create($incomingData);
    });
}
```

**Почему так:**

- **Форма** — это просто описание того, что видит пользователь; она не привязана жёстко к структуре одной таблицы. Можно смешивать поля любых моделей на одном экране.
- **`asset_notes` вместо `notes`** — у `Asset` и `AssetIncoming` обе модели имеют поле `notes`. Если назвать оба поля формы одинаково, они схлопнутся в один ключ массива `$data`, и одно значение перезапишет другое. Переименование — единственный способ развести их на этапе сбора данных, а при создании `Asset` возвращаем настоящее имя колонки.
- **Транзакция (`DB::transaction`)** — гарантирует атомарность: если создание `AssetIncoming` упадёт после того, как `Asset` уже создан, всё откатится, не оставляя "осиротевший" актив без истории поступления.
- **`Hidden::make('status')`** — поле физически участвует в данных формы (значение `capitalize` подставляется автоматически), но невидимо на экране — пользователь не может случайно выбрать другой статус при поступлении.

---

## 7. `options()` через `pluck()` вместо `relationship()`

```php
Select::make('location_id')
    ->options(fn () => Location::pluck('name', 'id')),
Select::make('type_id')
    ->options(fn () => AssetType::pluck('name', 'id')),
Select::make('custodian_id')
    ->options(fn () => Employee::pluck('full_name', 'id'))
    ->default(fn () => auth()->user()->role === UserRole::EDITOR ? auth()->user()->employee_id : null)
    ->required(),
Select::make('holder_id')
    ->options(fn () => Employee::pluck('full_name', 'id')),
```

**Почему не `->relationship()`, как везде в других формах:** `->relationship('location', 'name')` требует, чтобы метод-связь `location()` существовал **на модели самого ресурса** — а в контексте `AssetIncomingForm` это `AssetIncoming`, а не `Asset`. Такой связи на `AssetIncoming` нет и не должно быть (это чужие поля, временно оказавшиеся в одной форме ради UX). Попытка использовать `->relationship()` тут падает с `LogicException: relationship does not exist`.

Решение — обойти встроенный relationship-механизм полностью: `->options()` с обычным замыканием, которое напрямую обращается к нужной модели через `pluck($label, $key)` — возвращает массив вида `[id => 'подпись']`, ровно то, что ожидает `Select`, без всякой привязки к тому, есть ли связь на текущей модели.

`custodian_id` дополнительно получает `->default()` с проверкой роли — если залогинен editor, по умолчанию подставляется он сам (кто ж ещё принимает товар на складе), но поле остаётся редактируемым.

---

## 8. Умный редирект после сохранения — трейт вместо дублирования

**Задача:** по умолчанию Filament после сохранения на Edit-странице никуда не перенаправляет, остаётся на той же форме. Хотелось: если у ресурса есть View-страница — вести туда, если нет — на список. И применить это на девяти разных `EditXxx.php` файлах, не копируя одну и ту же логику девять раз.

```php
<?php

namespace App\Filament\Concerns;

/**
 * @mixin \Filament\Resources\Pages\EditRecord
 */
trait RedirectsAfterSave
{
    protected function getRedirectUrl(): string
    {
        if ($this->getResource()::hasPage('view')) {
            return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
        }

        return $this->getResource()::getUrl('index');
    }
}
```

Подключение в каждом `EditXxx.php`:

```php
use App\Filament\Concerns\RedirectsAfterSave;

class EditAsset extends EditRecord
{
    use RedirectsAfterSave;

    protected static string $resource = AssetResource::class;
    // ...
}
```

**Почему так:**

- **`getRedirectUrl()`** — метод, который Filament вызывает после успешного сохранения на Edit-странице; переопределяем его, чтобы задать логику вместо дефолтного "остаться на месте".
- **`$this->getResource()`** — уже существует на базовом `EditRecord`, возвращает класс ресурса текущей страницы. `::hasPage('view')` и `::getUrl(...)` — статические методы на самом Resource, ничего писать с нуля не пришлось.
- **Трейт, а не копипаста в девять файлов** — логика одна и та же для всех ресурсов, меняется только контекст (`$this`). Если завтра понадобится другое правило редиректа — правится в одном месте.
- **`@mixin` в PHPDoc** — трейт физически не знает заранее, в какой класс его подключат, поэтому IDE не может сама подтвердить, что там будет метод `getResource()`. `@mixin` — подсказка для IDE "считай, что этот трейт подмешан в `EditRecord`", убирает ложное предупреждение об отсутствующем методе. На выполнение кода не влияет.

---

## 9. Асинхронный экспорт в Excel + database-уведомления

**Задача:** экспорт отфильтрованного списка Assets в Excel, без блокировки других пользователей — при синхронном выполнении (`QUEUE_CONNECTION=sync`) экспорт занимает PHP-воркер на всё время генерации файла; при нескольких одновременных запросах это создаёт задержки для всех остальных, кто просто открывает страницы панели.

### Команды — фундамент (очередь + служебные таблицы)

```
php artisan make:queue-batches-table   # часто уже есть в свежих проектах (объединена с create_jobs_table)
php artisan make:notifications-table
php artisan vendor:publish --tag=filament-actions-migrations
php artisan migrate
```

`vendor:publish` с этим тегом публикует не одну таблицу, а три разом — `exports`, `imports`, `failed_import_rows` (пакет `filament/actions` несёт связанную пару функциональности, экспорт и импорт, одним набором миграций). Лишние сейчас не используются, но ничем не мешают.

### `.env`

```
QUEUE_CONNECTION=database
```

Вместо выполнения задачи немедленно внутри запроса — складывает в таблицу `jobs`, откуда её забирает отдельный процесс:

```
php artisan queue:work
```

На локальной разработке держится в отдельном терминале; на реальном сервере — через Supervisor, чтобы переживал перезапуски.

### Включение уведомлений — `AdminPanelProvider.php`

```php
->databaseNotifications()
->databaseNotificationsPolling('30s')
```

Уведомления **тоже** идут через очередь — без запущенного `queue:work` они будут тихо копиться в таблице `notifications`, не отправляясь колокольчику.

### Генерация экспортёра

```
php artisan make:filament-exporter Asset
```

### Готовый `AssetExporter.php` — с двумя нетривиальными местами

```php
class AssetExporter extends Exporter
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Назва'),
            ExportColumn::make('inventory_number')->label('Інвентарний номер'),
            ExportColumn::make('location.name')->label('Місцезнаходження'),
            ExportColumn::make('custodian.full_name')->label('МВО'), // не custodian.id!
            ExportColumn::make('status')
                ->label('Статус')
                ->formatStateUsing(fn ($state) => $state->getLabel()), // обязательно для enum-полей
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "Експорт активів завершено. Експортовано {$export->successful_rows} "
            . self::pluralizeRows($export->successful_rows) . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " Не вдалося експортувати {$failedRowsCount} "
                . self::pluralizeRows($failedRowsCount) . '.';
        }

        return $body;
    }

    private static function pluralizeRows(int $count): string
    {
        return match (true) {
            $count % 10 === 1 && $count % 100 !== 11 => 'рядок',
            in_array($count % 10, [2, 3, 4]) && ! in_array($count % 100, [12, 13, 14]) => 'рядки',
            default => 'рядків',
        };
    }
}
```

### Подключение кнопки — `AssetsTable.php`

```php
use App\Filament\Exports\AssetExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;

->headerActions([
    ExportAction::make()
        ->label('Експортувати')
        ->exporter(AssetExporter::class)
        ->formats([ExportFormat::Xlsx]),
])
```

**Почему так:**

- **`ExportAction` по умолчанию уважает текущие фильтры/сортировку таблицы** — отфильтровал список, нажал "Експортувати", в файле именно то, что видно на экране, а не вся таблица целиком.
- **Enum-поля не резолвятся в `ExportColumn` автоматически**, в отличие от `TextColumn`/`Select` в форме — без `->formatStateUsing()` попытка экспорта падает с ошибкой `Return value must be of type ?string, App\Enums\AssetStatus returned`. Известный, стабильно воспроизводимый нюанс пакета на разных его версиях.
- **Своя функция плюрализации** вместо `Str::of('row')->counted()` — украинский язык (как и русский) требует трёх форм числительных в зависимости от последней цифры и от того, попадает ли число в диапазон 11-14, а не двух, как в английском. Встроенный Laravel-хелпер `counted()` сделан под двухвариантную (en) логику и даёт грамматически неверный результат на славянских языках.
- **Импорт `ExportFormat` — частая ловушка автодополнения IDE.** Правильный путь `Filament\Actions\Exports\Enums\ExportFormat`; в IDE легко случайно выбрать похожий класс из `Filament\Actions\Exports\Enums\Contracts\ExportFormat`, который не тот — приводит к ошибке `Undefined constant`.

---

## 10. Длинные названия: `short_name` в заголовках, полное — в карточке

**Задача:** название актива бывает в несколько абзацев. В заголовке страницы и в компактных виджетах оно должно занимать одну строку и не более 4 слов.

```php
// Asset
protected function shortName(): Attribute
{
    return Attribute::get(fn (): string => (string) Str::of((string) $this->name)
        ->squish()          // абзацы и переносы -> один пробел
        ->words(4, '…')     // не больше 4 слов
        ->limit(60, '…'));  // страховка от одного "слова" без пробелов
}
```

Заголовок страницы просмотра (`ViewAsset::getHeading()`) и очередь на дашборде показывают `$asset->short_name`; полное название — в инфолисте:

```php
TextEntry::make('name')
    ->label('Повна назва')
    ->inlineLabel(false)                       // см. ниже
    ->lineClamp(3)
    ->tooltip(fn (string $state): string => $state)
    ->columnSpanFull(),
```

**Почему так:** название — не идентификатор, а описание; идентификатор (инвентарный номер) стоит рядом, копируемый. `inlineLabel(false)` нужен для длинных записей на всю ширину: при подписи в строку Filament отдаёт подписи примерно треть строки, и значение уезжает далеко вправо. Короткие факты (МВО, серийный номер) остаются с подписью в строку.

---

## 11. Дашборд — своя страница с явным порядком виджетов

```php
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Огляд майна';
    protected static ?string $navigationLabel = 'Огляд майна';

    public function getWidgets(): array
    {
        return [StatsOverview::class, AssetActivityChart::class, AssetStatusChart::class, AssetsNeedingAction::class];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 2];
    }
}
```

**Почему так:**

- Страница лежит в `app/Filament/Pages` и подхватывается `discoverPages()`; список виджетов живёт на странице, а не в `->widgets([...])` панели — порядок задан явно, а не случайностью `$sort`.
- **Все виджеты по умолчанию опрашивают сервер каждые 5 секунд** (`wire:poll.5s`), и на каждом опросе выполняется их запрос. Для данных, которые так часто не меняются, ставим `protected ?string $pollingInterval = null;` (у таблицы-виджета опроса по умолчанию нет).
- Виджеты **ленивые**: подгружаются отдельным запросом, когда попадают в область видимости. В тестах их нужно дожидаться.
- `StatsOverview` с четырьмя плитками: `protected int|array|null $columns = ['default' => 2, 'md' => 4]` — на телефоне 2×2 вместо четырёх высоких карточек.

---

## 12. Таблица-виджет со скоупом по роли и приоритетом

**Задача:** очередь «Потребує уваги» — активы в статусах, где нужно действие. Editor должен видеть только свои, а порядок — по срочности, а не по дате.

```php
->query(fn (): Builder => AssetResource::getEloquentQuery()   // тот же скоуп, что в списке активов
    ->whereIn('status', self::STATUSES)
    ->with('custodian')
    ->tap(fn (Builder $query) => self::orderByPriority($query))
    ->orderBy('updated_at')                                   // внутри группы — кто ждёт дольше
    ->orderBy('id'))
->recordUrl(fn (Asset $record) => AssetResource::getUrl('view', ['record' => $record]))
```

Группа срочности — переносимое выражение `CASE` (работает и на SQLite, и на MySQL):

```php
$sql = 'case status';
foreach (self::PRIORITY_GROUPS as $rank => $group) {
    foreach ($group as $status) {
        $sql .= ' when ? then '.$rank;
        $bindings[] = $status->value;
    }
}
$query->orderByRaw($sql.' else '.count(self::PRIORITY_GROUPS).' end', $bindings);
```

**Почему так:** виджеты по умолчанию не знают о ролях — `Asset::query()` дал бы editor'у общие цифры. Повторное использование `AssetResource::getEloquentQuery()` гарантирует, что правило видимости одно на весь проект. Остальные виджеты (плитки склада, графики) по решению остаются общими.

---

## 13. Графики: что можно, а чего нельзя

Filament передаёт опции Chart.js как JSON, поэтому **функции-колбэки в `getOptions()` невозможны** (нельзя `ticks.callback`, `tooltip.filter`). Всё, что обычно делается колбэком, решается данными или опциями:

- **Целые деления оси:** `'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]` — счётчики документов не бывают дробными. Для счётчиков — прямые отрезки (`'tension' => 0`): сглаживание рисует значения между месячными точками, которых нет.
- **Длинные подписи оси** переносим данными: подпись может быть массивом строк (`['проводиться', 'передача — 2']`) — Chart.js выводит её в две строки. Иначе на телефоне подпись обрезается слева.
- **Тонкие закруглённые «полоски» на светлой дорожке** — два набора данных на одних позициях:

```php
$pill = ['borderRadius' => 999, 'borderSkipped' => false, 'barThickness' => 12,
         'grouped' => false,       // рисовать друг на друге, а не рядом
         'minBarLength' => 10];    // даже один актив рядом с сотнями остаётся виден
// набор 1: значения, 'order' => 1 (сверху)      набор 2: «дорожка» = самое большое значение, 'order' => 2 (позади)
// 'scales' => ['x' => ['min' => 0, 'max' => $longest, 'display' => false]], 'plugins' => ['tooltip' => ['enabled' => false]]
```

Дорожка равна самому большому статусу, а не общему числу активов: иначе при преобладающем «на балансі» остальные статусы превращаются в волоски.

- **Цвета берём из enum, а не из второй карты в виджете:** `AssetStatus::getChartColor()` переводит имя цвета бейджа (`success`, `danger`…) в hex через `CHART_COLORS`. Статус выглядит одинаково и в бейдже, и на графике.
- `getData()` и `getOptions()` нужны одни и те же счётчики — запрос делается один раз на экземпляр виджета (приватное свойство-кэш).
- Графики в одном ряду выравниваются по высоте CSS-правилом `.fi-wi-chart > .fi-section { height: 100% }`, а описания под заголовками держим в одну строку.

---

## 14. Вкладки истории на карточке: счётчики, пустые состояния, не lazy

```php
class IncomingsRelationManager extends RelationManager
{
    protected static ?string $title = 'Отримано';
    protected static ?string $modelLabel = 'надходження';
    protected static ?string $pluralModelLabel = 'надходження';
    protected static bool $isLazy = false;      // lazy-блок — белая пустота, пока не прокрутишь к нему

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->incomings()->count();

        return $count > 0 ? (string) $count : null;   // пустая вкладка — без цифры
    }
    // table(): ->heading('') + emptyStateIcon / emptyStateHeading / emptyStateDescription
}
```

**Почему так:** пустая вкладка по умолчанию показывает «Не знайдено asset incomings» с английским названием модели (и английский `aria-label` у таблицы) — `emptyState*` и `$modelLabel` это исправляют. `->heading('')` убирает заголовок таблицы, дублирующий название вкладки.

---

## 15. CSS темы: правила привязываем к странице

Файл `resources/css/filament/admin/theme.css` пересобирается `npm run build` (без этого на сервере стили не появятся). Страницы Filament имеют классы `fi-resource-list-records-page`, `fi-resource-view-record-page` — по ним ограничиваем правила:

- рамка «вкладки + таблица как одна карточка» нужна только на страницах списков; без привязки правило `:has(.fi-sc-tabs)` на странице просмотра заворачивало **все** карточки инфолиста в одну большую;
- на странице просмотра карточкой становится только блок вкладок истории;
- на телефоне (`max-width: 767px`) очередь «Потребує уваги» ставится после плиток и до графиков через `order` у `.fi-wi-table` / `.fi-wi-chart`; вкладки получают меньший отступ, чтобы три штуки со счётчиками помещались в ширину.

---

## 16. Тесты: что проверено на практике

- **Кэш конфигурации и стёртая база.** `bootstrap/cache/config.php` заставляет Laravel игнорировать переменные окружения — тесты (`RefreshDatabase`) и подготовка e2e (`migrate:fresh`) работали на базе из `.env`. Теперь `tests/TestCase.php` отказывается стартовать не на SQLite в памяти, `assertE2eDatabase()` проверяет, что e2e идёт на `database/e2e.sqlite`, а хук первым шагом делает `config:clear`.
- **Виджеты и менеджеры связей** тестируются через `Livewire::test(Widget::class)`; защищённые `getData()` / `getOptions()` вызываются через `ReflectionMethod`; таблицы — `assertCanSeeTableRecords($records, inOrder: true)`. Страницы ресурсов — обычными HTTP-тестами с `$this->withoutVite()`.
- **Тест должен падать без изменения.** Новое поведение проверяем так: временно возвращаем старый файл (`git stash`) — тест обязан упасть, иначе он проверяет не то.
- **Playwright:** один воркер (PHP-сервер однопоточный), общий `expect`-таймаут 10 с, перед проверкой раскладки дожидаемся всех ленивых виджетов; сессии admin и editor сохраняются один раз в `global-setup`; список активов разбит по 5 строк — нужную запись ищем через поиск таблицы (`searchTable()`). Данные для сценариев задаёт `E2ESeeder`.

---

# Стан проєкту та історія рішень

## Статус проекту

**Ядро:** Employee/User з ролями, row-level scoping, Policy — повністю готово й протестовано.

**Перша хвиля:** Asset з довідниками (Location/AssetType/Department), формами, фільтрами — готово.

**Друга хвиля:** AssetIncoming/AssetTransfer/AssetWriteOff — двофазні процеси, автоматична зміна статусу, Policy зі scoping через Asset — готово.

**Relation Manager'и:** три вкладки історії на Asset (зі лічильниками, українськими порожніми станами), одна на Employee — готово, включно з `isReadOnly()` на View-сторінках.

**Картка активу:** назва в заголовку (до 4 слів), у шапці — копійований інвентарний номер, статус, локація, тип, рік; секції з довгими текстами; окрема картка історії; перевірено на 1440 / 1024 / 390 px — готово.

**Дашборд «Огляд майна»:** плитки складу, графік активності, розподіл за статусом, черга «Потребує уваги» (зі scoping за роллю) — готово, див. розділ нижче.

**Імпорт/експорт:** асинхронний експорт Assets в Excel, Filament Importer (CSV), імпорт xlsx-документів надходження/переміщення та акта введення в експлуатацію — готово.

**Інфраструктура:** черга (`database`), database-сповіщення, git-хук з перевірками, PHPUnit + Playwright (e2e), ESLint — готово й перевірено наскрізно (клік → черга → файл → сповіщення; коміт → усі перевірки).

**Свідомо не робимо:**

- Фільтри в окремих списках AssetIncomings/AssetTransfers/AssetWriteOffs — записи переважно переглядають через вкладки на картці Asset, окремі фільтри визнані зайвими
- Смуга-роздільник сайдбару — після розширення таблиці (`maxContentWidth`) необхідність відпала
- Плитки складу рахуються по захардкоджених типах і складах (`Склад-327`, `Склад-915`) і показують загальні цифри без scoping за роллю — свідоме рішення; зі scoping працює лише черга «Потребує уваги»

## Майбутні фази (окремо, не зараз)

### Імпорт даних — реалізовано й перевірено на реальних 849 рядках

> **Стан зараз:** цей CSV-імпорт працює, але кнопка `ImportAction` на списку активів закоментована в `ListAssets`; у інтерфейсі користувачам доступні xlsx-імпорти документів (розділ нижче).

Повторюваний інструмент через вбудований Filament `Importer` — Filament сам показує користувачу екран зіставлення колонок при завантаженні файлу, тож не потрібно писати окремий парсер під структуру таблиці кожного відділу.

Три рішення, прийняті свідомо:

1. **Доступ — тільки admin.** Експорт (тільки читання) відкритий усім, імпорт (запис у БД) — та ж логіка, що обмежувала пряме створення Asset тільки для admin, тільки з ще вищими ставками.
2. **"Брудні" рядки — тільки два критерії.** Порожнє `name` або нерезолвний `status` → рядок падає в `failed_import_rows` через `RowImportFailedException`. Все інше (location/type/holder не знайдені в довідниках) — безпечний `null`, рядок все одно створюється.
3. **Довідники — тільки існуючі записи, без автостворення.** Автостворення на льоту неминуче призводить до дублікатів через різницю в написанні — довідник перестає бути єдиним джерелом правди.

**Ключова архітектурна деталь:** складна логіка (пошук у довідниках, дозапис у спільний `notes`, перевірка `status`) свідомо зібрана не в окремих `castStateUsing()` на кожній колонці, а разом в `resolveRecord()` — обробники окремих колонок не бачать значення сусідніх полів, а тут потрібно одночасно читати `location`+`type` і одночасно писати в спільний `notes`, не затираючи вже записане.

```php
public function resolveRecord(): Asset
{
    $notes = trim($this->data['notes'] ?? '');
    $locationsText = trim($this->data['location'] ?? '');

    $status = collect(AssetStatus::cases())
        ->first(fn (AssetStatus $case) => $case->getLabel() === trim($this->data['status'] ?? ''));

    if (! $status) {
        throw new RowImportFailedException("Невідомий статус: «{$statusText}»");
    }

    $locationId = null;
    if ($locationsText !== '') {
        $firstLocation = trim(explode(',', $locationsText)[0]);
        $locationId = Location::where('name', $firstLocation)->value('id');

        if (str_contains($locationsText, ',')) {
            $notes = trim(($notes ? $notes . "\n\n" : '') . "[З імпорту, локація: {$locationsText}]");
        }
    }
    // ... аналогічно type, holder

    return new Asset([
        'name' => $name,
        'status' => $status,
        'notes' => $notes,
        'location_id' => $locationId,
        'custodian_id' => $this->options['custodian_id'], // з форми вибору, не з файлу
        // ...
    ]);
}
```

`custodian_id` — не з файлу, а обирається один раз при завантаженні через `getOptionsFormComponents()` (список Employee з роллю editor), однакове значення для всіх рядків файлу. Доступ до `ImportAction` обмежений тільки `admin` через `->visible()`.

### Знайдений і виправлений баг — `fillRecord()` перезаписував нашу власну логіку

**Симптом:** усі 849 рядків падали з загальною "Системна помилка, зверніться до служби підтримки" — не з нашим власним текстом з `RowImportFailedException`, а з чимось іншим. У `storage/logs/laravel.log` знайшлася справжня причина:

```
"поступає на баланс" is not a valid backing value for enum App\Enums\AssetStatus
```

**Причина:** базовий клас `Importer` має власний, вбудований метод `fillRecord()`, який ми ніколи не перевизначали. Його дефолтна поведінка — пройтись по кожному `ImportColumn` з `getColumns()` і **самостійно, ще раз**, записати сире значення з файлу прямо на модель, нічого не знаючи про те, що `resolveRecord()` вже все зібрав вручну. У результаті: `resolveRecord()` повертав `Asset` з правильно резолвленим `status`, а одразу слідом дефолтний `fillRecord()` **перезаписував** його сирим текстом лейбла з файлу — намагаючись напряму засунути `"поступає на баланс"` в поле, закастоване на enum, де очікується `'capitalize'`, а не текст.

Та сама логіка тихо могла ламати `location_id`/`type_id`/`holder_id` — без явної помилки (там немає строгого enum-касту), просто підміняючи вже правильно резолвлені значення сирим текстом.

**Рішення:**

```php
public function fillRecord(): void
{
    // Пусто — resolveRecord() вже повністю зібрав запис зі всіма полями
}
```

Важливий нюанс сигнатури — метод має бути **`public`**, не `protected`. Батьківський клас оголошує його публічним, а PHP при перевизначенні дозволяє розширювати видимість, але не звужувати — спроба поставити `protected` дає `Method ... is not compatible` від IDE ще до запуску.

**Ще два уроки з цієї сесії дебагу, варті запам'ятати:**

- **Черга кешує код у пам'яті.** Правки в enum/класах не підхоплюються запущеним `queue:work`, поки його не перезапустити — інакше тестуєш код, який вже не відповідає файлам на диску.
- **CSV — єдиний формат, який приймає нативний `Importer`.** На відміну від `Exporter` (`->formats([ExportFormat::Xlsx])`), у імпорту такого методу немає взагалі. З Google Таблиць — Файл → Завантажити → CSV, і саме активна вкладка потрапляє у файл, решта вкладок ігнорується.

**Перевірено на реальному файлі** — 849 рядків з реальної Google Таблиці, всі три знайдені баги (CSV/Excel, кеш черги, `fillRecord()`) виправлені по ходу, фінальний прогін пройшов успішно.

### Імпорт xlsx-документів — реалізовано (два сервіси, без тестів)

Окрім CSV-імпорту через Filament `Importer`, у інтерфейсі працюють два імпорти реальних документів у форматі xlsx. Саме вони зараз доступні користувачам; кнопка `ImportAction` на списку активів у `ListAssets` закоментована.

| Де | Кнопка | Сервіс | Що робить |
| --- | --- | --- | --- |
| Список «Надходження» | «Завантажити документ» (файл + МВО + локація, за замовчуванням `Склад-327`) | `IncomingDocumentImporter` | Визначає тип документа: **накладна** (`НАКЛАДНА` у клітинці A9) → кожна одиниця стає `Asset` + `AssetIncoming` (від складу) і отримує статус «не введено»; **акт внутрішнього переміщення** (`Акт внутрішнього переміщення` у A7) → `Asset` + `AssetIncoming` (з підрозділів) зі статусом «на балансі», рядки з уже існуючим `serial_number` пропускаються |
| Список «Активи» | «Введення в експлуатацію» | `AssetActivationImporter` | Читає блоки **акта введення в експлуатацію**, за серійним номером знаходить актив, записує інвентарний номер і переводить у «на балансі»; рядки без серійного номера (`б/н`) або без знайденого активу повертає як «потребує уваги» |

**Що треба пам'ятати:**

- Парсинг прив'язаний до **координат клітинок і текстових маркерів** конкретних форм документів (рядок початку таблиці, колонки A/B/C/H/P/Q/AA/AC/AL…). Змінилась форма документа — імпорт мовчки читатиме не те; перевіряйте на реальному файлі.
- Кількість більше одиниці розгортається в стільки окремих активів; для акта переміщення серійний номер отримує лише перша одиниця.
- Завантажені файли тимчасово лежать у `storage/app/private/temp-imports` (диск `local`) і не видаляються автоматично.
- Для цих сервісів немає автотестів — відомий пробіл (див. «Відомі проблеми»).

### Відкрите питання — захист від дублів при повторному імпорті

Зараз `resolveRecord()` завжди створює новий `Asset`, нічого не перевіряючи — повторне завантаження того ж файлу (наприклад, випадково двічі) створить повний набір дублікатів.

Обговорений, але ще не прийнятий підхід: перевіряти дублікат тільки по `serial_number`, коли він не порожній (найбільш природно унікальний ідентифікатор реальної техніки) — `inventory_number` свідомо не годиться для цього (може повторюватись, номенклатурний номер), а комбінація кількох полів одразу створює дві протилежні проблеми: занадто строго ловить справжні дублі (будь-яка розбіжність в одному полі — і захист не спрацює) і водночас занадто м'яко там, де полів бракує (порожній serial_number + випадково співпалий inventory_number різних фізичних одиниць одного типу).

Якщо `serial_number` порожній — рішення поки не прийняте: чи створювати без перевірки (ризик пропустити дубль), чи якось інакше. Потребує подальшого обмірковування наступного разу.

**Обране на сесії:** якщо дублікат знайдено (по serial_number) — рядок тихо пропускається (`resolveRecord()` повертає `null`), без падіння в `failed_import_rows`.

### Дашборд «Огляд майна» — реалізовано

Сторінка `app/Filament/Pages/Dashboard.php`; порядок віджетів: цифри → графіки → черга (на телефоні черга йде одразу після цифр). Рішення за патернами 11–13.

| Віджет | Що показує | Scoping за роллю |
| --- | --- | --- |
| `StatsOverview` «Наявність на складах» | чотири плитки (ПК, Ноутбук, Робоча станція, Монітор): активи «на балансі» на `Склад-327` і `Склад-915`, завжди чотири плитки, відсутній тип = «0 шт» | ні (загальні цифри, свідомо) |
| `AssetActivityChart` | лінія: кількість документів надходження та передачі за 6 місяців (документи, не активи) | ні |
| `AssetStatusChart` | горизонтальні «полоски» за статусами на світлій доріжці, кількість у підписі, кольори як у бейджів | ні |
| `AssetsNeedingAction` «Потребує уваги» | активи у статусах: передача/списання тривають → втрачено → не введено → ремонт; всередині групи — хто чекає довше; по 5 рядків, клік відкриває картку | **так** (`AssetResource::getEloquentQuery()`) |

**Нюанси:**

- Назви типів і складів у `StatsOverview` зашиті в коді (`TYPES`, `WAREHOUSES`). Якщо на проді вони називаються інакше, плитки покажуть «0 шт».
- Вигляд черги на телефоні: колонки «Інв. №» та «МВО» приховані, «Очікує» прокручується всередині картки.
- Старий віджет `NotIntroducedAssets` видалено: його замінила черга. «Ремонт» у порядку черги стоїть останнім, бо власник його не позначав.

### З плану по ролях — що зроблено, а що чекає потреби

Раніше планувалось окремо для ролей; зараз:

- **Зроблено:** «Потребує уваги» зі scoping (editor бачить лише свої активи, admin/viewer — усі). Це черга за статусами активів, а не за незавершеними документами.
- **Не зроблено, чекає реальної потреби:** розбивка за статусом тільки для editor'а; зведення по МВО (розподіл активів між editor'ами); **Log Viewer** (плагін `achyutn/filament-log-viewer`, показує `laravel.log` у панелі); **невдалі спроби входу** (listener на `Illuminate\Auth\Events\Failed`, запис у той самий `laravel.log`).
- **MCP/AI-інтеграція** — офіційний `laravel/mcp`, підтверджено робочий, окремий етап поверх готового ядра.

## Відомі проблеми (TODO)

Повний актуальний список — у `CLAUDE.md` (розділ «Known issues»). Коротко:

- **Захист від дублів при повторному імпорті Asset не реалізований** — див. розділ вище; рішення обговорене частково, потребує додаткового обдумування для порожнього `serial_number`.
- **Автотестів немає** для імпортів/експорту, xlsx-сервісів (`IncomingDocumentImporter`, `AssetActivationImporter`), форм створення/редагування і Policy. Покрито: фабрики/сидер, картка активу, вкладки історії, дашборд (сторінка і всі чотири віджети), e2e: smoke, картка, дашборд, черга, адаптив.
- У таблиці «Майно у користуванні» на сторінці співробітника рік усе ще з роздільником тисяч («2 024»); виправлено тільки на картці активу.
- ~45 старих PHP-файлів не відповідають Pint (хук перевіряє лише змінені файли).
- `User::canAccessPanel()` повертає `true` для всіх; `DatabaseSeeder` створює лише тестового користувача.

## Виправлено

**`EditAssetIncoming` использовал ту же форму, что и `CreateAssetIncoming`.** `AssetIncomingForm` содержит смешанные поля двух моделей (`Asset` + `AssetIncoming`) — это нормально работает при создании благодаря кастомному `handleRecordCreation()`, но на странице Edit Filament по умолчанию брал ту же самую форму и пытался сделать обычный `$record->update($data)` на модели `AssetIncoming`, у которой физически нет полей вроде `name`/`custodian_id`/`location_id`. Из-за этого редактирование существующей записи поступления не сохранялось.

**Решение:** переопределили `form()` прямо в `EditAssetIncoming.php`, отдельно от общей формы ресурса — там только реальные поля `AssetIncoming`. Заодно добавили `afterSave()` — двухфазную логику завершения поступления (заполнение `completed_at` автоматически переводит связанный `Asset` в статус "не введено" або "на балансі", в залежності від `incoming_type`), яку раніше не реалізували взагалі.

---

**`AssociateAction` на `hasMany`-зв'язку з нестандартним іменем не міг знайти зворотний зв'язок.** На `AssetsHolderRelationManager` (`Employee hasMany Asset` через `holder_id`, метод `assetsHolder()`) кнопка "Підключити" падала з `Call to undefined method App\Models\Asset::employee()`.

**Причина:** `AssociateAction` для `hasMany`-зв'язку сам намагається вгадати ім'я зворотної (`belongsTo`) зв'язки на пов'язаній моделі — бере коротке ім'я класу власника (`Employee`) і пробує викликати метод з такою ж назвою в однині: `Asset::employee()`. У нас ця зв'язка називається не `employee()`, а `holder()` (свідомо, щоб розрізняти роль custodian/holder) — вгадування Filament не влучило.

**Рішення:**

```php
class AssetsHolderRelationManager extends RelationManager
{
    protected static string $relationship = 'assetsHolder';

    protected static ?string $inverseRelationship = 'holder'; // явно, замість вгадування
    // ...
}
```

Загальний висновок: якщо назва `belongsTo`-методу на пов'язаній моделі не збігається з коротким ім'ям класу власника (в однині) — `$inverseRelationship` доведеться вказувати явно на кожному такому Relation Manager'і. Це не помилка в наших назвах методів (`holder()`/`custodian()` — обидва осмислені й правильні для домену), просто конвенція автовгадування Filament занадто наївна для такого випадку.

---

**Фільтрація `AssociateAction` через `recordSelect()`+`modifyQueryUsing()` — неправильний і небезпечний підхід.** Початкова спроба обмежити список активів для прикріплення тільки статусом "на балансі":

```php
AssociateAction::make()
    ->recordSelect(
        fn (Select $select) => $select->modifyQueryUsing(
            fn ($query) => $query->where('status', AssetStatus::BALANCE->value)
        )
    ),
```

падала з `Method Select::modifyQueryUsing does not exist` — цей параметр існує тільки в парі з `->relationship()` на звичайному `Select`, а не на "голому" `$select` всередині `recordSelect()`.

Але важливіше — навіть виправлений синтаксично, цей підхід був би **не про безпеку, а тільки про відображення**. Документація Filament прямо попереджає: фільтрація через `modifyQueryUsing()`/`recordSelect()` впливає лише на те, що видно у списку вибору. Це не межа безпеки — користувач, який втрутиться у відправлені дані форми напряму, все одно зможе прикріпити запис, виключений з видимого списку.

**Правильне рішення** — окремий метод прямо на самому екшені, який реально обмежує, що можна прикріпити, а не тільки що видно:

```php
use Illuminate\Database\Eloquent\Builder;

AssociateAction::make()
    ->recordSelectSearchColumns(['name', 'inventory_number', 'serial_number'])
    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('status', AssetStatus::BALANCE->value)),
```

Імпорт `Builder` — саме `Illuminate\Database\Eloquent\Builder` (Eloquent-версія, працює з моделями), не `Illuminate\Database\Query\Builder` (низькорівневий SQL-білдер) — обидва класи мають однакову коротку назву, IDE легко підставляє не той варіант в автодоповненні.

**Важливе уточнення — те саме слово `modifyQueryUsing` означає різні речі залежно від місця виклику.** Легко переплутати з паттерном 3 вище (`AssetTransferForm`/`AssetWriteOffForm`), де `modifyQueryUsing` теж використовується — але там це **реальний, серверний захист**: Filament перевіряє відправлені значення саме проти цього запиту, записи поза ним відхиляються при валідації. Це працює тому, що там `modifyQueryUsing` — іменований аргумент методу `->relationship()` на звичайному `Select` у формі ресурсу.

А от `modifyQueryUsing`, викликаний вручну на об'єкті `$select` всередині `AssociateAction::make()->recordSelect(fn ($select) => $select->modifyQueryUsing(...))` — це вже **не** валідація поля форми (`AssociateAction` працює через окремий механізм, не через звичайний цикл валідації ресурсу), тому й не є межею безпеки, лише впливає на відображення. Звідси і правило:

| Де                                                                              | Метод                                           | Чи реальний захист                                             |
| ------------------------------------------------------------------------------- | ----------------------------------------------- | -------------------------------------------------------------- |
| `Select::make(...)->relationship(..., modifyQueryUsing: ...)` у звичайній формі | `modifyQueryUsing` як аргумент `relationship()` | Так — Filament валідує відправлене значення проти цього запиту |
| `AssociateAction`/`AttachAction` (і bulk-варіанти)                              | `->recordSelectOptionsQuery(...)`               | Так — спеціально призначений для цього                         |
| `AssociateAction`/`AttachAction` через `->recordSelect(fn ($select) => ...)`    | Що завгодно, включно з `modifyQueryUsing`       | **Ні** — тільки відображення, легко обійти                     |

`AssetTransferForm`/`AssetWriteOffForm` (патерн 3) чіпати не треба — вони вже безпечні як є, там перший рядок таблиці.

---

**Тесты и подготовка e2e стирали базу из `.env`.** `RefreshDatabase` и `migrate:fresh` в подготовке Playwright выполнялись на локальной MySQL, а не на тестовой SQLite — все таблицы пересоздавались.

**Причина:** закэшированная конфигурация (`bootstrap/cache/config.php`) — Laravel при её наличии не читает переменные окружения, поэтому `DB_CONNECTION=sqlite` из `phpunit.xml` и из e2e-окружения не применялось.

**Решение:** `tests/TestCase.php` отказывается стартовать не на SQLite в памяти; `assertE2eDatabase()` проверяет e2e-базу перед `migrate:fresh` и перед запуском сервера; pre-commit хук первым шагом выполняет `config:clear`. Подробности — паттерн 16.

---

**Виджеты дашборда опрашивали сервер каждые 5 секунд.** У Stats Overview и графиков по умолчанию `wire:poll.5s`; график активности при этом на каждом опросе делал 12 запросов. Теперь `$pollingInterval = null`, а график — два запроса (паттерны 11 и 13).

---

**Год актива отображался как «2 024».** `TextEntry::numeric()` форматирует число с разделителем тысяч; на карточке актива убран. В таблице «Майно у користуванні» на странице сотрудника остаётся (см. «Відомі проблеми»).

---

**Карточки инфолиста оказывались внутри одной большой карточки.** Правило темы `:has(.fi-sc-tabs)`, написанное для списков, срабатывало и на странице просмотра. Теперь оно привязано к страницам списков (паттерн 15).

## Общий принцип, который тянется через всё

Разделение двух разных вопросов, которые легко перепутать:

- **Policy** — может ли юзер сделать действие над конкретной, уже существующей записью
- **`getEloquentQuery()`** — какие записи вообще попадают в список
- **`modifyQueryUsing`/`options` с проверкой роли/операции** — что видно и выбираемо в конкретном поле формы, в зависимости от контекста

Каждый из этих трёх уровней решает свою задачу и не подменяет остальные — именно поэтому в некоторых местах приходится писать проверку роли трижды в разных файлах, а не один раз централизованно.
