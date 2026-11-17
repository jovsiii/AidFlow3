<?php

namespace App\Http\Controllers\PhoneFeatures;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;
use Illuminate\Http\Request;

/** Provides relief-pack scanning and distribution with inventory validation. */
class ReliefGoodsController extends Controller
{
    public function __construct()
    {
    }

    /** Display the mobile relief-goods scanner. */
    public function index()
    {
        return view('phoneFeatures.reliefGoods');
    }

    /** Consume a scanned pack and report duplicate scans or insufficient stock. */
    public function scan(Request $request, FirebaseService $firebase, ReliefPackCalculator $calculator)
    {
        $validated = $request->validate([
            'package_id' => ['required', 'string', 'max:50', 'regex:/^#\d+$/'],
        ]);

        $packNumber = (int) ltrim($validated['package_id'], '#');
        $accountName = $request->session()->get('user.fullName')
            ?: $request->session()->get('user.username')
            ?: $request->session()->get('username');
        $planItems = $firebase->getReliefPackPlan()['items'] ?? $calculator->requiredItemDetails();
        $result = $firebase->consumeReliefPack($packNumber, $planItems, $accountName);

        if ($result['status'] === 'already_scanned') {
            return response()->json([
                'success' => true,
                'already_scanned' => true,
                'message' => "Relief pack #{$packNumber} was already distributed.",
            ]);
        }

        if ($result['status'] === 'insufficient_stock') {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock for {$result['item']}.",
            ], 422);
        }

        if ($result['status'] !== 'consumed') {
            return response()->json([
                'success' => false,
                'message' => 'Firebase could not update inventory. Please try again.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => "Relief pack #{$packNumber} distributed and inventory updated.",
        ]);
    }
}
