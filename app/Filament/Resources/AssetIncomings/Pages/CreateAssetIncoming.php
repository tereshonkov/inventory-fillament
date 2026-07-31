<?php

namespace App\Filament\Resources\AssetIncomings\Pages;

use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\AssetIncomings\AssetIncomingResource;
use App\Models\Asset;
use App\Models\AssetIncoming;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateAssetIncoming extends CreateRecord
{
    use RedirectsAfterSave;

    protected static string $resource = AssetIncomingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Вычленить поля, которые относятся к Asset
            $assetData = [
                'name' => $data['name'],
                'inventory_number' => $data['inventory_number'],
                'serial_number' => $data['serial_number'],
                'notes' => $data['asset_notes'],
                'year' => $data['year'],
                'location_id' => $data['location_id'],
                'type_id' => $data['type_id'],
                'custodian_id' => $data['custodian_id'],
                'holder_id' => $data['holder_id'],
                'status' => $data['status']
            ];

            // 2. Создать Asset
            $asset = Asset::create($assetData);

            // 3. Оставшиеся поля — это данные AssetIncoming,
            //    плюс asset_id только что созданного актива
            $incomingData = [
                'incoming_type' => $data['incoming_type'],
                'source' => $data['source'],
                'document_number' => $data['document_number'],
                'received_at' => $data['received_at'],
                'completed_at' => $data['completed_at'],
                'notes' => $data['notes']
            ];
            $incomingData['asset_id'] = $asset->id;

            // 4. Создать и вернуть AssetIncoming
            return AssetIncoming::create($incomingData);
        });
    }
}
