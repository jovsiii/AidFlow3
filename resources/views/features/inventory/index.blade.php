@extends('features.layout')

@section('title', 'Inventory - AidFlow')
@section('page-title', 'Inventory')

@section('content')
    @php
        $today = now()->startOfDay();
        $inventoryMetrics = [
            [
                'label' => 'Total inventory items',
                'value' => $totalItems ?? 0,
                'icon' => 'fa-boxes-stacked',
                'tone' => 'bg-red-50 text-red-700',
            ],
            [
                'label' => 'Standard relief packs',
                'value' => $standardReliefPacks ?? 0,
                'icon' => 'fa-kit-medical',
                'tone' => 'bg-blue-50 text-blue-700',
            ],
            [
                'label' => 'Low stock items',
                'value' => $lowStockItems ?? 0,
                'icon' => 'fa-triangle-exclamation',
                'tone' => 'bg-amber-50 text-amber-700',
            ],
            [
                'label' => 'Near expiry items',
                'value' => $nearExpiryItems ?? 0,
                'icon' => 'fa-clock',
                'tone' => 'bg-orange-50 text-orange-700',
            ],
        ];
    @endphp

    @if (session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm">
            <i class="fas fa-circle-check" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($lowStockAlerts->isNotEmpty() || $nearExpiryAlerts->isNotEmpty() || $standardReliefPackSupply <= $minimumStandardReliefPacks)
        <section id="inventoryAlerts" role="alert"
            class="mb-5 max-h-[60vh] overflow-y-auto rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-950 shadow-sm sm:p-5">
            <div class="mb-3 flex items-center justify-between gap-4">
                <h2 class="flex items-center gap-2 text-lg font-bold">
                    <i class="fas fa-triangle-exclamation text-amber-600" aria-hidden="true"></i>
                    Inventory Alerts
                </h2>
                <button type="button" id="closeInventoryAlerts" aria-label="Close inventory alerts"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-xl font-bold leading-none text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-700">
                    &times;
                </button>
            </div>
            @if ($lowStockAlerts->isNotEmpty())
                <h3 class="font-semibold">Low stock</h3>
                <ul class="mb-3 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($lowStockAlerts as $alertItem)
                        <li>
                            {{ $alertItem['name'] ?? 'Unnamed Item' }} has {{ $alertItem['stock'] ?? 0 }}
                            remaining (low-stock threshold: {{ $alertItem['lowStockThreshold'] }}, 25% of
                            {{ $alertItem['startingStock'] }} starting stock)
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($nearExpiryAlerts->isNotEmpty())
                <h3 class="font-semibold">Near expiry (within 3 months)</h3>
                <ul class="mb-3 list-disc space-y-1 pl-5 text-sm">
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
                <p>Only {{ $standardReliefPackSupply }} standard relief {{ \Illuminate\Support\Str::plural('pack', $standardReliefPackSupply) }} can be assembled (minimum {{ $minimumStandardReliefPacks }}).</p>
            @endif
        </section>
    @endif

    <div class="space-y-6">
        <section class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-red-700">Supply management</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Inventory</h2>
                <p class="mt-1 text-sm text-slate-500">Monitor available supplies, stock levels, and expiration dates.</p>
            </div>
            <span class="text-sm text-slate-500">{{ number_format($inventoryListItems->count()) }} item groups shown</span>
        </section>

        <section id="inventory-overview" aria-label="Inventory overview"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($inventoryMetrics as $metric)
                <article class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $metric['tone'] }}">
                        <i class="fas {{ $metric['icon'] }} text-lg" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($metric['value']) }}</p>
                    </div>
                </article>
            @endforeach
        </section>

        <form id="inventory-filters" method="GET" action="{{ route('inventory.index') }}"
            class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-[minmax(14rem,1fr)_minmax(10rem,0.65fr)_minmax(10rem,0.65fr)_auto] xl:items-end">
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search inventory</span>
                <span class="flex h-11 items-center gap-2 rounded-xl border border-slate-300 px-3 transition focus-within:border-red-700 focus-within:ring-2 focus-within:ring-red-100">
                    <i class="fas fa-search text-slate-400" aria-hidden="true"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Item name or category"
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0" />
                </span>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</span>
                <select name="category"
                    class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-red-700 focus:outline-none focus:ring-2 focus:ring-red-100">
                    <option value="">All categories</option>
                    @foreach (collect($inventoryItems)->pluck('category')->filter()->unique()->sort() as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Stock status</span>
                <select name="status"
                    class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-red-700 focus:outline-none focus:ring-2 focus:ring-red-100">
                    <option value="">All statuses</option>
                    <option value="good" @selected(request('status') === 'good')>Good</option>
                    <option value="near_expiry" @selected(request('status') === 'near_expiry')>Near Expiry</option>
                    <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                </select>
            </label>
            <div class="flex flex-wrap gap-2 sm:col-span-2 xl:col-span-1">
                <button type="submit"
                    class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-red-800 px-5 text-sm font-semibold text-white transition hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-300 sm:flex-none">
                    <i class="fas fa-filter" aria-hidden="true"></i> Apply filters
                </button>
                <a href="{{ route('inventory.index') }}"
                    class="inline-flex h-11 flex-1 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:flex-none">Reset</a>
            </div>
        </form>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_21rem]">
            <section id="inventory-table" aria-labelledby="inventory-list-heading"
                class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 id="inventory-list-heading" class="text-lg font-bold text-slate-900">Inventory list</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Select an item to see batch and expiration details.</p>
                    </div>
                    @if ($inventoryItems->isNotEmpty())
                        <button type="button" id="openExpiredModal"
                            class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3.5 py-2 text-sm font-semibold text-red-800 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-200">
                            <i class="fas fa-calendar-xmark" aria-hidden="true"></i> View expired items
                        </button>
                    @endif
                </div>
                <div class="max-h-[68vh] overflow-auto">
                    <table class="w-full min-w-[38rem] text-left text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Item name</th>
                            <th class="px-5 py-3 font-semibold">Category</th>
                            <th class="px-5 py-3 font-semibold">Unit</th>
                            <th class="px-5 py-3 text-right font-semibold">Available stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($inventoryListItems as $item)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3.5 font-semibold">
                                    <button type="button"
                                        class="inventory-details-toggle inline-flex items-center gap-2 text-left text-slate-900 transition hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-200"
                                        aria-expanded="false" aria-controls="inventory-details-{{ $loop->index }}">
                                        {{ $item['name'] ?? 'Unnamed Item' }}
                                        <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform" aria-hidden="true"></i>
                                    </button>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $item['category'] ?? 'Uncategorized' }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-500">{{ $item['unit'] ?? '-' }}</td>
                                <td class="px-5 py-3.5 text-right font-semibold tabular-nums text-slate-900">
                                    {{ number_format($item['stock'] ?? 0) }}
                                </td>
                            </tr>
                            <tr id="inventory-details-{{ $loop->index }}" class="hidden bg-slate-50">
                                <td colspan="4" class="px-5 py-4">
                                    <div class="overflow-x-auto">
                                        <table class="w-full min-w-[34rem] overflow-hidden rounded-xl border border-slate-200 bg-white text-left text-xs">
                                            <thead class="bg-slate-100 text-slate-600">
                                                <tr>
                                                    <th class="px-3 py-2.5 font-semibold">Expiration Date</th>
                                                    <th class="px-3 py-2.5 font-semibold">Status</th>
                                                    <th class="px-3 py-2.5 font-semibold">Stock</th>
                                                    <th class="px-3 py-2.5 font-semibold">Starting stock</th>
                                                    <th class="px-3 py-2.5 font-semibold">Batch</th>
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
                                                    <tr class="border-t border-slate-100">
                                                        <td class="px-3 py-2.5">
                                                            {{ $parsedExpirationDate ? $parsedExpirationDate->format('m/d/Y') : '—' }}
                                                        </td>
                                                        <td class="px-3 py-2.5">
                                                            <span @class([
                                                                'inline-flex rounded-full px-2 py-0.5 font-semibold',
                                                                'bg-emerald-50 text-emerald-700' => $status === 'Good',
                                                                'bg-amber-50 text-amber-700' => $status === 'Near Expiry',
                                                                'bg-red-50 text-red-700' => $status === 'Expired',
                                                            ])>{{ $status }}</span>
                                                        </td>
                                                        <td class="px-3 py-2.5 tabular-nums">{{ number_format($record['stock'] ?? 0) }}</td>
                                                        <td class="px-3 py-2.5 tabular-nums">{{ number_format($record['startingStock'] ?? $record['stock'] ?? 0) }}</td>
                                                        <td class="px-3 py-2.5">{{ $record['batch'] ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center">
                                    <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <i class="fas fa-box-open" aria-hidden="true"></i>
                                    </span>
                                    <span class="block font-semibold text-slate-700">No inventory items found</span>
                                    <span class="mt-1 block text-sm text-slate-500">Try changing or clearing your filters.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </section>

            <aside class="flex flex-col gap-5">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-base font-bold text-slate-900">Stock status overview</h3>
                        <p class="mt-1 text-sm text-slate-500">Expiration status across tracked items.</p>
                    </div>
                    <div class="flex flex-col items-center gap-4 sm:flex-row xl:flex-col 2xl:flex-row">
                    <svg class="h-36 w-36 shrink-0" viewBox="0 0 42 42" width="144" height="144" role="img"
                        aria-label="{{ number_format($totalItems ?? 0) }} items: {{ number_format($goodItems ?? 0) }} good, {{ number_format($nearExpiryItems ?? 0) }} near expiry, {{ number_format($expiredItems ?? 0) }} expired">
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
                        <text x="21" y="22" font-size="6" font-weight="700" text-anchor="middle"
                            fill="#0f172a">{{ number_format($totalItems ?? 0) }}</text>
                    </svg>
                    <div class="w-full space-y-3">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Good</span>
                            <strong class="tabular-nums text-slate-900">{{ number_format($goodItems ?? 0) }}</strong>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>Near expiry</span>
                            <strong class="tabular-nums text-slate-900">{{ number_format($nearExpiryItems ?? 0) }}</strong>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Expired</span>
                            <strong class="tabular-nums text-slate-900">{{ number_format($expiredItems ?? 0) }}</strong>
                        </div>
                    </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-base font-bold text-slate-900">Category breakdown</h3>
                        <p class="mt-1 text-sm text-slate-500">Items grouped by supply type.</p>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-600">Food</span>
                                <strong class="tabular-nums text-slate-900">{{ number_format($foodCount ?? 0) }}</strong>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-red-700"
                                    style="width: {{ ($totalItems ?? 0) > 0 ? min(100, ($foodCount / $totalItems) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-600">Non-food</span>
                                <strong class="tabular-nums text-slate-900">{{ number_format($nonFoodCount ?? 0) }}</strong>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-slate-500"
                                    style="width: {{ ($totalItems ?? 0) > 0 ? min(100, ($nonFoodCount / $totalItems) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
                        <span class="font-medium text-slate-500">Total</span>
                        <span class="font-bold tabular-nums text-slate-900">{{ number_format($totalItems ?? 0) }}</span>
                    </div>
                </section>
            </aside>
        </div>
    </div>

        <div id="expiredModal"
            class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-red-700">Inventory review</div>
                            <h3 class="mt-1 text-2xl font-bold text-slate-900">Expired Inventory</h3>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="max-h-[60vh] overflow-auto rounded-xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="sticky top-0 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3 font-semibold">Item</th>
                                        <th class="px-5 py-3 font-semibold">Category</th>
                                        <th class="px-5 py-3 font-semibold">Quantity</th>
                                        <th class="px-5 py-3 font-semibold">Exp Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @php
                                        $expiredItemsForModal = $inventoryItems->filter(function ($item) use ($today) {
                                            $expirationDate = $item['expirationDate'] ?? null;

                                            return $expirationDate && \Carbon\Carbon::parse($expirationDate)->lt($today);
                                        })->values();
                                    @endphp
                                    @forelse($expiredItemsForModal as $item)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $item['name'] ?? 'Unnamed Item' }}</td>
                                            <td class="px-5 py-3.5 text-slate-600">{{ $item['category'] ?? 'Uncategorized' }}</td>
                                            <td class="px-5 py-3.5 font-medium tabular-nums text-slate-900">{{ number_format($item['stock'] ?? 0) }}</td>
                                            <td class="px-5 py-3.5 text-red-700">
                                                {{ $item['expirationDate'] ? \Carbon\Carbon::parse($item['expirationDate'])->format('m/d/y') : '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">No expired items found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                        <button type="button" id="expiredCloseBtn"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-red-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-300">Back
                            to Inventory</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="batchModal"
            class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-red-700">Inventory review</div>
                            <h3 class="mt-1 text-2xl font-bold text-slate-900">Batch Details</h3>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @if ($batchGroups->isNotEmpty())
                        <div class="mb-4 flex flex-wrap gap-3" role="tablist" aria-label="Inventory batches">
                            @foreach ($batchGroups as $batchName => $batchItems)
                                <button type="button" role="tab" data-batch-target="batch-panel-{{ $loop->index }}"
                                    class="batch-tab rounded-xl px-4 py-2 text-sm {{ $loop->first ? 'bg-red-800 text-white font-semibold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                                    Batch {{ $batchName }} ({{ $batchItems->count() }})
                                </button>
                            @endforeach
                        </div>

                        @foreach ($batchGroups as $batchName => $batchItems)
                            <div id="batch-panel-{{ $loop->index }}"
                                class="batch-panel {{ $loop->first ? '' : 'hidden' }} overflow-hidden rounded-xl border border-slate-200 bg-white">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm text-left">
                                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th class="px-5 py-4">Item</th>
                                                <th class="px-5 py-4">Category</th>
                                                <th class="px-5 py-4">Quantity</th>
                                                <th class="px-5 py-4">Exp Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($batchItems as $item)
                                                <tr class="border-t border-slate-100">
                                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $item['name'] ?? 'Unnamed Item' }}</td>
                                                    <td class="px-5 py-4 text-slate-600">{{ $item['category'] ?? 'Uncategorized' }}</td>
                                                    <td class="px-5 py-4 text-slate-900">{{ $item['stock'] ?? 0 }}</td>
                                                    <td class="px-5 py-4 text-slate-600">
                                                        {{ !empty($item['expirationDate']) ? \Carbon\Carbon::parse($item['expirationDate'])->format('m/d/y') : '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-8 text-center text-slate-600">
                            No batched inventory items yet. Add an item and choose or create a batch.
                        </div>
                    @endif
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                        <button type="button" id="batchSaveBtn"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-red-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-300">Save</button>
                        <button type="button" id="batchCloseBtn"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Back
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
                        button.querySelector('i')?.classList.toggle('rotate-180', !isExpanded);
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
                            tab.classList.remove('bg-red-800', 'text-white', 'font-semibold');
                            tab.classList.add('bg-slate-100', 'text-slate-700');
                        });
                        batchPanels.forEach((panel) => panel.classList.add('hidden'));

                        button.classList.remove('bg-slate-100', 'text-slate-700');
                        button.classList.add('bg-red-800', 'text-white', 'font-semibold');
                        document.getElementById(button.dataset.batchTarget)?.classList.remove('hidden');
                    });
                });
            </script>
        @endpush
@endsection