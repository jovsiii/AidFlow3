<?php

use App\Http\Controllers\PhoneFeatures\AddInventoryController;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

it('provides presets for the listed inventory items', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getInventory')->once()->andReturn([]);

    $response = (new AddInventoryController())->index($firebase);
    $itemDefaults = $response->getData()['itemDefaults'];

    expect($itemDefaults)->toHaveCount(20)
        ->and($itemDefaults['Toothbrush'])->toBe([
            'quantity' => 1500,
            'unit' => 'Piece',
            'category' => 'Hygiene',
        ])
        ->and($itemDefaults['Shampoo'])->toBe([
            'quantity' => 300,
            'unit' => 'Bottle',
            'category' => 'Hygiene',
        ])
        ->and($itemDefaults['Rice'])->toBe([
            'quantity' => 1800,
            'unit' => 'Kg',
            'category' => 'Food',
        ])
        ->and($itemDefaults['Kitchen Utensils'])->toBe([
            'quantity' => 300,
            'unit' => 'Set',
            'category' => 'Equipment',
        ]);
});

it('stores inventory items from the phone form in firebase', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('createInventory')->once()->with([
        'name' => 'Rice',
        'category' => 'Food',
        'unit' => 'Pack',
        'stock' => 50,
        'startingStock' => 50,
        'received' => '2026-07-26',
        'expirationDate' => '2026-12-31',
        'batch' => 'Batch 1',
    ])->andReturn(['id' => 'abc123']);

    $request = new Request([
        'item_name' => 'Rice',
        'category' => 'Food',
        'unit' => 'Pack',
        'quantity' => 50,
        'date_received' => '2026-07-26',
        'expiration_date' => '2026-12-31',
        'batch_option' => 'existing',
        'batch' => 'Batch 1',
    ]);

    $controller = new AddInventoryController();
    $response = $controller->store($request, $firebase);

    expect($response->getSession()->get('success'))->toBe('Item added successfully');
});

it('stores inventory items without an expiration date when their category does not require one', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('createInventory')->once()->with([
        'name' => 'Bucket',
        'category' => 'Equipment',
        'unit' => 'Piece',
        'stock' => 10,
        'startingStock' => 10,
        'received' => '2026-07-26',
        'expirationDate' => null,
        'batch' => 'Batch 1',
    ])->andReturn(['id' => 'abc123']);

    $request = new Request([
        'item_name' => 'Bucket',
        'category' => 'Equipment',
        'unit' => 'Piece',
        'quantity' => 10,
        'date_received' => '2026-07-26',
        'batch_option' => 'existing',
        'batch' => 'Batch 1',
    ]);

    $controller = new AddInventoryController();
    $response = $controller->store($request, $firebase);

    expect($response->getSession()->get('success'))->toBe('Item added successfully');
});

it('stores rice without an expiration date even when its category normally requires one', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('createInventory')->once()->with([
        'name' => 'Rice',
        'category' => 'Food',
        'unit' => 'Pack',
        'stock' => 50,
        'startingStock' => 50,
        'received' => '2026-07-26',
        'expirationDate' => null,
        'batch' => 'Batch 1',
    ])->andReturn(['id' => 'abc123']);

    $request = new Request([
        'item_name' => 'Rice',
        'category' => 'Food',
        'unit' => 'Pack',
        'quantity' => 50,
        'date_received' => '2026-07-26',
        'batch_option' => 'existing',
        'batch' => 'Batch 1',
    ]);

    $controller = new AddInventoryController();
    $response = $controller->store($request, $firebase);

    expect($response->getSession()->get('success'))->toBe('Item added successfully');
});
