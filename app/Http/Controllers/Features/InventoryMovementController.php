<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Carbon\Carbon;

/** Shows inventory stock coming in (inbound) and going out (outbound). */
class InventoryMovementController extends Controller
{
    public function inbound(FirebaseService $firebase)
    {
        $movements = $this->movements($firebase, fn ($item) => [
            'date' => $item['added'] ?? $item['received'] ?? null,
            'quantity' => (int) ($item['startingStock'] ?? $item['stock'] ?? 0),
            'type' => 'Received',
        ]);

        return view('features.inventory.movements', [
            'direction' => 'inbound',
            'title' => 'Inbound',
            'movements' => $movements,
        ]);
    }

    public function outbound(FirebaseService $firebase)
    {
        $movements = $this->movements($firebase, function ($item) {
            $distributed = $item['distributed'] ?? null;
            $removed = $item['removed'] ?? null;
            $date = $distributed ?: $removed;

            if (!$date) {
                return null;
            }

            return [
                'date' => $date,
                'quantity' => (int) ($item['startingStock'] ?? $item['stock'] ?? 0),
                'type' => $distributed ? 'Distributed' : 'Removed',
            ];
        });

        return view('features.inventory.movements', [
            'direction' => 'outbound',
            'title' => 'Outbound',
            'movements' => $movements,
        ]);
    }

    /** Build movement rows from inventory records; the resolver returns null to skip a record. */
    private function movements(FirebaseService $firebase, callable $resolver)
    {
        return collect($firebase->getInventory())
            ->filter(fn ($item) => is_array($item))
            ->map(function ($item) use ($resolver) {
                $movement = $resolver($item);

                if (!$movement || !$movement['date']) {
                    return null;
                }

                return $movement + [
                    'name' => $item['name'] ?? 'Unnamed Item',
                    'category' => $item['category'] ?? 'Uncategorized',
                    'unit' => $item['unit'] ?? '',
                    'batch' => $item['batch'] ?? null,
                    'timestamp' => Carbon::parse($movement['date'])->timestamp,
                ];
            })
            ->filter()
            ->sortByDesc('timestamp')
            ->values();
    }
}
