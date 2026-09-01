<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use League\Csv\Reader;
use Carbon\Carbon;

class MatchInventoryStatement extends Command
{
    protected $signature = 'assets:match-statement {file}';

    protected $description = 'Одноразовий парсер інвентаризаційної відомості';

    public function handle(): void
    {
        $csv = Reader::createFromPath($this->argument('file'));
        $csv->setHeaderOffset(0);

        $updated = 0;
        $yearFilled = 0;
        $notFound = [];

        foreach ($csv->getRecords() as $row) {
            // тут твоя логика:
            // 1. достать инвентарник из $row, найти Asset
            $inventoryNumber = trim($row['інвентарний/номенклатурний'] ?? null);
            $name = trim($row['Найменування, стисла характеристика та призначення об’єкта'] ?? null);
            $year = trim($row['Рік випуску  (будівництва) чи дата придбання (введення в експлуатацію) та виготовлювач'] ?? null);
            $asset = Asset::where('inventory_number', $inventoryNumber)->first();
            // 2. если не нашли — записать в $notFound, продолжить
            if (! $asset) {
                $notFound[] = $inventoryNumber;
                continue;
            }
            // 3. если нашли — обновить name (всегда)
            $asset->name = $name;
            // 4. если year пустой — распарсить дату из $row, заполнить, увеличить $yearFilled
            if (empty($asset->year)) {
                try {
                    $asset->year = Carbon::parse($year)->year;
                    $yearFilled++;
                } catch (\Exception $e) {
                    $this->warn("Не вдалося розпізнати дату для {$inventoryNumber}: {$year}");
                }
            }
            // 5. сохранить, увеличить $updated
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
