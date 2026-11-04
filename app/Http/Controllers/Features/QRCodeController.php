<?php

namespace App\Http\Controllers\Features;

use App\Http\Controllers\Controller;
use App\Models\ScanEvent;
use App\Models\OccupiedTent;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Serves QR scanning pages and records tent or relief-pack scan operations. */
class QRCodeController extends Controller
{
    public function __construct()
    {

    }

    /** Display the QR scanning page. */
    public function index(FirebaseService $firebase, ReliefPackCalculator $reliefPackCalculator)
    {
        $availableReliefPacks = $reliefPackCalculator->count($firebase->getInventory());
        $reliefPackRequirements = $reliefPackCalculator->requiredItemDetails();

        return view('features.qrcode.index', compact('availableReliefPacks', 'reliefPackRequirements'));
    }

    /** Return the saved tent count for every configured barangay. */
    public function tentCounts(FirebaseService $firebase)
    {
        return response()->json($firebase->getTentCounts());
    }

    /** Save how many tents a barangay has. */
    public function saveTentCount(Request $request, FirebaseService $firebase)
    {
        $validated = $request->validate([
            'barangay_code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
            'count' => 'required|integer|min:1|max:500',
        ]);

        if (! $firebase->setTentCount($validated['barangay_code'], (int) $validated['count'])) {
            return response()->json(['success' => false, 'message' => 'Unable to save the tent count.'], 502);
        }

        return response()->json(['success' => true]);
    }

    /** Return the next available relief-pack number without reserving it. */
    public function nextReliefPack(FirebaseService $firebase)
    {
        $number = $firebase->getNextReliefPackNumber();

        return response()->json([
            'success' => true,
            'number' => $number,
            'label' => "Relief Pack: #{$number}",
        ]);
    }

    /** Reserve the next relief-pack number and return its display label. */
    public function reserveReliefPack(FirebaseService $firebase)
    {
        $number = $firebase->reserveNextReliefPackNumber();

        return response()->json([
            'success' => true,
            'number' => $number,
            'label' => "Relief Pack: #{$number}",
        ]);
    }

    /** Record a tent scan and mark that tent as occupied. */
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'tent_code' => 'required|string',
            'barangay_code' => 'nullable|string',
        ]);

        // Record the scan event
        ScanEvent::create([
            'tent_code' => $validated['tent_code'],
            'barangay_code' => $validated['barangay_code'] ?? null,
            'scanned_at' => now(),
        ]);

        // Mark tent as occupied
        OccupiedTent::updateOrCreate(
            ['tent_code' => $validated['tent_code']],
            ['barangay_code' => $validated['barangay_code'] ?? null]
        );

        return response()->json(['success' => true]);
    }
}
