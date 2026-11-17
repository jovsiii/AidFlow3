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
        ->assertSee('Planning only')
        ->assertSee('does not reserve stock or change QR codes');
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

it('does not present a Firebase failure as an empty plan or inventory', function (string $failedPath) {
    Http::fake(function (Request $request) use ($failedPath) {
        return str_ends_with($request->url(), $failedPath)
            ? Http::response([], 500)
            : Http::response(null);
    });

    $this->withSession(['user' => ['role' => 'admin']])
        ->get(route('relief-pack-plan.index'))->assertStatus(500);
})->with(['/inventory.json', '/reliefPackPlan.json']);
