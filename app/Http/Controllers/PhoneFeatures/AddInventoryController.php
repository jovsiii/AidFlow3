<?php

namespace App\Http\Controllers\PhoneFeatures;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

/** Supplies the mobile inventory form and creates inventory records from its inputs. */
class AddInventoryController extends Controller
{
    private const EXPIRATION_CATEGORIES = ['Food', 'Medicine', 'Drink', 'Hygiene'];

    private const ITEM_DEFAULTS = [
        'Toothbrush' => ['quantity' => 1500, 'unit' => 'Piece', 'category' => 'Hygiene'],
        'Toothpaste' => ['quantity' => 600, 'unit' => 'Piece', 'category' => 'Hygiene'],
        'Shampoo' => ['quantity' => 300, 'unit' => 'Bottle', 'category' => 'Hygiene'],
        'Bath Bar Soap' => ['quantity' => 1200, 'unit' => 'Piece', 'category' => 'Hygiene'],
        'Laundry Bar Soap' => ['quantity' => 300, 'unit' => 'Piece', 'category' => 'Equipment'],
        'Sanitary Napkin' => ['quantity' => 1200, 'unit' => 'Pack', 'category' => 'Hygiene'],
        'Comb' => ['quantity' => 300, 'unit' => 'Piece', 'category' => 'Hygiene'],
        'Disposable Shaving Razor' => ['quantity' => 300, 'unit' => 'Piece', 'category' => 'Hygiene'],
        'Nail Cutter' => ['quantity' => 300, 'unit' => 'Piece', 'category' => 'Hygiene'],
        'Bathroom Dipper' => ['quantity' => 300, 'unit' => 'Piece', 'category' => 'Equipment'],
        '20L Square Plastic Bucket with Deep Cover and Plastic Handle' => ['quantity' => 300, 'unit' => 'Piece', 'category' => 'Equipment'],
        'Blanket' => ['quantity' => 300, 'unit' => 'Piece', 'category' => 'Equipment'],
        'Mosquito Net' => ['quantity' => 300, 'unit' => 'Piece', 'category' => 'Equipment'],
        'Mat' => ['quantity' => 300, 'unit' => 'Piece', 'category' => 'Equipment'],
        'Kitchen Utensils' => ['quantity' => 300, 'unit' => 'Set', 'category' => 'Equipment'],
        'Rice' => ['quantity' => 1800, 'unit' => 'Kg', 'category' => 'Food'],
        'Canned Sardines' => ['quantity' => 1500, 'unit' => 'Can', 'category' => 'Food'],
        'Canned Tuna' => ['quantity' => 1500, 'unit' => 'Can', 'category' => 'Food'],
        'Canned Beef Loaf' => ['quantity' => 1500, 'unit' => 'Can', 'category' => 'Food'],
        'Coffee or Energy Drinks' => ['quantity' => 1500, 'unit' => 'Sachet', 'category' => 'Food'],
    ];

    public function __construct()
    {
    }

    /** Load distinct existing batch names for the inventory form. */
    public function index(FirebaseService $firebase)
    {
        $inventory = $firebase->getInventory();
        $batches = collect(is_array($inventory) ? $inventory : [])
            ->filter(fn ($item) => is_array($item) && filled($item['batch'] ?? null))
            ->pluck('batch')
            ->map(fn ($batch) => (string) $batch)
            ->unique()
            ->values();

        return view('phoneFeatures.addInventory', [
            'batches' => $batches,
            'expirationCategories' => self::EXPIRATION_CATEGORIES,
            'itemDefaults' => self::ITEM_DEFAULTS,
        ]);
    }

    /** Normalize supported form field names and save the validated inventory item. */
    public function store(Request $request, FirebaseService $firebase)
    {
        $isRice = $request->input('item_name', $request->input('name')) === 'Rice';
        $expirationRequired = !$isRice
            && in_array($request->input('category'), self::EXPIRATION_CATEGORIES, true);
        $expirationDateRules = $expirationRequired
            ? 'nullable|date|required_without:expirationDate'
            : 'nullable|date';
        $expirationDateAliasRules = $expirationRequired
            ? 'nullable|date|required_without:expiration_date'
            : 'nullable|date';

        $validated = $request->validate([
            'item_name' => 'nullable|string|required_without:name',
            'other_item_name' => 'nullable|string|required_if:item_name,Other',
            'name' => 'nullable|string|required_without:item_name',
            'quantity' => 'nullable|integer|min:0|required_without:stock',
            'stock' => 'nullable|integer|min:0|required_without:quantity',
            'category' => 'required|string',
            'unit' => 'required|string',
            'date_received' => 'nullable|date|required_without:received',
            'received' => 'nullable|date|required_without:date_received',
            'expiration_date' => $expirationDateRules,
            'expirationDate' => $expirationDateAliasRules,
            'batch_option' => 'required|in:existing,new',
            'batch' => 'nullable|string|max:100|required_if:batch_option,existing',
            'new_batch' => 'nullable|string|max:100|required_if:batch_option,new',
        ]);

        $batch = trim($validated['batch_option'] === 'new'
            ? $validated['new_batch']
            : $validated['batch']);

        $payload = [
            'name' => ($validated['item_name'] ?? null) === 'Other'
                ? $validated['other_item_name']
                : ($validated['item_name'] ?? $validated['name'] ?? null),
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'stock' => (int) ($validated['quantity'] ?? $validated['stock'] ?? 0),
            'startingStock' => (int) ($validated['quantity'] ?? $validated['stock'] ?? 0),
            'received' => $validated['date_received'] ?? $validated['received'] ?? null,
            'added' => now()->toDateString(),
            'removed' => null,
            'distributed' => null,
            'expirationDate' => $isRice
                ? null
                : ($validated['expiration_date'] ?? $validated['expirationDate'] ?? null),
            'batch' => $batch,
        ];

        $firebase->createInventory($payload);

        return redirect()->route('phoneFeatures.addInventory')->with('success', 'Item added successfully');
    }
}
