<?php

use App\Services\ReliefPackCalculator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function packPlanKey(string $name, string $unit): string
{
    return base64_encode(json_encode([mb_strtolower(trim($name)), mb_strtolower(trim($unit))], JSON_THROW_ON_ERROR));
}

it('saves an admin plan and estimates packs using only unexpired matching stock', function () {
    $inventory = [
        'rice-a' => ['name' => 'Rice', 'unit' => 'Kg', 'stock' => 11],
        'rice-b' => ['name' => 'Rice', 'unit' => 'kg', 'stock' => 9],
        'old' => ['name' => 'Rice', 'unit' => 'kg', 'stock' => 100, 'expirationDate' => '2000-01-01'],
        'water' => ['name' => 'Water', 'unit' => 'Bottle', 'stock' => 8],
        'prepacked' => ['type' => 'standard'],
    ];
    $plan = null;
    Http::fake(function (Request $request) use ($inventory, &$plan) {
        if (str_ends_with($request->url(), '/inventory.json')) {
            return Http::response($inventory);
        }
        if (str_ends_with($request->url(), '/reliefPackPlan.json')) {
            if ($request->method() === 'PUT') {
                $plan = $request->data();
            }

            return Http::response($plan);
        }

        return Http::response([], 500);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->post(route('relief-pack-plan.store'), ['items' => [
            ['item' => packPlanKey('Rice', 'kg'), 'quantity' => 2],
            ['item' => packPlanKey('Water', 'bottle'), 'quantity' => 3],
        ]])
        ->assertRedirect(route('relief-pack-plan.index'))
        ->assertSessionHas('success', 'Relief pack plan saved.');

    expect($plan['items'])->toHaveCount(2)
        ->and($plan['items'][0]['quantity'])->toBe(2)
        ->and($plan['items'][1]['quantity'])->toBe(3);

    $this->get(route('relief-pack-plan.index'))
        ->assertOk()
        ->assertViewHas('availablePacks', 2)
        ->assertSee('Rice')
        ->assertSee('Water')
        ->assertSee('Saving this plan makes it active')
        ->assertSee('Existing unscanned QR codes use the active plan when scanned');
});

it('restricts plan management to admins', function () {
    Http::fake();
    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('relief-pack-plan.index'))->assertForbidden();
    $this->post(route('relief-pack-plan.store'), [])->assertForbidden();
    Http::assertNothingSent();
});

it('offers the plan configuration link only to admins', function () {
    Http::fake(['*' => Http::response(null)]);

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('inventory.index'))->assertOk()
        ->assertSee(route('relief-pack-plan.index'), false);
    $this->withSession(['user' => ['role' => 'user']])
        ->get(route('inventory.index'))->assertOk()
        ->assertDontSee(route('relief-pack-plan.index'), false);
});

it('rejects duplicate, invalid, and unknown plan items', function (array $items, string $field) {
    Http::fake([
        '*inventory.json' => Http::response(['rice' => ['name' => 'Rice', 'unit' => 'kg', 'stock' => 4]]),
        '*reliefPackPlan.json' => Http::response(null),
    ]);

    $this->withSession(['user' => ['role' => 'admin']])
        ->post(route('relief-pack-plan.store'), ['items' => $items])
        ->assertSessionHasErrors($field);
    Http::assertNotSent(fn (Request $request) => $request->method() === 'PUT');
})->with([
    'duplicate' => [[['item' => packPlanKey('Rice', 'kg'), 'quantity' => 1], ['item' => packPlanKey('Rice', 'kg'), 'quantity' => 2]], 'items.0.item'],
    'zero quantity' => [[['item' => packPlanKey('Rice', 'kg'), 'quantity' => 0]], 'items.0.quantity'],
    'unknown item' => [[['item' => packPlanKey('Unknown', 'kg'), 'quantity' => 1]], 'items.0.item'],
    'empty' => [[], 'items'],
]);

