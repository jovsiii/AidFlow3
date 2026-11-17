<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ReliefPackPlanController extends Controller
{
    public function index(Request $request, FirebaseService $firebase, ReliefPackCalculator $calculator)
    {
        $this->authorizeAdmin($request);
        $inventory = collect($firebase->getInventory(true));
        $plan = $firebase->getReliefPackPlan();
        $items = $plan['items'] ?? $calculator->requiredItemDetails();
        $options = $this->itemOptions($inventory, $items);
        $availablePacks = $calculator->count($inventory, $items);
        $defaultFormItems = collect($items)->map(fn ($item) => [
            'item' => $this->itemKey($item['name'], $item['unit']),
            'quantity' => $item['quantity'],
        ])->all();
        $oldItems = old('items');
        $formItems = is_array($oldItems)
            ? collect($oldItems)->filter(fn ($item) => is_array($item))->take(100)->values()->all()
            : $defaultFormItems;

        return view('features.inventory.relief-pack-plan', compact('options', 'availablePacks', 'formItems'));
    }

    public function store(Request $request, FirebaseService $firebase, ReliefPackCalculator $calculator)
    {
        $this->authorizeAdmin($request);
        $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.item' => ['required', 'string', 'max:4000', 'distinct:strict'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $inventory = collect($firebase->getInventory(true));
        $plan = $firebase->getReliefPackPlan();
        $options = $this->itemOptions($inventory, $plan['items'] ?? $calculator->requiredItemDetails());
        $validated = $request->validate([
            'items.*.item' => ['required', Rule::in($options->keys()->all())],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);
        $items = collect($validated['items'])->map(fn ($item) => [
            'name' => $options[$item['item']]['name'],
            'unit' => $options[$item['item']]['unit'],
            'quantity' => (int) $item['quantity'],
        ])->values()->all();
        \Illuminate\Support\Facades\Validator::make(['items' => $items], [
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:100'],
        ])->validate();

        if (!$firebase->saveReliefPackPlan($items)) {
            return back()->withInput()->withErrors(['plan' => 'Unable to save the relief pack plan. Please try again.']);
        }

        return redirect()->route('relief-pack-plan.index')->with('success', 'Relief pack plan saved.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(mb_strtolower((string) $request->session()->get('user.role', '')) === 'admin', 403);
    }

    private function itemOptions(Collection $inventory, array $items): Collection
    {
        return $inventory->concat($items)
            ->filter(fn ($item) => is_array($item) && is_string($item['name'] ?? null)
                && trim($item['name']) !== '' && is_string($item['unit'] ?? null) && trim($item['unit']) !== '')
            ->mapWithKeys(fn ($item) => [
                $this->itemKey($item['name'], $item['unit']) => [
                    'name' => trim($item['name']),
                    'unit' => trim($item['unit']),
                ],
            ])
            ->sortBy(fn ($item) => mb_strtolower($item['name'] . ' ' . $item['unit']));
    }

    private function itemKey(string $name, string $unit): string
    {
        return base64_encode(json_encode([mb_strtolower(trim($name)), mb_strtolower(trim($unit))], JSON_THROW_ON_ERROR));
    }
}
