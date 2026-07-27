# Inventory-App — шпаргалка по паттернам Filament

Этот файл собирает рабочие решения, к которым мы пришли по ходу разработки — не просто код, а **почему** он написан именно так. Полезно, когда через месяц откроешь похожую задачу и не будешь помнить, зачем тут вообще эта строчка.

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

## Общий принцип, который тянется через всё

Разделение двух разных вопросов, которые легко перепутать:

- **Policy** — может ли юзер сделать действие над конкретной, уже существующей записью
- **`getEloquentQuery()`** — какие записи вообще попадают в список
- **`modifyQueryUsing`/`options` с проверкой роли/операции** — что видно и выбираемо в конкретном поле формы, в зависимости от контекста

Каждый из этих трёх уровней решает свою задачу и не подменяет остальные — именно поэтому в некоторых местах приходится писать проверку роли трижды в разных файлах, а не один раз централизованно.