it('reports a failed plan save', function () {
    Http::fake(function (Request $request) {
        if (str_ends_with($request->url(), '/inventory.json')) {
            return Http::response(['rice' => ['name' => 'Rice', 'unit' => 'kg', 'stock' => 5]]);
        }
        return $request->method() === 'PUT' ? Http::response([], 500) : Http::response(null);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->post(route('relief-pack-plan.store'), ['items' => [
            ['item' => packPlanKey('Rice', 'kg'), 'quantity' => 1],
        ]])
        ->assertSessionHasErrors(['plan']);
});

it('starts with the standard requirements without changing them', function () {
    Http::fake(['*' => Http::response(null)]);

    $response = $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('relief-pack-plan.index'))->assertOk()
        ->assertViewHas('availablePacks', 0);

    expect($response->viewData('formItems'))->toHaveCount(count((new ReliefPackCalculator())->requiredItemDetails()));
});

it('uses standard requirements when Firebase plan items have been deleted', function (array $plan) {
    Http::fake(function (Request $request) use ($plan) {
        return str_ends_with($request->url(), '/reliefPackPlan.json')
            ? Http::response($plan)
            : Http::response(null);
    });

    $response = $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('relief-pack-plan.index'))->assertOk();

    expect($response->viewData('formItems'))->toHaveCount(count((new ReliefPackCalculator())->requiredItemDetails()));
})->with([
    'empty items' => [['items' => []]],
    'deleted items node' => [['updatedAt' => '2025-01-01T00:00:00+00:00']],
    'deleted item entries' => [['items' => [null, null]]],
]);

