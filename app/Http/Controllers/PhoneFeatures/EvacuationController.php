<?php

namespace App\Http\Controllers\PhoneFeatures;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

class EvacuationController extends Controller
{
    /** Display the mobile evacuation scanner. */
    public function index()
    {
        return view('phoneFeatures.evacuation');
    }

    public function scan(Request $request, FirebaseService $firebase)
    {
        $validated = $request->validate([
            'tent_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'barangay_code' => 'required|string|max:10',
            'barangay_name' => 'required|string|max:100',
            'full_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L} ]+$/u'],
            'household_size' => 'required|integer|min:1|max:100',
            'confirm_unoccupy' => 'sometimes|boolean',
        ]);

        $tentCode = strtoupper(trim($validated['tent_code']));
        $barangayCode = strtoupper($validated['barangay_code']);
        $barangayName = $validated['barangay_name'];
        $occupied = $firebase->getOccupiedTent($tentCode);
        $confirmUnoccupy = $validated['confirm_unoccupy'] ?? false;

        if ($confirmUnoccupy && !$occupied) {
            return response()->json([
                'success' => true,
                'action' => 'unoccupied',
                'message' => "Tent {$tentCode} is already unoccupied.",
            ]);
        }

        if ($occupied && !$confirmUnoccupy) {
            return response()->json([
                'success' => false,
                'requires_confirmation' => true,
                'action' => 'unoccupied',
                'message' => "Tent {$tentCode} is already occupied. Confirm to mark it unoccupied.",
            ]);
        }

        $scannedAt = now();
        $willBeOccupied = !$occupied;
        $firebaseData = [
            'tentCode' => $tentCode,
            'barangayCode' => $barangayCode,
            'barangayName' => $barangayName,
            'scannedAt' => $scannedAt->toISOString(),
            'accountName' => $request->session()->get('user.fullName')
                ?: $request->session()->get('user.username')
                ?: $request->session()->get('username'),
        ];
        $familyProfile = [
            'fullName' => trim($validated['full_name']),
            'headOfFamily' => trim($validated['full_name']),
            'householdSize' => (int) $validated['household_size'],
            'tentCode' => $tentCode,
            'barangayCode' => $barangayCode,
            'barangay' => $barangayName,
            'updatedAt' => $scannedAt->toISOString(),
        ];

        if (!$firebase->recordTentScan(
            $tentCode,
            $firebaseData,
            $willBeOccupied,
            $willBeOccupied ? $familyProfile : null
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Firebase could not record the scan. Please try again.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'action' => $willBeOccupied ? 'occupied' : 'unoccupied',
            'message' => $willBeOccupied
                ? "Tent {$tentCode} marked occupied and family profile saved."
                : "Tent {$tentCode} marked unoccupied.",
        ]);
    }

}