<?php

use App\Http\Controllers\Features\QRCodeController;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;

it('renders a qr generator with tent and food pack options', function () {
    $calculator = new ReliefPackCalculator();
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('getInventory')->once()->andReturn(
        collect($calculator->requiredItemDetails())
            ->map(fn ($item) => [
                'name' => $item['name'],
                'stock' => $item['quantity'],
                'unit' => $item['unit'],
            ])
            ->all()
    );
    $html = (new QRCodeController())->index($firebase, $calculator)->render();

    expect($html)
        ->toContain('Generate QR Code')
        ->toContain('Tent Code')
        ->toContain('Evacuation / Tent Code')
        ->toContain('Food Pack')
        ->toContain('Download PDF')
        ->toContain('Number of Relief Pack QR Codes')
        ->toContain('id="foodPackQuantity"')
        ->toContain('max="1"')
        ->toContain('1 relief pack(s) available.')
        ->toContain('Requirements for each relief pack')
        ->toContain('Laundry Bar Soap: 2 piece')
        ->toContain('Rice: 6 kg')
        ->toContain('Coffee or Energy Drinks: 5 sachet');
});
