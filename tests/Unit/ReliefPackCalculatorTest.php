<?php

use App\Services\ReliefPackCalculator;

function completeReliefPackItems(): array
{
    return [
        ['name' => 'Toothbrush', 'stock' => 5, 'unit' => 'piece'],
        ['name' => 'Toothpaste', 'stock' => 2, 'unit' => 'piece'],
        ['name' => 'Shampoo', 'stock' => 1, 'unit' => 'bottle'],
        ['name' => 'Bath Bar Soap', 'stock' => 4, 'unit' => 'piece'],
        ['name' => 'Laundry Bar Soap', 'stock' => 2, 'unit' => 'piece'],
        ['name' => 'Sanitary Napkin', 'stock' => 4, 'unit' => 'pack'],
        ['name' => 'Comb', 'stock' => 1, 'unit' => 'piece'],
        ['name' => 'Disposable Shaving Razor', 'stock' => 1, 'unit' => 'piece'],
        ['name' => 'Nail Cutter', 'stock' => 1, 'unit' => 'piece'],
        ['name' => 'Bathroom Dipper', 'stock' => 1, 'unit' => 'piece'],
        ['name' => '20L Square Plastic Bucket with Deep Cover and Plastic Handle', 'stock' => 1, 'unit' => 'piece'],
        ['name' => 'Blanket', 'stock' => 1, 'unit' => 'piece'],
        ['name' => 'Mosquito Net', 'stock' => 1, 'unit' => 'piece'],
        ['name' => 'Mat', 'stock' => 1, 'unit' => 'piece'],
        ['name' => 'Kitchen Utensils', 'stock' => 1, 'unit' => 'set'],
        ['name' => 'Rice', 'stock' => 6, 'unit' => 'kg'],
        ['name' => 'Canned Sardines', 'stock' => 5, 'unit' => 'can'],
        ['name' => 'Canned Tuna', 'stock' => 5, 'unit' => 'can'],
        ['name' => 'Canned Beef Loaf', 'stock' => 5, 'unit' => 'can'],
        ['name' => 'Coffee or Energy Drinks', 'stock' => 5, 'unit' => 'sachet'],
    ];
}

it('counts one pack for one complete standard item group', function () {
    expect((new ReliefPackCalculator())->count(completeReliefPackItems()))->toBe(1);
});

it('does not count an incomplete item group as a pack', function () {
    $items = completeReliefPackItems();
    array_pop($items);

    expect((new ReliefPackCalculator())->count($items))->toBe(0);
});

it('does not combine stock recorded in incompatible units', function () {
    $items = completeReliefPackItems();
    $items[0]['unit'] = 'box';

    expect((new ReliefPackCalculator())->count($items))->toBe(0);
});