<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ReliefPackCalculator
{
    private const REQUIRED_ITEMS = [
        ['name' => 'Toothbrush', 'quantity' => 5, 'unit' => 'piece'],
        ['name' => 'Toothpaste', 'quantity' => 2, 'unit' => 'piece'],
        ['name' => 'Shampoo', 'quantity' => 1, 'unit' => 'bottle'],
        ['name' => 'Bath Bar Soap', 'quantity' => 4, 'unit' => 'piece'],
        ['name' => 'Laundry Bar Soap', 'quantity' => 2000, 'unit' => 'gram'],
        ['name' => 'Sanitary Napkin', 'quantity' => 4, 'unit' => 'pack'],
        ['name' => 'Comb', 'quantity' => 1, 'unit' => 'piece'],
        ['name' => 'Disposable Shaving Razor', 'quantity' => 1, 'unit' => 'piece'],
        ['name' => 'Nail Cutter', 'quantity' => 1, 'unit' => 'piece'],
        ['name' => 'Bathroom Dipper', 'quantity' => 1, 'unit' => 'piece'],
        [
            'name' => '20L Square Plastic Bucket with Deep Cover and Plastic Handle',
            'quantity' => 1,
            'unit' => 'piece',
        ],
        ['name' => 'Blanket', 'quantity' => 1, 'unit' => 'piece'],
        ['name' => 'Mosquito Net', 'quantity' => 1, 'unit' => 'piece'],
        ['name' => 'Mat', 'quantity' => 1, 'unit' => 'piece'],
        ['name' => 'Kitchen Utensils', 'quantity' => 1, 'unit' => 'set'],
        ['name' => 'Rice', 'quantity' => 6, 'unit' => 'kg'],
        ['name' => 'Canned Sardines', 'quantity' => 5, 'unit' => 'can'],
        ['name' => 'Canned Tuna', 'quantity' => 5, 'unit' => 'can'],
        ['name' => 'Canned Beef Loaf', 'quantity' => 5, 'unit' => 'can'],
        ['name' => 'Coffee or Energy Drinks', 'quantity' => 5, 'unit' => 'sachet'],
    ];

    public function requiredItems(): array
    {
        return array_column(self::REQUIRED_ITEMS, 'quantity', 'name');
    }

    public function requiredItemDetails(): array
    {
        return self::REQUIRED_ITEMS;
    }

    public function count($inventory): int
    {
        $inventory = collect($inventory);
        $standardPacks = $inventory->filter(fn ($item) => is_array($item) && ($item['type'] ?? '') === 'standard')->count();
        $stockByName = $inventory
            ->filter(fn ($item) => is_array($item) && filled($item['name'] ?? null))
            ->groupBy(fn ($item) => mb_strtolower(trim($item['name'])))
            ->map(fn (Collection $items) => $items->sum(fn ($item) => (int) ($item['stock'] ?? 0)));

        $completePacks = collect($this->requiredItems())
            ->map(fn ($requiredQuantity, $itemName) => intdiv(
                (int) ($stockByName->get(mb_strtolower($itemName), 0)),
                $requiredQuantity
            ))
            ->min() ?? 0;

        return $standardPacks + $completePacks;
    }
}