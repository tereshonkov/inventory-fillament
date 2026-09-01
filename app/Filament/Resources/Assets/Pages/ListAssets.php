<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ImportAction;
use Filament\Actions\ExportAction;
use App\Enums\UserRole;
use App\Filament\Exports\AssetExporter;
use App\Filament\Imports\AssetImporter;
use Filament\Actions\Exports\Enums\ExportFormat;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Експортувати')
                ->exporter(AssetExporter::class)
                ->formats([ExportFormat::Xlsx]),
            ImportAction::make()
                ->label('Імпортувати')
                ->importer(AssetImporter::class)
                ->visible(fn() => auth()->user()->role === UserRole::ADMIN),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = fn() => static::getResource()::getEloquentQuery();

        return [
            'all' => Tab::make('Усі')
                ->badge((clone $baseQuery())->count()),

            'balance' => Tab::make('На балансі')
                ->badge((clone $baseQuery())->where('status', AssetStatus::BALANCE)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', AssetStatus::BALANCE)),

            'not_put_in_to_operation' => Tab::make('Не введено')
                ->badge((clone $baseQuery())->where('status', AssetStatus::NOT_PUT_IN_TO_OPERATION)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', AssetStatus::NOT_PUT_IN_TO_OPERATION)),

            'capitalize' => Tab::make('Поступає на баланс')
                ->badge((clone $baseQuery())->where('status', AssetStatus::CAPITALIZE)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', AssetStatus::CAPITALIZE)),

            'transferring' => Tab::make('Проводиться передача')
                ->badge((clone $baseQuery())->where('status', AssetStatus::TRANSFERRING)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', AssetStatus::TRANSFERRING)),
        ];
    }
}
