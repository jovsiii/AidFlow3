<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\InventoryReleaseHistory;
use App\Services\ReliefPackCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Shows the inventory ledger: inbound receipts and outbound distributions. */
class InventoryLedgerController extends Controller
{
    public function index(Request $request, FirebaseService $firebase, ReliefPackCalculator $calculator)
    {
        $tab = $request->query('tab') === 'outbound' ? 'outbound' : 'inbound';

        $inventory = collect($firebase->getInventory())->filter(fn ($item) => is_array($item));
        $inbound = $inventory
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
                'type' => 'pack',
                'date' => $scan['scanned_at'],
                'timestamp' => Carbon::parse($scan['scanned_at'])->timestamp,
                'pack' => $scan['pack_number'] ?? null,
                'items' => isset($scan['items']) && is_array($scan['items'])
                    ? collect($scan['items'])->mapWithKeys(fn ($item) => [
                        $item['name'] . ' (' . $item['unit'] . ')' => (int) $item['quantity'],
                    ])->all()
                    : $packItems,
                'distributedBy' => $scan['accountName'] ?? null,
                'batch' => null,
                'purpose' => null,
                'unit' => null,
            ])
            ->values();

        $releases = InventoryReleaseHistory::fromInventory($inventory)
            ->map(fn ($release) => [
                'type' => 'release',
                'date' => $release['releasedAt'],
                'timestamp' => Carbon::parse($release['releasedAt'])->timestamp,
                'pack' => null,
                'items' => [$release['itemName'] => $release['quantity']],
                'distributedBy' => $release['releasedBy'],
                'batch' => $release['batch'],
                'purpose' => $release['purpose'],
                'unit' => $release['unit'],
            ]);

        $outbound = $outbound->concat($releases)->sortBy('timestamp')->values();

        // Running balance: total units received minus units distributed up to each event.
        $balance = (int) $inbound->sum('quantity');
        $outbound = $outbound->map(function ($row) use (&$balance) {
            $balance -= (int) collect($row['items'])->sum();
            $row['remaining'] = $balance;

            return $row;
        })->sortByDesc('timestamp')->values();

        $version = $this->fingerprint($firebase, $inventory->all());

        return view('features.ledger.index', compact('tab', 'inbound', 'outbound', 'version'));
    }

    /** Return a fingerprint of the ledger data so open pages can detect new records. */
    public function version(FirebaseService $firebase)
    {
        return response()->json(['version' => $this->fingerprint($firebase)])
            ->header('Cache-Control', 'no-store');
    }

    private function fingerprint(FirebaseService $firebase, ?array $inventory = null): string
    {
        return md5(json_encode([
            $inventory ?? collect($firebase->getInventory())->filter(fn ($item) => is_array($item))->all(),
            $firebase->getKeys('reliefPackScans'),
        ]));
    }
}
