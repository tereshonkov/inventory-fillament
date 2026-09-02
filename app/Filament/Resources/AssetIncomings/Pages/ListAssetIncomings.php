<?php

namespace App\Filament\Resources\AssetIncomings\Pages;

use App\Filament\Resources\AssetIncomings\AssetIncomingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use App\Enums\IncomingType;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAssetIncomings extends ListRecords
{
    protected static string $resource = AssetIncomingResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

        public function getTabs(): array
    {
        $baseQuery = fn() => static::getResource()::getEloquentQuery();

        return [
            'all' => Tab::make('Усі')
                ->badge((clone $baseQuery())->count()),

            'incoming storage' => Tab::make('Отримано на складі')
                ->badge((clone $baseQuery())->where('incoming_type', IncomingType::NEW)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('incoming_type', IncomingType::NEW)),

            'incoming department' => Tab::make('Отримано з підрозділу')
                ->badge((clone $baseQuery())->where('incoming_type', IncomingType::ALREADY_IN_USE)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('incoming_type', IncomingType::ALREADY_IN_USE)),
        ];
    }
}
