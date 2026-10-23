<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;
use Illuminate\Http\Request;

/** Lists and maintains inventory records stored in Firebase. */
class InventoryController extends Controller
{
    private const NEAR_EXPIRY_MONTHS = 3;
    private const MINIMUM_STANDARD_RELIEF_PACKS = 1;

    /** Convert Firebase's keyed records into view-ready items while retaining each record ID. */
    private function normalizeInventoryItems($inventoryData)
    {
        return collect($inventoryData)
            ->map(function ($item, $id) {
                if (!is_array($item)) {
                    return null;
                }

                $item['id'] = $id;

                return $item;
            })
            ->filter()
            ->values();
    }

    /** Filter inventory, calculate summary metrics, and render the inventory page. */
    public function index(Request $request, FirebaseService $firebase, ?ReliefPackCalculator $reliefPackCalculator = null)
    {
        $reliefPackCalculator ??= new ReliefPackCalculator();
        $allInventoryItems = $this->normalizeInventoryItems($firebase->getInventory());
        $inventoryItems = $allInventoryItems;
        $batchGroups = $inventoryItems
            ->filter(fn ($item) => filled($item['batch'] ?? null))
            ->groupBy(fn ($item) => (string) $item['batch'])
            ->sortKeysUsing('strnatcasecmp');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->search);
            $inventoryItems = $inventoryItems->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item['name'] ?? ''), $search)
                    || str_contains(mb_strtolower($item['category'] ?? ''), $search);
            });
        }

        if ($request->filled('category')) {
            $inventoryItems = $inventoryItems->filter(function ($item) use ($request) {
                return ($item['category'] ?? '') === $request->category;
            });
        }

        if ($request->filled('status')) {
            $today = now()->startOfDay();
            $inventoryItems = $inventoryItems->filter(function ($item) use ($request, $today) {
                $expirationDate = $item['expirationDate'] ?? null;

                if ($request->status === 'expired') {
                    return $expirationDate && \Carbon\Carbon::parse($expirationDate)->lt($today);
                }

                if ($request->status === 'near_expiry') {
                    return $expirationDate && \Carbon\Carbon::parse($expirationDate)->between($today, $today->copy()->addMonths(self::NEAR_EXPIRY_MONTHS));
                }

                return !$expirationDate || \Carbon\Carbon::parse($expirationDate)->gt($today->copy()->addMonths(self::NEAR_EXPIRY_MONTHS));
            });
        }

        $inventoryItems = $inventoryItems->sortBy(fn($item) => $item['name'] ?? '')->values();
        $today = now()->startOfDay();
        $activeInventoryItems = $allInventoryItems->reject(function ($item) use ($today) {
            $expirationDate = $item['expirationDate'] ?? null;

            return $expirationDate && \Carbon\Carbon::parse($expirationDate)->lt($today);
        });
        $lowStockAlerts = $activeInventoryItems
            ->groupBy(fn ($item) => mb_strtolower(trim($item['name'] ?? 'Unnamed Item')))
            ->map(function ($items) {
                $item = $items->first();
                $item['stock'] = $items->sum(fn ($record) => (int) ($record['stock'] ?? 0));
                $item['startingStock'] = $items->sum(fn ($record) => (int) ($record['startingStock'] ?? $record['stock'] ?? 0));
                $item['lowStockThreshold'] = (int) floor($item['startingStock'] * 0.25);

                return $item;
            })
            ->filter(fn ($item) => $item['stock'] <= $item['lowStockThreshold'])
            ->values();
        $lowStockItems = $lowStockAlerts->count();
        $nearExpiryAlerts = $activeInventoryItems
            ->filter(function ($item) use ($today) {
                $expirationDate = $item['expirationDate'] ?? null;

                return $expirationDate && \Carbon\Carbon::parse($expirationDate)->between($today, $today->copy()->addMonths(self::NEAR_EXPIRY_MONTHS));
            })
            ->values();
        $standardReliefPackSupply = $reliefPackCalculator->count($activeInventoryItems);
        $minimumStandardReliefPacks = self::MINIMUM_STANDARD_RELIEF_PACKS;
        $inventoryListItems = $inventoryItems
            ->reject(function ($item) use ($today) {
                $expirationDate = $item['expirationDate'] ?? null;

                return $expirationDate && \Carbon\Carbon::parse($expirationDate)->lt($today);
            })
            ->groupBy(fn ($item) => mb_strtolower(trim($item['name'] ?? 'Unnamed Item')))
            ->map(function ($items) {
                $item = $items->first();
                $item['records'] = $items->values();
                $item['stock'] = $items->sum(fn ($item) => (int) ($item['stock'] ?? 0));
                $item['startingStock'] = $items->sum(fn ($item) => (int) ($item['startingStock'] ?? $item['stock'] ?? 0));

                return $item;
            })
            ->values();

        $totalItems = $inventoryItems->count();
        $goodItems = $inventoryItems->filter(function ($item) use ($today) {
            $expirationDate = $item['expirationDate'] ?? null;

            return !$expirationDate || \Carbon\Carbon::parse($expirationDate)->gt($today->copy()->addMonths(self::NEAR_EXPIRY_MONTHS));
        })->count();
        $nearExpiryItems = $inventoryItems->filter(function ($item) use ($today) {
            $expirationDate = $item['expirationDate'] ?? null;

            return $expirationDate && \Carbon\Carbon::parse($expirationDate)->between($today, $today->copy()->addMonths(self::NEAR_EXPIRY_MONTHS));
        })->count();
        $expiredItems = $inventoryItems->filter(function ($item) use ($today) {
            $expirationDate = $item['expirationDate'] ?? null;

            return $expirationDate && \Carbon\Carbon::parse($expirationDate)->lt($today);
        })->count();
        $standardReliefPacks = $reliefPackCalculator->count($inventoryItems);
        $foodCount = $inventoryItems->filter(function ($item) {
            return mb_strtolower($item['category'] ?? '') === 'food';
        })->count();
        $nonFoodCount = $inventoryItems->filter(function ($item) {
            return mb_strtolower($item['category'] ?? '') !== 'food';
        })->count();

        $goodPercent = $totalItems > 0 ? round(($goodItems / $totalItems) * 100, 1) : 0;
        $nearExpiryPercent = $totalItems > 0 ? round(($nearExpiryItems / $totalItems) * 100, 1) : 0;
        $expiredPercent = $totalItems > 0 ? round(($expiredItems / $totalItems) * 100, 1) : 0;

        if ($totalItems > 0 && abs(($goodPercent + $nearExpiryPercent + $expiredPercent) - 100) > 0.1) {
            $expiredPercent = max(0, 100 - $goodPercent - $nearExpiryPercent);
        }

        return view('features.inventory.index', compact(
            'inventoryItems',
            'inventoryListItems',
            'batchGroups',
            'lowStockAlerts',
            'nearExpiryAlerts',
            'standardReliefPackSupply',
            'minimumStandardReliefPacks',
            'totalItems',
            'lowStockItems',
            'goodItems',
            'nearExpiryItems',
            'expiredItems',
            'standardReliefPacks',
            'foodCount',
            'nonFoodCount',
            'goodPercent',
            'nearExpiryPercent',
            'expiredPercent'
        ));
    }

    /** Validate and add an inventory record. */
    public function store(Request $request, FirebaseService $firebase)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'unit' => 'required|string',
            'stock' => 'required|integer|min:0',
            'received' => 'nullable|date',
            'expirationDate' => 'nullable|date',
        ]);

        $validated['startingStock'] = (int) $validated['stock'];

        $firebase->createInventory($validated);

        return redirect()->back()->with('success', 'Item added successfully');
    }

    /** Validate and replace the selected inventory record's data. */
    public function update(Request $request, $id, FirebaseService $firebase)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'unit' => 'required|string',
            'stock' => 'required|integer|min:0',
            'received' => 'nullable|date',
            'expirationDate' => 'nullable|date',
        ]);

        $existingItems = $firebase->getInventory();
        $existingItem = is_array($existingItems) ? ($existingItems[$id] ?? []) : [];
        $validated['startingStock'] = (int) ($existingItem['startingStock'] ?? $existingItem['stock'] ?? $validated['stock']);

        $firebase->updateInventory($id, array_merge($existingItem, $validated));

        return redirect()->back()->with('success', 'Item updated successfully');
    }

    /** Remove the selected inventory record. */
    public function destroy($id, FirebaseService $firebase)
    {
        $firebase->deleteInventory($id);

        return redirect()->back()->with('success', 'Item deleted successfully');
    }

    /** Show records associated with the requested batch identifier. */
    public function batch($batchId, FirebaseService $firebase)
    {
        $items = $this->normalizeInventoryItems($firebase->getInventory())
            ->filter(fn($item) => ($item['id'] ?? null) === $batchId)
            ->values();

        return view('features.inventory.batch', ['items' => $items]);
    }
}
