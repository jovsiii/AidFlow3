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

it('releases a selected batch record and shows the release in the outbound ledger', function () {
    $inventory = [
        'record-a' => [
            'name' => 'Rice', 'category' => 'Food', 'unit' => 'Kg', 'stock' => 10,
            'startingStock' => 10, 'batch' => 'Batch A', 'expirationDate' => now()->addYear()->toDateString(),
        ],
        'record-b' => [
            'name' => 'Rice', 'category' => 'Food', 'unit' => 'Kg', 'stock' => 8,
            'startingStock' => 8, 'batch' => 'Batch A', 'expirationDate' => now()->addYears(2)->toDateString(),
        ],
    ];

    \Illuminate\Support\Facades\Http::fake(function ($request) use (&$inventory) {
        if (str_ends_with($request->url(), '/inventory/record-b.json')) {
            if ($request->method() === 'GET') {
                expect($request->header('X-Firebase-ETag'))->toContain('true');
                return \Illuminate\Support\Facades\Http::response($inventory['record-b'], 200, ['ETag' => '"v1"']);
            }
            if ($request->method() === 'PUT') {
                expect($request->header('if-match'))->toContain('"v1"');
                $inventory['record-b'] = $request->data();

                return \Illuminate\Support\Facades\Http::response($request->data());
            }
        }
        if (str_ends_with($request->url(), '/inventory.json')) {
            return \Illuminate\Support\Facades\Http::response($inventory);
        }
        if (str_contains($request->url(), '/reliefPackScans.json')) {
            return \Illuminate\Support\Facades\Http::response(null);
        }

        return \Illuminate\Support\Facades\Http::response([], 500);
    });

    $this->withSession(['user' => ['role' => 'admin', 'fullName' => 'Test Officer']])
        ->get(route('inventory.index'))
        ->assertOk()
        ->assertSee('Edit')
        ->assertSee('Quantity to release')
        ->assertSee('Batch record')
        ->assertSee('Purpose')
        ->assertSee('value="record-a"', false)
        ->assertSee('value="record-b"', false);

    $previousVersion = $this->getJson(route('ledger.version'))->json('version');
    $this->from(route('inventory.index'))
        ->post(route('inventory.release'), [
            'item_name' => 'Rice', 'record_id' => 'record-b', 'quantity' => 3, 'purpose' => 'Evacuation supplies',
        ])
        ->assertRedirect(route('inventory.index'))
        ->assertSessionHas('success', 'Inventory released successfully.');

    expect($inventory['record-a']['stock'])->toBe(10)
        ->and($inventory['record-b']['stock'])->toBe(5)
        ->and($inventory['record-b']['startingStock'])->toBe(8)
        ->and(array_values($inventory['record-b']['releases'])[0])->toMatchArray([
            'itemName' => 'Rice', 'batch' => 'Batch A', 'quantity' => 3,
            'purpose' => 'Evacuation supplies', 'releasedBy' => 'Test Officer',
        ]);

    expect($this->getJson(route('ledger.version'))->json('version'))->not->toBe($previousVersion);

    $this->get(route('ledger.index', ['tab' => 'outbound']))
        ->assertOk()
        ->assertSee('Manual release')
        ->assertSee('Evacuation supplies')
        ->assertSee('Batch A')
        ->assertSee('Test Officer')
        ->assertSee('Rice × 3 Kg')
        ->assertSee('15</span> units left', false);

    $this->from(route('inventory.index'))
        ->delete(route('inventory.destroy', 'record-b'))
        ->assertSessionHasErrors(['error']);
    expect($inventory['record-b']['releases'])->toHaveCount(1);
});

it('rejects invalid quantities, mismatched items, expired stock, and concurrent changes', function (array $item, array $input, int $writeStatus, string $error) {
    \Illuminate\Support\Facades\Http::fake(function ($request) use ($item, $writeStatus) {
        if ($request->method() === 'GET') {
            return \Illuminate\Support\Facades\Http::response($item, 200, ['ETag' => '"v1"']);
        }

        return \Illuminate\Support\Facades\Http::response([], $writeStatus);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->from(route('inventory.index'))
        ->post(route('inventory.release'), array_merge([
            'item_name' => 'Rice', 'record_id' => 'record-a', 'quantity' => 3, 'purpose' => 'Relief',
        ], $input))
        ->assertRedirect(route('inventory.index'))
        ->assertSessionHasErrors(['release' => $error]);
    \Illuminate\Support\Facades\Http::assertSentCount($writeStatus === 412 ? 2 : 1);
})->with([
    'insufficient stock' => [['name' => 'Rice', 'stock' => 2], [], 200, 'The selected batch record does not have enough stock.'],
    'wrong item' => [['name' => 'Soap', 'stock' => 5], [], 200, 'The selected inventory record no longer exists or does not match this item.'],
    'expired stock' => [['name' => 'Rice', 'stock' => 5, 'expirationDate' => '2000-01-01'], [], 200, 'Expired inventory cannot be released.'],
    'concurrent update' => [['name' => 'Rice', 'stock' => 5], [], 412, 'The inventory changed while processing your request. Please try again.'],
]);

it('requires a positive release quantity and a purpose', function () {
    \Illuminate\Support\Facades\Http::fake();

    $this->withSession(['user' => ['role' => 'admin']])
        ->post(route('inventory.release'), [
            'item_name' => 'Rice', 'record_id' => 'record-a', 'quantity' => 0, 'purpose' => '',
        ])
        ->assertSessionHasErrors(['quantity', 'purpose']);

    \Illuminate\Support\Facades\Http::assertNothingSent();
});

it('can still delete an inventory record without release history', function () {
    \Illuminate\Support\Facades\Http::fake(function ($request) {
        if ($request->method() === 'GET') {
            return \Illuminate\Support\Facades\Http::response(['name' => 'Rice', 'stock' => 5], 200, ['ETag' => '"v1"']);
        }

        expect($request->method())->toBe('DELETE')
            ->and($request->header('if-match'))->toContain('"v1"');
        return \Illuminate\Support\Facades\Http::response(null);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->from(route('inventory.index'))
        ->delete(route('inventory.destroy', 'record-a'))
        ->assertSessionHas('success', 'Item deleted successfully');
});

it('reports Firebase read and write failures without claiming a release', function (int $readStatus, ?string $etag, int $writeStatus) {
    \Illuminate\Support\Facades\Http::fake(function ($request) use ($readStatus, $etag, $writeStatus) {
        if ($request->method() === 'GET') {
            return \Illuminate\Support\Facades\Http::response(
                ['name' => 'Rice', 'stock' => 5],
                $readStatus,
                $etag === null ? [] : ['ETag' => $etag]
            );
        }

        return \Illuminate\Support\Facades\Http::response([], $writeStatus);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->post(route('inventory.release'), [
            'item_name' => 'Rice', 'record_id' => 'record-a', 'quantity' => 3, 'purpose' => 'Relief',
        ])
        ->assertSessionHasErrors(['release' => 'Unable to release inventory. Please try again.'])
        ->assertSessionMissing('success');

    \Illuminate\Support\Facades\Http::assertSentCount($readStatus === 200 && $etag !== null ? 2 : 1);
})->with([
    'read failure' => [500, null, 200],
    'missing concurrency token' => [200, null, 200],
    'write failure' => [200, '"v1"', 500],
]);
