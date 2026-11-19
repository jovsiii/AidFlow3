@extends('features.layout')

@section('title', 'Inventory Ledger - AidFlow')
@section('page-title', 'Inventory Ledger')

@section('content')
    @php
        $search = mb_strtolower(trim(request('search', '')));
        $rows = $transactions;
        $batches = $inbound->pluck('batch')->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
        $batches = $batches->merge($outbound->pluck('batch'))->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
        $selectedBatch = (string) request('batch', '');

        if ($selectedBatch !== '') {
            $rows = $rows->filter(fn ($row) => (string) ($row['batch'] ?? '') === $selectedBatch)->values();
        }

        if ($search !== '') {
            $rows = $rows->filter(function ($row) use ($search) {
                $haystack = [
                    $row['direction'],
                    $row['details'],
                    $row['name'] ?? null,
                    $row['category'] ?? null,
                    $row['batch'] ?? null,
                    $row['purpose'] ?? null,
                    $row['receivedBy'] ?? null,
                    $row['distributedBy'] ?? null,
                    collect($row['items'] ?? [])->keys()->implode(' '),
                ];

                return str_contains(mb_strtolower(implode(' ', array_filter($haystack, fn ($v) => $v !== null))), $search);
            })->values();
        }

        $cards = [
            ['icon' => 'fa-arrow-down', 'label' => 'Inbound Records', 'value' => $inbound->count()],
            ['icon' => 'fa-boxes-stacked', 'label' => 'Total Units Received', 'value' => $inbound->sum('quantity')],
            ['icon' => 'fa-arrow-up', 'label' => 'Relief Packs Distributed', 'value' => $outbound->where('type', 'pack')->count()],
            ['icon' => 'fa-warehouse', 'label' => 'Units Still Available', 'value' => number_format($inbound->sum('quantity') - $outbound->sum(fn ($row) => collect($row['items'])->sum())) . '/' . number_format($inbound->sum('quantity'))],
            ['icon' => 'fa-user-check', 'label' => 'Accounts Involved', 'value' => $inbound->pluck('receivedBy')->merge($outbound->pluck('distributedBy'))->filter()->unique()->count()],
        ];
    @endphp

    <div class="flex flex-row flex-wrap lg:flex-nowrap gap-2">
        @foreach ($cards as $card)
            <div class="bg-white rounded-[20px] border-[3px] border-[#8a1c1c] p-4 flex items-center shadow-sm w-full">
                <div class="shrink-0 mr-4">
                    <div class="w-15 h-15 rounded-[18px] border-[3px] border-[#8a1c1c] bg-[#ff6b6b] flex items-center justify-center">
                        <i class="fas {{ $card['icon'] }} text-2xl text-[#1f0000]"></i>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-700 mb-0.5 whitespace-nowrap">{{ $card['label'] }}</p>
                    <p class="text-[28px] leading-none font-medium">{{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('ledger.index') }}" class="flex flex-wrap gap-3 items-center mb-4 mt-4">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Search item, pack, batch, purpose or account"
            class="flex-1 min-w-[220px] rounded-full pl-4 py-2 border-2 border-pink-200" />
        <select name="batch" class="px-3 py-2 rounded-md border-2 border-pink-200" onchange="this.form.submit()">
            <option value="">All Batches</option>
            @foreach ($batches as $batch)
                <option value="{{ $batch }}" @selected($selectedBatch === (string) $batch)>{{ $batch }}</option>
            @endforeach
        </select>
        <button type="submit"
            class="px-4 py-2 rounded-md bg-pink-200 border-2 border-red-600 text-red-800 font-semibold">Search</button>
        <a href="{{ route('ledger.index') }}"
            class="px-4 py-2 rounded-md bg-gray-200 border-2 border-gray-400 text-gray-700">Reset</a>
    </form>

    <div class="flex flex-col bg-white rounded-lg border-2 border-red-600 p-4 min-h-[calc(82vh-120px)] max-h-[calc(82vh-120px)]">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-red-700 text-xl font-semibold">
                Combined Inventory Ledger
            </h3>
            <span class="rounded-full bg-pink-100 px-3 py-1 text-xs font-semibold text-red-700">
                <i class="fas fa-right-left mr-1"></i>Inbound + Outbound
            </span>
        </div>

        <div class="overflow-auto h-full max-h-full border border-red-200 rounded-md">
            <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-pink-50 text-red-800 font-semibold">
                        <tr>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Type</th>
                            <th class="px-3 py-2 text-left">Details</th>
                            <th class="px-3 py-2 text-left">Batch</th>
                            <th class="px-3 py-2 text-left">Purpose</th>
                            <th class="px-3 py-2 text-left">Quantity</th>
                            <th class="px-3 py-2 text-left">Balance</th>
                            <th class="px-3 py-2 text-left">Account</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        @forelse ($rows as $row)
                            <tr class="border-b align-top hover:bg-pink-50">
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('M d, Y h:i A') : '—' }}</td>
                                <td class="px-3 py-2">
                                    @if ($row['direction'] === 'inbound')
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 font-semibold text-green-700"><i class="fas fa-arrow-down mr-1"></i>Inbound</span>
                                    @else
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 font-semibold text-red-700"><i class="fas fa-arrow-up mr-1"></i>Outbound</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if ($row['direction'] === 'inbound')
                                        <div class="font-semibold text-red-700">{{ $row['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $row['category'] }}</div>
                                    @else
                                        <div class="font-semibold text-red-700">{{ $row['details'] }}</div>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach (($row['items'] ?? []) as $name => $qty)
                                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ $name }} × {{ $qty }}{{ $row['unit'] ? ' ' . $row['unit'] : '' }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ $row['batch'] ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $row['purpose'] ?? '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if ($row['direction'] === 'inbound')
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 font-semibold text-green-700">+{{ number_format($row['change']) }}</span>
                                        {{ $row['unit'] }}
                                    @else
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 font-semibold text-red-700">{{ number_format($row['change']) }}</span> units
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 font-semibold text-gray-700">{{ number_format($row['remaining']) }}</span> units{{ $row['direction'] === 'outbound' ? ' left' : '' }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if ($row['direction'] === 'inbound' && $row['receivedBy'])
                                        <i class="fas fa-user-circle mr-1 text-red-400"></i>{{ $row['receivedBy'] }}
                                    @elseif ($row['direction'] === 'outbound' && $row['distributedBy'])
                                        <i class="fas fa-user-circle mr-1 text-red-400"></i>{{ $row['distributedBy'] }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-10 text-center text-gray-500">No inventory records found.</td></tr>
                        @endforelse
                    </tbody>
            </table>
        </div>
    </div>

    <script>
        (() => {
            const versionUrl = @json(route('ledger.version'));
            const intervalMs = 3000;
            const currentVersion = @json($version);

            const check = async () => {
                if (document.hidden) return;

                try {
                    const response = await fetch(versionUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                    if (!response.ok) return;

                    const { version } = await response.json();
                    if (version !== currentVersion) {
                        window.location.reload();
                    }
                } catch (error) {
                    // Ignore transient network errors; the next poll retries.
                }
            };

            check();
            setInterval(check, intervalMs);
            document.addEventListener('visibilitychange', check);
        })();
    </script>
@endsection
