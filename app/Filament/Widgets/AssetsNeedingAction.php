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
    /** Statuses that mean "something has to be done with the asset". */
    public const STATUSES = [
        AssetStatus::NOT_PUT_IN_TO_OPERATION,
        AssetStatus::TRANSFERRING,
        AssetStatus::WRITING_OFF,
        AssetStatus::REPAIR,
        AssetStatus::LOST,
    ];

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Потребує уваги')
            ->description('Не введено в експлуатацію, триває передача чи списання, ремонт або втрачено. Спершу — ті, що чекають найдовше.')
            ->query(fn (): Builder => AssetResource::getEloquentQuery()
                ->whereIn('status', self::STATUSES)
                ->with('custodian')
                ->orderBy('updated_at')
                ->orderBy('id'))
            ->columns([
                TextColumn::make('inventory_number')
                    ->label('Інв. №')
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
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
}
