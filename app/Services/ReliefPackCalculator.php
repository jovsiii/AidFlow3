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
        ['name' => 'Laundry Bar Soap', 'quantity' => 2, 'unit' => 'piece'],
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
        $stockByNameAndUnit = $inventory
            ->filter(fn ($item) => is_array($item)
                && filled($item['name'] ?? null)
                && filled($item['unit'] ?? null))
            ->groupBy(fn ($item) => $this->inventoryKey($item['name'], $item['unit']))
            ->map(fn (Collection $items) => $items->sum(fn ($item) => (int) ($item['stock'] ?? 0)));

        $completePacks = collect(self::REQUIRED_ITEMS)
            ->map(fn ($item) => intdiv(
                (int) $stockByNameAndUnit->get($this->inventoryKey($item['name'], $item['unit']), 0),
                $item['quantity']
            ))
            ->min() ?? 0;

        return $standardPacks + $completePacks;
    }

    private function inventoryKey(string $name, string $unit): string
    {
        return mb_strtolower(trim($name)).'|'.mb_strtolower(trim($unit));
    }
}