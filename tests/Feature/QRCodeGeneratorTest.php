<?php

use App\Http\Controllers\Features\QRCodeController;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;
use Illuminate\Http\Request;

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
        ->toContain('barangayList.map((barangay)')
        ->toContain('tentCodesFor(barangay, count)')
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

it('saves an adjusted CMEC tent count', function () {
    $firebase = Mockery::mock(FirebaseService::class);
    $firebase->shouldReceive('setTentCount')->once()->with('CMEC', 50)->andReturn(true);
    $request = Request::create('/features/qr/tent-counts', 'POST', [
        'barangay_code' => 'CMEC',
        'count' => 50,
    ]);

    $response = (new QRCodeController())->saveTentCount($request, $firebase);

    expect($response->getData()->success)->toBeTrue();
});
