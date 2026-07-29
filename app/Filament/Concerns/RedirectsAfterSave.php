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
