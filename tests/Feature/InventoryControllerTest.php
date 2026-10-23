<?php

use App\Http\Controllers\Features\InventoryController;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

it('combines inventory list rows with the same name and sums their stock', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getInventory')->andReturn([
        'first' => [
            'name' => 'Toothbrush',
            'category' => 'Food',
            'unit' => 'Piece',
            'stock' => 1,
            'startingStock' => 30,
            'expirationDate' => now()->subDay()->toDateString(),
            'batch' => 'Batch A',
            'added' => now()->subDays(2)->toDateString(),
        ],
        'second' => [
            'name' => 'Toothbrush',
            'category' => 'Food',
            'unit' => 'Piece',
            'stock' => 2,
            'startingStock' => 20,
            'expirationDate' => now()->addMonths(2)->toDateString(),
            'added' => now()->subDays(2)->toDateString(),
        ],
        'third' => [
            'name' => 'Soap',
            'category' => 'Hygiene',
            'unit' => 'Piece',
            'stock' => 4,
            'startingStock' => 20,
            'added' => now()->subDays(2)->toDateString(),
        ],
    ]);

    $response = (new InventoryController())->index(Request::create('/features/inventory'), $firebase);
    $html = $response->render();

    expect($response->getData()['inventoryListItems'])
        ->toHaveCount(2)
        ->and($response->getData()['inventoryListItems']->firstWhere('name', 'Toothbrush')['stock'])
        ->toBe(2)
        ->and($response->getData()['inventoryListItems']->firstWhere('name', 'Toothbrush')['records'])
        ->toHaveCount(1)
        ->and($response->getData()['lowStockAlerts']->firstWhere('name', 'Toothbrush')['stock'])
        ->toBe(2)
        ->and($response->getData()['lowStockAlerts']->firstWhere('name', 'Toothbrush')['lowStockThreshold'])
        ->toBe(5)
        ->and($response->getData()['nearExpiryAlerts'])
        ->toHaveCount(1)
        ->and($response->getData()['standardReliefPackSupply'])
        ->toBe(0)
        ->and($response->getData()['minimumStandardReliefPacks'])
        ->toBe(1)
        ->and($html)
        ->toContain('Expiration Date', 'Expired', 'Batch A', 'Inventory Alerts', 'Near expiry (within 3 months)', 'Only 0 standard relief packs')
        ->not->toContain('Configure Alert');
});

it('preserves an item starting-stock baseline when updating inventory details', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getInventory')->once()->andReturn([
        'item-1' => [
            'name' => 'Toothbrush',
            'category' => 'Hygiene',
            'unit' => 'Piece',
            'stock' => 8,
            'startingStock' => 20,
            'batch' => 'Batch A',
            'added' => now()->subDay()->toDateString(),
        ],
    ]);
    $firebase->shouldReceive('updateInventory')
        ->once()
        ->with('item-1', Mockery::on(fn ($item) => $item['startingStock'] === 20 && $item['batch'] === 'Batch A'))
        ->andReturn(null);

    $request = Request::create('/features/inventory/item-1', 'PUT', [
        'name' => 'Toothbrush',
        'category' => 'Hygiene',
        'unit' => 'Piece',
        'stock' => 8,
        'received' => '',
        'expirationDate' => '',
    ]);

    $response = (new InventoryController())->update($request, 'item-1', $firebase);

    expect($response->getSession()->get('success'))->toBe('Item updated successfully');
});

it('calculates low stock from the combined starting and remaining stock across batches', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getInventory')->andReturn([
        'batch-a' => [
            'name' => 'Rice',
            'category' => 'Food',
            'unit' => 'Kg',
            'stock' => 10,
            'startingStock' => 40,
        ],
        'batch-b' => [
            'name' => 'Rice',
            'category' => 'Food',
            'unit' => 'Kg',
            'stock' => 10,
            'startingStock' => 40,
        ],
    ]);

    $response = (new InventoryController())->index(Request::create('/features/inventory'), $firebase);
    $alert = $response->getData()['lowStockAlerts']->first();

    expect($alert['name'])->toBe('Rice')
        ->and($alert['stock'])->toBe(20)
        ->and($alert['startingStock'])->toBe(80)
        ->and($alert['lowStockThreshold'])->toBe(20)
        ->and($response->getData()['lowStockItems'])->toBe(1);
});
