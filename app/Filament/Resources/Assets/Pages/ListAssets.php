<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\AssetResource;
use App\Services\AssetActivationImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
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
            // ImportAction::make()
            //     ->label('Імпортувати')
            //     ->importer(AssetImporter::class)
            //     ->visible(fn() => auth()->user()->role === UserRole::ADMIN),
            Action::make('activateAssets')
                ->label('Введення в експлуатацію')
                ->form([
                    FileUpload::make('file')
                        ->label('Акт введення в експлуатацію')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required()
                        ->disk('local')
                        ->directory('temp-imports'),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['file']);

                    $result = (new AssetActivationImporter())->activate($path);

                    $skippedCount = count($result['skipped']);

                    Notification::make()
                        ->title('Введення в експлуатацію завершено')
                        ->body("Активовано: {$result['activated']}, потребує уваги: {$skippedCount}")
                        ->success()
                        ->send();
                }),
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
