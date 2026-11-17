<?php

namespace App\Services;

use Illuminate\Support\Collection;

class InventoryReleaseHistory
{
    public static function fromInventory($inventory): Collection
    {
        return collect($inventory)
            ->filter(fn ($item) => is_array($item))
            ->flatMap(fn ($item) => collect($item['releases'] ?? [])
                ->filter(fn ($release) => is_array($release) && !empty($release['releasedAt']))
                ->map(fn ($release) => [
                    'itemName' => $release['itemName'] ?? $item['name'] ?? 'Unnamed Item',
                    'quantity' => (int) ($release['quantity'] ?? 0),
                    'unit' => $release['unit'] ?? null,
                    'batch' => $release['batch'] ?? null,
                    'purpose' => $release['purpose'] ?? null,
                    'releasedAt' => $release['releasedAt'],
                    'releasedBy' => $release['releasedBy'] ?? null,
                ])->values());
    }
}
