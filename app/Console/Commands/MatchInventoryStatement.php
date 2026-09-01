<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Illuminate\Console\Command;
use League\Csv\Reader;

class MatchInventoryStatement extends Command
{
    protected $signature = 'assets:match-statement {file}';

    protected $description = 'Одноразовий парсер інвентаризаційної відомості';

    public function handle(): void
    {
        $csv = Reader::createFromPath($this->argument('file'));

        $updated = 0;
        $yearFilled = 0;
        $notFound = [];

        foreach ($csv as $record) {
            $inventoryNumber = trim($record[14] ?? '');

            if ($inventoryNumber === '' || ! ctype_digit($inventoryNumber)) {
                continue;
            }

            $asset = Asset::where('inventory_number', $inventoryNumber)->first();

            if (! $asset) {
                $notFound[] = $inventoryNumber;
                continue;
            }

            $name = trim($record[1] ?? '');
            $asset->name = $name;

            if (empty($asset->year)) {
                $yearText = trim($record[10] ?? '');
                if (preg_match('/\d{4}/', $yearText, $matches)) {
                    $asset->year = (int) $matches[0];
                    $yearFilled++;
                }
            }

            $asset->save();
            $updated++;
        }

        $this->info("Оновлено записів: {$updated}");
        $this->info("Заповнено year: {$yearFilled}");

        if ($notFound !== []) {
            $this->warn('Не знайдено: ' . implode(', ', $notFound));
        }
    }
}