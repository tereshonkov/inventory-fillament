<?php

namespace App\Filament\Resources\AssetIncomings\Pages;

use App\Filament\Resources\AssetIncomings\AssetIncomingResource;
use App\Enums\IncomingType;
use App\Models\Location;
use App\Services\IncomingDocumentImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ListAssetIncomings extends ListRecords
{
    protected static string $resource = AssetIncomingResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importDocument')
                ->label('Завантажити документ')
                ->form([
                    FileUpload::make('file')
                        ->label('Файл (накладна або акт переміщення)')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required()
                        ->disk('local')
                        ->directory('temp-imports'),
                    Select::make('custodian_id')
                        ->label('МВО')
                        ->options(\App\Models\Employee::pluck('full_name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('location_id')
                        ->label('Локація')
                        ->options(\App\Models\Location::pluck('name', 'id'))
                        ->default(fn () => \App\Models\Location::where('name', 'Склад-327')->value('id'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['file']);

                    $result = (new IncomingDocumentImporter())->import(
                        $path,
                        $data['custodian_id'],
                        $data['location_id'],
                    );

                    Notification::make()
                        ->title('Імпорт завершено')
                        ->body("Створено: {$result['created']}" . (isset($result['skipped']) ? ", пропущено: {$result['skipped']}" : ''))
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = fn () => static::getResource()::getEloquentQuery();

        return [
            'all' => Tab::make('Усі')
                ->badge((clone $baseQuery())->count()),

            'incoming storage' => Tab::make('Отримано на складі')
                ->badge((clone $baseQuery())->where('incoming_type', IncomingType::NEW)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('incoming_type', IncomingType::NEW)),

            'incoming department' => Tab::make('Отримано з підрозділу')
                ->badge((clone $baseQuery())->where('incoming_type', IncomingType::ALREADY_IN_USE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('incoming_type', IncomingType::ALREADY_IN_USE)),
        ];
    }
}
