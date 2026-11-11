<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\Asset;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class AssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // One card with two zones (equal height by construction): current state and identifier
                // on the left (the name is already the page heading), compact facts on the right.
                Section::make()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Group::make([
                            TextEntry::make('inventory_number')
                                ->label('Інвентарний номер')
                                ->fontFamily(FontFamily::Mono)
                                ->copyable()
                                ->copyMessage('Інвентарний номер скопійовано'),
                            TextEntry::make('status')
                                ->hiddenLabel()
                                ->badge()
                                ->size(TextSize::Large),
                        ]),
                        Group::make([
                            TextEntry::make('location.name')
                                ->label('Локація')
                                ->inlineLabel()
                                ->icon(Heroicon::MapPin)
                                ->placeholder('Не призначено'),
                            TextEntry::make('type.name')
                                ->label('Тип')
                                ->inlineLabel()
                                ->placeholder('Не призначено'),
                            TextEntry::make('year')
                                ->label('Рік')
                                ->inlineLabel()
                                ->placeholder('Не вказано'),
                        ]),
                    ]),

                Section::make('Відповідальність та ідентифікація')
                    ->inlineLabel()
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        // The page heading shows only a short name, so the full one lives here (clamped, full text in tooltip).
                        TextEntry::make('name')
                            ->label('Повна назва')
                            ->inlineLabel(false) // full-width long text: label above, not a third of the row away
                            ->lineClamp(3)
                            ->tooltip(fn (string $state): string => $state)
                            ->columnSpanFull(),
                        TextEntry::make('custodian.full_name')
                            ->label('МВО')
                            ->placeholder('Не призначено'),
                        TextEntry::make('holder.full_name')
                            ->label('Користувач')
                            ->placeholder('Не призначено'),
                        TextEntry::make('serial_number')
                            ->label('Серійний номер')
                            ->fontFamily(FontFamily::Mono)
                            ->copyable()
                            ->copyMessage('Серійний номер скопійовано')
                            ->placeholder('Не вказано'),
                        TextEntry::make('notes')
                            ->label('Примітки')
                            ->inlineLabel(false)
                            ->placeholder('Немає приміток')
                            ->columnSpanFull(),
                    ]),

                // Composition of a workstation by kind (monitors, PC, printer, ...); other categories have no such block.
                Section::make('Склад станції')
                    ->visible(fn (Asset $record): bool => $record->isWorkstation())
                    ->schema([
                        View::make('filament.assets.composition')
                            ->viewData(fn (Asset $record): array => ['groups' => $record->compositionByKind()]),
                    ]),

                Section::make('Службова інформація')
                    ->inlineLabel()
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Створено')
                            ->since()
                            ->dateTimeTooltip(),
                        TextEntry::make('updated_at')
                            ->label('Оновлено')
                            ->since()
                            ->dateTimeTooltip(),
                    ]),
            ]);
    }
}