it('ignores deleted entries in a partially retained Firebase plan', function () {
    \Illuminate\Support\Facades\Log::shouldReceive('warning')
        ->once()
        ->with('Deleted Firebase relief pack plan entries were ignored.', ['deleted_item_count' => 1]);
    Http::fake(function (Request $request) {
        return str_ends_with($request->url(), '/reliefPackPlan.json')
            ? Http::response([
                'items' => [
                    ['name' => 'Rice', 'unit' => 'kg', 'quantity' => 6],
                    null,
                ],
            ])
            : Http::response(null);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('relief-pack-plan.index'))->assertOk()
        ->assertSee('Rice');
});

it('does not present a Firebase failure as an empty plan or inventory', function (string $failedPath) {
    Http::fake(function (Request $request) use ($failedPath) {
        return str_ends_with($request->url(), $failedPath)
            ? Http::response([], 500)
            : Http::response(null);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('relief-pack-plan.index'))->assertStatus(500);
})->with(['/inventory.json', '/reliefPackPlan.json']);

it('uses the saved plan for availability and scans while preserving past scan contents', function () {
    $inventory = [
        'rice-a' => ['name' => 'Rice', 'unit' => 'kg', 'stock' => 5],
        'rice-b' => ['name' => 'rice', 'unit' => 'KG', 'stock' => 2],
        'wrong-unit' => ['name' => 'Rice', 'unit' => 'sack', 'stock' => 40],
        'expired' => ['name' => 'Rice', 'unit' => 'kg', 'stock' => 80, 'expirationDate' => '2000-01-01'],
        'water' => ['name' => 'Water', 'unit' => 'bottle', 'stock' => 5],
        'prepacked' => ['type' => 'standard'],
    ];
    $plan = null;
    $scans = ['70' => ['pack_number' => 70, 'scanned_at' => '2025-01-01T00:00:00+00:00']];
    Http::fake(function (Request $request) use (&$inventory, &$plan, &$scans) {
        $url = $request->url();
        if (str_ends_with($url, '/reliefPackPlan.json')) {
            if ($request->method() === 'PUT') {
                $plan = $request->data();
            }
            return Http::response($plan);
        }
        if (str_ends_with($url, '/inventory.json')) {
            return Http::response($inventory);
        }
        if (preg_match('~/reliefPackScans/(\d+)\.json$~', $url, $matches)) {
            return Http::response($scans[$matches[1]] ?? null);
        }
        if (str_contains($url, '/reliefPackScans.json')) {
            return Http::response($scans);
        }
        if ($request->method() === 'PATCH' && str_ends_with($url, '/.json')) {
            foreach ($request->data() as $path => $data) {
                [$node, $id] = explode('/', $path, 2);
                if ($node === 'inventory') $inventory[$id] = $data;
                if ($node === 'reliefPackScans') $scans[$id] = $data;
            }
            return Http::response($request->data());
        }
        if (str_ends_with($url, '/relief_packs.json') || str_ends_with($url, '/scanEvents.json')
            || str_ends_with($url, '/occupiedTents.json')) {
            return Http::response(null);
        }
        return Http::response([], 500);
    });

    $this->withSession(['user' => ['role' => 'admin', 'fullName' => 'Test Officer']])
        ->post(route('relief-pack-plan.store'), ['items' => [
            ['item' => packPlanKey('Rice', 'kg'), 'quantity' => 2],
            ['item' => packPlanKey('Water', 'bottle'), 'quantity' => 1],
        ]])->assertSessionHas('success');

    $this->get(route('qrcode.index'))->assertOk()
        ->assertViewHas('availableReliefPacks', 3)
        ->assertSee('Rice: 2 kg')
        ->assertSee('Water: 1 bottle');
    $this->get(route('dashboard'))->assertOk()->assertViewHas('standardReliefPacks', 3);
    $this->get(route('inventory.index'))->assertOk()->assertViewHas('standardReliefPacks', 3);

    $this->postJson(route('phoneFeatures.reliefGoods.scan'), ['package_id' => '#55'])
        ->assertOk()->assertJsonPath('success', true);
    expect($inventory['rice-a']['stock'])->toBe(3)
        ->and($inventory['rice-b']['stock'])->toBe(2)
        ->and($inventory['wrong-unit']['stock'])->toBe(40)
        ->and($inventory['expired']['stock'])->toBe(80)
        ->and($inventory['water']['stock'])->toBe(4)
        ->and($scans[55]['items'])->toHaveCount(2);

    $this->post(route('relief-pack-plan.store'), ['items' => [
        ['item' => packPlanKey('Water', 'bottle'), 'quantity' => 2],
    ]])->assertSessionHas('success');
    $this->postJson(route('phoneFeatures.reliefGoods.scan'), ['package_id' => '#55'])
        ->assertOk()->assertJsonPath('already_scanned', true);
    $this->postJson(route('phoneFeatures.reliefGoods.scan'), ['package_id' => '#56'])
        ->assertOk()->assertJsonPath('success', true);
    expect($inventory['water']['stock'])->toBe(2)
        ->and($inventory['rice-a']['stock'])->toBe(3)
        ->and($scans[56]['items'])->toHaveCount(1);

    $this->get(route('ledger.index', ['tab' => 'outbound']))->assertOk()
        ->assertSee('Rice (kg) × 2')
        ->assertSee('Water (bottle) × 1')
        ->assertSee('Water (bottle) × 2')
        ->assertSee('Rice × 6');
});

it('does not deduct mismatched units or expired stock when scanning', function () {
    $plan = ['items' => [['name' => 'Rice', 'unit' => 'kg', 'quantity' => 5]]];
    Http::fake(function (Request $request) use ($plan) {
        if (str_ends_with($request->url(), '/reliefPackPlan.json')) return Http::response($plan);
        if (str_ends_with($request->url(), '/inventory.json')) return Http::response([
            'valid' => ['name' => 'Rice', 'unit' => 'kg', 'stock' => 2],
            'wrong' => ['name' => 'Rice', 'unit' => 'sack', 'stock' => 100],
            'expired' => ['name' => 'Rice', 'unit' => 'kg', 'stock' => 100, 'expirationDate' => '2000-01-01'],
        ]);
        if (str_contains($request->url(), '/reliefPackScans/')) return Http::response(null);
        return Http::response([], 500);
    });

    $this->withSession(['user' => ['role' => 'user']])
        ->postJson(route('phoneFeatures.reliefGoods.scan'), ['package_id' => '#22'])
        ->assertStatus(422)->assertJsonPath('success', false);
    Http::assertNotSent(fn (Request $request) => $request->method() === 'PATCH');
});

it('does not silently use the default pack if the active plan cannot be read', function () {
    Http::fake(['*reliefPackPlan.json' => Http::response([], 500)]);

    $this->withSession(['user' => ['role' => 'user']])
        ->postJson(route('phoneFeatures.reliefGoods.scan'), ['package_id' => '#22'])
        ->assertStatus(500);
    Http::assertNotSent(fn (Request $request) => $request->method() === 'PATCH');
});

it('distributes a requirement across matching batches and keeps different units separate', function () {
    $updates = null;
    Http::fake(function (Request $request) use (&$updates) {
        if (str_contains($request->url(), '/reliefPackScans/')) return Http::response(null);
        if (str_ends_with($request->url(), '/inventory.json')) return Http::response([
            'a' => ['name' => 'Rice', 'unit' => 'kg', 'stock' => 5],
            'b' => ['name' => 'Rice', 'unit' => 'KG', 'stock' => 2],
            'c' => ['name' => 'Rice', 'unit' => 'sack', 'stock' => 2],
        ]);
        if ($request->method() === 'PATCH') {
            $updates = $request->data();
            return Http::response($updates);
        }
        return Http::response([], 500);
    });

    $result = app(\App\Services\FirebaseService::class)->consumeReliefPack(10, [
        ['name' => 'Rice', 'unit' => 'kg', 'quantity' => 6],
        ['name' => 'Rice', 'unit' => 'sack', 'quantity' => 1],
    ]);

    expect($result['status'])->toBe('consumed')
        ->and($updates['inventory/a']['stock'])->toBe(0)
        ->and($updates['inventory/b']['stock'])->toBe(1)
        ->and($updates['inventory/c']['stock'])->toBe(1)
        ->and($updates['reliefPackScans/10']['items'])->toHaveCount(2);
});
