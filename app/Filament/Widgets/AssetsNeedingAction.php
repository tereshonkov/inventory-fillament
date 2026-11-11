<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\AssetResource;
use App\Models\Asset;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Queue of assets that need somebody's attention. Unlike the other dashboard widgets it respects the role
 * scoping of the assets resource: an editor sees only the assets they are responsible for.
 */
class AssetsNeedingAction extends TableWidget
{
    /**
     * Statuses that mean "something has to be done with the asset", grouped by urgency (most urgent group first):
     * a transfer or write-off in progress, then lost, then not put into operation, then repair.
     * Statuses of one group are equally urgent and are ordered by how long the asset has been waiting.
     */
    public const PRIORITY_GROUPS = [
        [AssetStatus::TRANSFERRING, AssetStatus::WRITING_OFF],
        [AssetStatus::LOST],
        [AssetStatus::NOT_PUT_IN_TO_OPERATION],
        [AssetStatus::REPAIR],
    ];

    /** All statuses of the queue, in priority order. */
    public const STATUSES = [
        AssetStatus::TRANSFERRING,
        AssetStatus::WRITING_OFF,
        AssetStatus::LOST,
        AssetStatus::NOT_PUT_IN_TO_OPERATION,
        AssetStatus::REPAIR,
    ];

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Потребує уваги')
            ->description('Спершу передача і списання, що тривають, потім втрачені, не введені в експлуатацію та ремонт; у межах групи — ті, що чекають найдовше.')
            ->query(fn (): Builder => AssetResource::getEloquentQuery()
                ->whereIn('status', self::STATUSES)
                ->with('custodian')
                ->tap(fn (Builder $query) => self::orderByPriority($query))
                ->orderBy('updated_at')
                ->orderBy('id'))
            ->columns([
                TextColumn::make('inventory_number')
                    ->label('Інв. №')
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->visibleFrom('md')
                    ->placeholder('—'),
                TextColumn::make('short_name')
                    ->label('Назва')
                    // Names can be very long (several paragraphs): at most 4 words here, the full name on hover.
                    ->tooltip(fn (Asset $record): string => Str::limit($record->name, 300)),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('custodian.full_name')
                    ->label('МВО')
                    ->visibleFrom('md')
                    ->placeholder('—'),
                TextColumn::make('updated_at')
                    ->label('Очікує')
                    ->since()
                    ->dateTimeTooltip(),
            ])
            ->recordUrl(fn (Asset $record): string => AssetResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->emptyStateHeading('Усе в порядку')
            ->emptyStateDescription('Немає активів, які потребують уваги.');
    }

    /** Most urgent status group first (portable CASE expression, works on SQLite and MySQL). */
    private static function orderByPriority(Builder $query): void
    {
        $sql = 'case status';
        $bindings = [];

        foreach (self::PRIORITY_GROUPS as $rank => $group) {
            foreach ($group as $status) {
                $sql .= ' when ? then '.$rank;
                $bindings[] = $status->value;
            }
        }

        $query->orderByRaw($sql.' else '.count(self::PRIORITY_GROUPS).' end', $bindings);
    }
}
