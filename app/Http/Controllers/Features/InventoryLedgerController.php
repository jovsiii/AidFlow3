<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Shows the inventory ledger: inbound receipts and outbound relief-pack distributions. */
class InventoryLedgerController extends Controller
{
    public function index(Request $request, FirebaseService $firebase, ReliefPackCalculator $calculator)
    {
        $tab = $request->query('tab') === 'outbound' ? 'outbound' : 'inbound';

        $inbound = collect($firebase->getInventory())
            ->filter(fn ($item) => is_array($item))
            ->map(function ($item) {
                $date = $item['added'] ?? $item['received'] ?? null;

                return [
                    'date' => $date,
                    'timestamp' => $date ? Carbon::parse($date)->timestamp : 0,
                    'name' => $item['name'] ?? 'Unnamed Item',
                    'category' => $item['category'] ?? 'Uncategorized',
                    'batch' => $item['batch'] ?? null,
                    'quantity' => (int) ($item['startingStock'] ?? $item['stock'] ?? 0),
                    'unit' => $item['unit'] ?? '',
                    'receivedBy' => $item['receivedBy'] ?? null,
                ];
            })
            ->sortByDesc('timestamp')
            ->values();

        $packItems = $calculator->requiredItems();

        $outbound = collect($firebase->getReliefPackScans())
            ->filter(fn ($scan) => is_array($scan) && !empty($scan['scanned_at']))
            ->map(fn ($scan) => [
                'date' => $scan['scanned_at'],
                'timestamp' => Carbon::parse($scan['scanned_at'])->timestamp,
                'pack' => $scan['pack_number'] ?? null,
                'items' => $packItems,
                'distributedBy' => $scan['accountName'] ?? null,
            ])
            ->sortByDesc('timestamp')
            ->values();

        return view('features.ledger.index', compact('tab', 'inbound', 'outbound'));
    }
}
