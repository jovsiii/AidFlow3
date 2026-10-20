@extends('features.layout')

@section('title', 'Inventory - AidFlow')
@section('page-title', 'Inventory')

@section('content')
    @php
        $today = now()->startOfDay();
    @endphp

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($lowStockAlerts->isNotEmpty() || $nearExpiryAlerts->isNotEmpty() || $standardReliefPackSupply <= $minimumStandardReliefPacks)
        <section id="inventoryAlerts" role="alert"
            class="fixed right-4 top-4 z-40 max-h-[80vh] w-[calc(100vw-2rem)] max-w-xl overflow-y-auto rounded-lg border-2 border-amber-500 bg-amber-50 p-4 text-amber-950 shadow-xl">
            <div class="mb-2 flex items-center justify-between gap-4">
                <h2 class="text-lg font-bold">Inventory Alerts</h2>
                <button type="button" id="closeInventoryAlerts" aria-label="Close inventory alerts"
                    class="rounded px-2 py-1 text-xl font-bold leading-none text-amber-950 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-700">
                    &times;
                </button>
            </div>
            @if ($lowStockAlerts->isNotEmpty())
                <h3 class="font-semibold">Low stock</h3>
                <ul class="mb-2 list-disc pl-5">
                    @foreach ($lowStockAlerts as $alertItem)
                        <li>
                            {{ $alertItem['name'] ?? 'Unnamed Item' }} has {{ $alertItem['stock'] ?? 0 }}
                            remaining (minimum {{ $alertItem['minimumStock'] ?? 50 }})
                            @if (filled($alertItem['batch'] ?? null))
                                — Batch {{ $alertItem['batch'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($nearExpiryAlerts->isNotEmpty())
                <h3 class="font-semibold">Near expiry (within 3 months)</h3>
                <ul class="mb-2 list-disc pl-5">
                    @foreach ($nearExpiryAlerts as $alertItem)
                        <li>
                            {{ $alertItem['name'] ?? 'Unnamed Item' }} expires
                            {{ \Carbon\Carbon::parse($alertItem['expirationDate'])->format('m/d/Y') }}
                            @if (filled($alertItem['batch'] ?? null))
                                — Batch {{ $alertItem['batch'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($standardReliefPackSupply <= $minimumStandardReliefPacks)
                <p>
                    Only {{ $standardReliefPackSupply }} standard relief
                    {{ \Illuminate\Support\Str::plural('pack', $standardReliefPackSupply) }} can be assembled (minimum
                    {{ $minimumStandardReliefPacks }}).
                </p>
            @endif
        </section>
    @endif

    <div class="flex flex-row flex-wrap lg:flex-nowrap gap-2">
        <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 flex items-center shadow-sm w-full">
            <div class="shrink-0 mr-4">
                <div
                    class="w-15 h-15 rounded-[18px] border-[3px] border-[#8a1c1c] bg-[#ff6b6b] flex items-center justify-center">
                    <i class="fas fa-warehouse text-2xl text-[#1f0000]"></i>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-700 mb-0.5 whitespace-nowrap">Total Inventory Items</p>
                <p class="text-[28px] leading-none font-medium">{{ number_format($totalItems ?? 0) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 flex items-center shadow-sm w-full">
            <div class="shrink-0 mr-4">
                <div
                    class="w-15 h-15 rounded-[18px] border-[3px] border-[#8a1c1c] bg-[#ff6b6b] flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-2xl text-[#1f0000]"></i>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-700 mb-0.5 whitespace-nowrap">Standard relief packs</p>
                <p class="text-[28px] leading-none font-medium">{{ number_format($standardReliefPacks ?? 0) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 flex items-center shadow-sm w-full">
            <div class="shrink-0 mr-4">
                <div
                    class="w-15 h-15 rounded-[18px] border-[3px] border-[#8a1c1c] bg-[#ff6b6b] flex items-center justify-center">
                    <i class="fas fa-campground text-2xl text-[#1f0000]"></i>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-700 mb-0.5 whitespace-nowrap">Low Stock Items</p>
                <p class="text-[28px] leading-none font-medium">{{ number_format($lowStockItems ?? 0) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 flex items-center shadow-sm w-full">
            <div class="shrink-0 mr-4">
                <div
                    class="w-15 h-15 rounded-[18px] border-[3px] border-[#8a1c1c] bg-[#ff6b6b] flex items-center justify-center">
                    <i class="fas fa-university text-2xl text-[#1f0000]"></i>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-700 mb-0.5 whitespace-nowrap">Near Expiry Items</p>
                <p class="text-[28px] leading-none font-medium">{{ number_format($nearExpiryItems ?? 0) }}</p>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-wrap gap-3 items-center mb-4 mt-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Item"
            class="flex-1 min-w-[220px] rounded-full pl-4 py-2 border-2 border-pink-200" />
        <select name="category" class="px-3 py-2 rounded-md border-2 border-pink-200">
            <option value="">Category</option>
            @foreach (collect($inventoryItems)->pluck('category')->filter()->unique()->sort() as $category)
                <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 rounded-md border-2 border-pink-200">
            <option value="">Status</option>
            <option value="good" @selected(request('status') === 'good')>Good</option>
            <option value="near_expiry" @selected(request('status') === 'near_expiry')>Near Expiry</option>
            <option value="expired" @selected(request('status') === 'expired')>Expired</option>
        </select>
        <button type="submit"
            class="px-4 py-2 rounded-md bg-pink-200 border-2 border-red-600 text-red-800 font-semibold">Filter</button>
        <a href="{{ route('inventory.index') }}"
            class="px-4 py-2 rounded-md bg-gray-200 border-2 border-gray-400 text-gray-700">Reset</a>
    </form>

    <div class="flex flex-row gap-4">
        <div
            class="flex-1 flex flex-col bg-white rounded-lg border-2 border-red-600 p-4 min-h-[calc(82vh-120px)] max-h-[calc(82vh-120px)]">
            <h3 class="text-red-700 text-xl font-semibold mb-2">Inventory List</h3>
            <div class="overflow-auto h-full max-h-full border border-red-200 rounded-md p-2">
                <table class="w-full text-sm divide-y divide-gray-100">
                    <thead class="text-red-800 font-semibold">
                        <tr>
                            <th class="py-2 text-left">Item Name</th>
                            <th class="py-2 text-left">Category</th>
                            <th class="py-2 text-left">Unit</th>
                            <th class="py-2 text-left">Stock</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        @forelse ($inventoryListItems as $item)
                            <tr class="border-b">
                                <td class="py-2">
                                    <button type="button"
                                        class="inventory-details-toggle cursor-pointer text-left text-red-700 underline decoration-dotted underline-offset-2 hover:text-red-900"
                                        aria-expanded="false" aria-controls="inventory-details-{{ $loop->index }}">
                                        {{ $item['name'] ?? 'Unnamed Item' }}
                                    </button>
                                </td>
                                <td>{{ $item['category'] ?? 'Uncategorized' }}</td>
                                <td>{{ $item['unit'] ?? '-' }}</td>
                                <td>{{ $item['stock'] ?? 0 }}</td>
                            </tr>
                            <tr id="inventory-details-{{ $loop->index }}" class="hidden bg-red-200">
                                <td colspan="4" class="p-3">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-left">
                                            <thead class="text-red-800">
                                                <tr>
                                                    <th class="px-3 py-2">Expiration Date</th>
                                                    <th class="px-3 py-2">Status</th>
                                                    <th class="px-3 py-2">Stock</th>
                                                    <th class="px-3 py-2">Minimum Stock</th>
                                                    <th class="px-3 py-2">Batch</th>
                                                    <th class="px-3 py-2">Configure Alert</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($item['records'] as $record)
                                                    @php
                                                        $expirationDate = $record['expirationDate'] ?? null;
                                                        $parsedExpirationDate = $expirationDate
                                                            ? \Carbon\Carbon::parse($expirationDate)
                                                            : null;
                                                        $status = !$parsedExpirationDate || $parsedExpirationDate->gt($today->copy()->addMonths(3))
                                                            ? 'Good'
                                                            : ($parsedExpirationDate->lt($today)
                                                                ? 'Expired'
                                                                : 'Near Expiry');
                                                    @endphp
                                                    <tr class="border-t border-red-200">
                                                        <td class="px-3 py-2">
                                                            {{ $parsedExpirationDate ? $parsedExpirationDate->format('m/d/Y') : '—' }}
                                                        </td>
                                                        <td class="px-3 py-2">{{ $status }}</td>
                                                        <td class="px-3 py-2">{{ $record['stock'] ?? 0 }}</td>
                                                        <td class="px-3 py-2">{{ $record['minimumStock'] ?? 50 }}</td>
                                                        <td class="px-3 py-2">{{ $record['batch'] ?? '—' }}</td>
                                                        <td class="px-3 py-2">
                                                            <form method="POST"
                                                                action="{{ route('inventory.update', $record['id']) }}"
                                                                class="flex items-center gap-2">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="name"
                                                                    value="{{ $record['name'] ?? '' }}">
                                                                <input type="hidden" name="category"
                                                                    value="{{ $record['category'] ?? '' }}">
                                                                <input type="hidden" name="unit"
                                                                    value="{{ $record['unit'] ?? '' }}">
                                                                <input type="hidden" name="stock"
                                                                    value="{{ $record['stock'] ?? 0 }}">
                                                                <input type="hidden" name="received"
                                                                    value="{{ $record['received'] ?? '' }}">
                                                                <input type="hidden" name="expirationDate"
                                                                    value="{{ $record['expirationDate'] ?? '' }}">
                                                                <label class="sr-only"
                                                                    for="minimum-stock-{{ $record['id'] }}">Minimum stock for
                                                                    {{ $record['name'] ?? 'item' }}</label>
                                                                <input id="minimum-stock-{{ $record['id'] }}" name="minimumStock"
                                                                    type="number" min="0"
                                                                    value="{{ $record['minimumStock'] ?? 50 }}" required
                                                                    class="w-20 rounded border border-red-300 px-2 py-1">
                                                                <button type="submit"
                                                                    class="rounded bg-red-700 px-2 py-1 text-white hover:bg-red-800">Save</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">No inventory items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex gap-3 justify-center mt-3">

                @if ($inventoryItems->isNotEmpty())
                    <button type="button" id="openExpiredModal"
                        class="cursor-pointer px-4 py-2 rounded-full bg-pink-100 border-2 border-red-600 text-red-800 font-bold">View
                        Expired Items</button>
                    {{-- <button type="button" id="openBatchModal"
                        class="cursor-pointer px-4 py-2 rounded-full bg-pink-100 border-2 border-red-600 text-red-800 font-bold">View
                        Batch</button> --}}
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4 w-80 h-[calc(82vh-120px)] min-h-0">
            <div class="bg-white rounded-lg border-2 border-red-600 p-4">
                <div class="text-red-800 text-lg font-semibold mb-2">Stock Status Overview</div>
                <div class="flex gap-3 items-center">
                    <svg class="w-40 h-40" viewBox="0 0 42 42" width="160" height="160">
                        <circle r="15.9" cx="21" cy="21" fill="transparent" stroke="#e9caca" stroke-width="8" />
                        <circle r="15.9" cx="21" cy="21" fill="transparent" stroke="#2fa84f" stroke-width="8"
                            stroke-dasharray="{{ $goodPercent }} {{ 100 - $goodPercent }}" stroke-dashoffset="25"
                            transform="rotate(-90 21 21)" />
                        <circle r="15.9" cx="21" cy="21" fill="transparent" stroke="#f2b24a" stroke-width="8"
                            stroke-dasharray="{{ $nearExpiryPercent }} {{ 100 - $nearExpiryPercent }}"
                            stroke-dashoffset="{{ 25 - $goodPercent }}" transform="rotate(-90 21 21)" />
                        <circle r="15.9" cx="21" cy="21" fill="transparent" stroke="#e04b4b" stroke-width="8"
                            stroke-dasharray="{{ $expiredPercent }} {{ 100 - $expiredPercent }}"
                            stroke-dashoffset="{{ 25 - $goodPercent - $nearExpiryPercent }}"
                            transform="rotate(-90 21 21)" />
                        <text x="21" y="22" font-size="6" text-anchor="middle"
                            fill="#7a0b0b">{{ number_format($totalItems ?? 0) }}</text>
                    </svg>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2"><span
                                class="w-3 h-3 bg-green-500 inline-block rounded-sm"></span> Good <strong
                                class="ml-2">{{ number_format($goodItems ?? 0) }}</strong></div>
                        <div class="flex items-center gap-2"><span
                                class="w-3 h-3 bg-yellow-400 inline-block rounded-sm"></span> Near Expiry <strong
                                class="ml-2">{{ number_format($nearExpiryItems ?? 0) }}</strong></div>
                        <div class="flex items-center gap-2"><span
                                class="w-3 h-3 bg-red-500 inline-block rounded-sm"></span> Expired <strong
                                class="ml-2">{{ number_format($expiredItems ?? 0) }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border-2 border-red-600 p-4">
                <div class="text-red-800 text-lg font-semibold mb-2">Category Breakdown</div>
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center gap-3">
                        <div class="text-lg">Food</div>
                        <div class="font-bold">{{ number_format($foodCount ?? 0) }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-lg">Non-Food</div>
                        <div class="font-bold">{{ number_format($nonFoodCount ?? 0) }}</div>
                    </div>
                </div>
                <div class="border-t border-pink-100 pt-3 flex justify-between items-center">
                    <div>Total</div>
                    <div class="bg-pink-100 px-3 py-1 rounded-md border-2 border-red-600">
                        {{ number_format($totalItems ?? 0) }}
                    </div>
                </div>
            </div>
            <div
                class="bg-white rounded-[18px] border-[3px] border-[#ef4444] p-4 flex-1 min-h-0 overflow-auto">
                <div class="mb-2 text-[18px] font-bold text-[#8a1c1c] sticky top-0 bg-white py-2">Inventory Ledger</div>
                <div class="flex flex-col divide-y divide-[#f0b5b5]">
                    @forelse ($inventoryItems as $item)
                        @php
                            $distributed = $item['distributed'] ?? false;
                            $removed = $item['removed'] ?? false;

                            $statusLabel = $distributed ? 'Distributed' : ($removed ? 'Removed' : 'Added');
                            $statusDate = $distributed ? $item['distributed'] : ($removed ? $item['removed'] : $item['added']);
                            $statusClass = $distributed
                                ? 'text-blue-600'
                                : ($removed ? 'text-gray-600' : 'text-green-600');
                        @endphp

                        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-2 py-2.5">
                            <div class="min-w-0 overflow-hidden">
                                <div class="truncate text-[18px] font-semibold text-[#111827]"
                                    title="{{ $item['name'] ?? 'Unnamed Item' }}">
                                    {{ $item['name'] ?? 'Unnamed Item' }}
                                </div>
                                <div class="text-[12px] text-gray-600">{{ $item['category'] ?? 'Uncategorized' }}</div>
                            </div>
                            <div class="shrink-0 whitespace-nowrap text-right text-[12px] font-semibold {{ $statusClass }}">
                                {{ $statusLabel }} on
                                {{ $statusDate ? \Carbon\Carbon::parse($statusDate)->format('m/d/y') : '—' }}
                            </div>
                        </div>
                    @empty
                        <div class="py-4 text-center text-gray-500">No inventory items found.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div id="expiredModal"
            class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="w-full max-w-5xl rounded-2xl border-4 border-red-600 bg-white shadow-2xl overflow-hidden">
                <div class="px-6 py-5 text-white">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-[0.35em] text-red-400">Expired Items</div>
                            <h3 class="text-3xl font-bold text-red-600">Expired Inventory</h3>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="overflow-hidden rounded border border-red-600 bg-[#ffdfe1] shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left">
                                <thead class="text-red-700">
                                    <tr>
                                        <th class="px-5 py-4">Item</th>
                                        <th class="px-5 py-4">Category</th>
                                        <th class="px-5 py-4">Quantity</th>
                                        <th class="px-5 py-4">Exp Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $expiredItemsForModal = $inventoryItems->filter(function ($item) use ($today) {
                                            $expirationDate = $item['expirationDate'] ?? null;

                                            return $expirationDate && \Carbon\Carbon::parse($expirationDate)->lt($today);
                                        })->values();
                                    @endphp
                                    @forelse($expiredItemsForModal as $item)
                                        <tr class="border-t border-red-100">
                                            <td class="px-5 py-4 text-gray-900">{{ $item['name'] ?? 'Unnamed Item' }}</td>
                                            <td class="px-5 py-4 text-gray-900">{{ $item['category'] ?? 'Uncategorized' }}</td>
                                            <td class="px-5 py-4 text-gray-900">{{ $item['stock'] ?? 0 }}</td>
                                            <td class="px-5 py-4 text-gray-900">
                                                {{ $item['expirationDate'] ? \Carbon\Carbon::parse($item['expirationDate'])->format('m/d/y') : '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-5 py-8 text-center text-gray-500">No expired items found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                        <button type="button" id="expiredCloseBtn"
                            class="cursor-pointer inline-flex items-center justify-center rounded-full border border-red-700 bg-white px-6 py-3 text-red-700 font-semibold transition hover:bg-red-50">Back
                            to Inventory</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="batchModal"
            class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="w-full max-w-5xl rounded-2xl border-4 border-red-600 bg-white shadow-2xl overflow-hidden">
                <div class="px-6 py-5 text-white">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-[0.35em] text-red-400">Table of Item Batch</div>
                            <h3 class="text-3xl font-bold text-red-600">Batch Details</h3>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @if ($batchGroups->isNotEmpty())
                        <div class="mb-4 flex flex-wrap gap-3" role="tablist" aria-label="Inventory batches">
                            @foreach ($batchGroups as $batchName => $batchItems)
                                <button type="button" role="tab" data-batch-target="batch-panel-{{ $loop->index }}"
                                    class="batch-tab rounded-full px-4 py-2 {{ $loop->first ? 'bg-red-700 text-white font-semibold' : 'bg-red-50 text-red-700' }}">
                                    Batch {{ $batchName }} ({{ $batchItems->count() }})
                                </button>
                            @endforeach
                        </div>

                        @foreach ($batchGroups as $batchName => $batchItems)
                            <div id="batch-panel-{{ $loop->index }}"
                                class="batch-panel {{ $loop->first ? '' : 'hidden' }} overflow-hidden rounded border border-red-600 bg-[#ffdfe1] shadow-sm">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm text-left">
                                        <thead class="text-red-700">
                                            <tr>
                                                <th class="px-5 py-4">Item</th>
                                                <th class="px-5 py-4">Category</th>
                                                <th class="px-5 py-4">Quantity</th>
                                                <th class="px-5 py-4">Exp Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($batchItems as $item)
                                                <tr class="border-t border-red-100">
                                                    <td class="px-5 py-4 text-gray-900">{{ $item['name'] ?? 'Unnamed Item' }}</td>
                                                    <td class="px-5 py-4 text-gray-900">{{ $item['category'] ?? 'Uncategorized' }}</td>
                                                    <td class="px-5 py-4 text-gray-900">{{ $item['stock'] ?? 0 }}</td>
                                                    <td class="px-5 py-4 text-gray-900">
                                                        {{ !empty($item['expirationDate']) ? \Carbon\Carbon::parse($item['expirationDate'])->format('m/d/y') : '�' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="rounded border border-red-200 bg-red-50 px-5 py-8 text-center text-gray-600">
                            No batched inventory items yet. Add an item and choose or create a batch.
                        </div>
                    @endif
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                        <button type="button" id="batchSaveBtn"
                            class="cursor-pointer inline-flex items-center justify-center rounded-full bg-red-700 px-6 py-3 text-white font-semibold transition hover:bg-red-800">Save</button>
                        <button type="button" id="batchCloseBtn"
                            class="cursor-pointer inline-flex items-center justify-center rounded-full border border-red-700 bg-white px-6 py-3 text-red-700 font-semibold transition hover:bg-red-50">Back
                            to Inventory</button>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                const expiredModal = document.getElementById('expiredModal');
                const openExpiredModalBtn = document.getElementById('openExpiredModal');
                const closeExpiredModalBtn = document.getElementById('closeExpiredModal');
                const expiredCloseBtn = document.getElementById('expiredCloseBtn');
                const batchModal = document.getElementById('batchModal');
                const openBatchModalBtn = document.getElementById('openBatchModal');
                const closeBatchModalBtn = document.getElementById('closeBatchModal');
                const batchCloseBtn = document.getElementById('batchCloseBtn');
                const batchSaveBtn = document.getElementById('batchSaveBtn');
                const batchTabButtons = document.querySelectorAll('.batch-tab');
                const batchPanels = document.querySelectorAll('.batch-panel');
                const inventoryDetailsButtons = document.querySelectorAll('.inventory-details-toggle');
                const inventoryAlerts = document.getElementById('inventoryAlerts');
                const closeInventoryAlertsBtn = document.getElementById('closeInventoryAlerts');

                closeInventoryAlertsBtn?.addEventListener('click', () => {
                    inventoryAlerts?.classList.add('hidden');
                });

                inventoryDetailsButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const details = document.getElementById(button.getAttribute('aria-controls'));
                        const isExpanded = button.getAttribute('aria-expanded') === 'true';

                        details?.classList.toggle('hidden', isExpanded);
                        button.setAttribute('aria-expanded', String(!isExpanded));
                    });
                });

                function openExpiredModal() {
                    expiredModal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }

                function closeExpiredModal() {
                    expiredModal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }

                function openBatchModal() {
                    batchModal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }

                function closeBatchModal() {
                    batchModal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }

                openExpiredModalBtn?.addEventListener('click', openExpiredModal);
                closeExpiredModalBtn?.addEventListener('click', closeExpiredModal);
                expiredCloseBtn?.addEventListener('click', closeExpiredModal);
                openBatchModalBtn?.addEventListener('click', openBatchModal);
                closeBatchModalBtn?.addEventListener('click', closeBatchModal);
                batchCloseBtn?.addEventListener('click', closeBatchModal);
                batchSaveBtn?.addEventListener('click', closeBatchModal);

                expiredModal?.addEventListener('click', (event) => {
                    if (event.target === expiredModal) {
                        closeExpiredModal();
                    }
                });

                batchModal?.addEventListener('click', (event) => {
                    if (event.target === batchModal) {
                        closeBatchModal();
                    }
                });

                batchTabButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        batchTabButtons.forEach((tab) => {
                            tab.classList.remove('bg-red-700', 'text-white', 'font-semibold');
                            tab.classList.add('bg-red-50', 'text-red-700');
                        });
                        batchPanels.forEach((panel) => panel.classList.add('hidden'));

                        button.classList.remove('bg-red-50', 'text-red-700');
                        button.classList.add('bg-red-700', 'text-white', 'font-semibold');
                        document.getElementById(button.dataset.batchTarget)?.classList.remove('hidden');
                    });
                });
            </script>
        @endpush
@endsection