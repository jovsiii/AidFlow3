@extends('features.layout')

@section('title', 'Inventory Ledger - AidFlow')
@section('page-title', 'Inventory Ledger')

@section('content')
    @php
        $search = mb_strtolower(trim(request('search', '')));
        $rows = $tab === 'inbound' ? $inbound : $outbound;

        if ($search !== '') {
            $rows = $rows->filter(function ($row) use ($tab, $search) {
                $haystack = $tab === 'inbound'
                    ? [$row['name'], $row['category'], $row['batch'], $row['receivedBy']]
                    : [$row['pack'], $row['distributedBy'], collect($row['items'])->keys()->implode(' ')];

                return str_contains(mb_strtolower(implode(' ', array_filter($haystack, fn ($v) => $v !== null))), $search);
            })->values();
        }

        $cards = [
            ['icon' => 'fa-arrow-down', 'label' => 'Inbound Records', 'value' => $inbound->count()],
            ['icon' => 'fa-boxes-stacked', 'label' => 'Total Units Received', 'value' => $inbound->sum('quantity')],
            ['icon' => 'fa-arrow-up', 'label' => 'Relief Packs Distributed', 'value' => $outbound->count()],
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
                    <p class="text-[28px] leading-none font-medium">{{ number_format($card['value']) }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('ledger.index') }}" class="flex flex-wrap gap-3 items-center mb-4 mt-4">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="{{ $tab === 'inbound' ? 'Search item, batch or account' : 'Search pack, item or account' }}"
            class="flex-1 min-w-[220px] rounded-full pl-4 py-2 border-2 border-pink-200" />
        <button type="submit"
            class="px-4 py-2 rounded-md bg-pink-200 border-2 border-red-600 text-red-800 font-semibold">Search</button>
        <a href="{{ route('ledger.index', ['tab' => $tab]) }}"
            class="px-4 py-2 rounded-md bg-gray-200 border-2 border-gray-400 text-gray-700">Reset</a>
    </form>

    <div class="flex flex-col bg-white rounded-lg border-2 border-red-600 p-4 min-h-[calc(82vh-120px)] max-h-[calc(82vh-120px)]">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-red-700 text-xl font-semibold">
                {{ $tab === 'inbound' ? 'Inbound — Items Received' : 'Outbound — Items Distributed' }}
            </h3>
            <div class="inline-flex overflow-hidden rounded-md border-2 border-red-600" role="tablist" aria-label="Ledger tables">
                <a href="{{ route('ledger.index', ['tab' => 'inbound']) }}" role="tab" aria-selected="{{ $tab === 'inbound' ? 'true' : 'false' }}"
                    @class(['px-5 py-2 font-semibold transition', 'bg-red-600 text-white' => $tab === 'inbound', 'bg-white text-red-700 hover:bg-pink-100' => $tab !== 'inbound'])>
                    <i class="fas fa-arrow-down mr-2"></i>Inbound
                </a>
                <a href="{{ route('ledger.index', ['tab' => 'outbound']) }}" role="tab" aria-selected="{{ $tab === 'outbound' ? 'true' : 'false' }}"
                    @class(['px-5 py-2 font-semibold border-l-2 border-red-600 transition', 'bg-red-600 text-white' => $tab === 'outbound', 'bg-white text-red-700 hover:bg-pink-100' => $tab !== 'outbound'])>
                    <i class="fas fa-arrow-up mr-2"></i>Outbound
                </a>
            </div>
        </div>

        <div class="overflow-auto h-full max-h-full border border-red-200 rounded-md">
            @if ($tab === 'inbound')
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-pink-50 text-red-800 font-semibold">
                        <tr>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Item</th>
                            <th class="px-3 py-2 text-left">Category</th>
                            <th class="px-3 py-2 text-left">Batch</th>
                            <th class="px-3 py-2 text-left">Quantity</th>
                            <th class="px-3 py-2 text-left">Received By</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        @forelse ($rows as $row)
                            <tr class="border-b hover:bg-pink-50">
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('M d, Y') : '—' }}</td>
                                <td class="px-3 py-2 font-semibold text-red-700">{{ $row['name'] }}</td>
                                <td class="px-3 py-2">{{ $row['category'] }}</td>
                                <td class="px-3 py-2">{{ $row['batch'] ?? '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 font-semibold text-green-700">+{{ number_format($row['quantity']) }}</span>
                                    {{ $row['unit'] }}
                                </td>
                                <td class="px-3 py-2">
                                    @if ($row['receivedBy'])
                                        <i class="fas fa-user-circle mr-1 text-red-400"></i>{{ $row['receivedBy'] }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-gray-500">No inbound records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-pink-50 text-red-800 font-semibold">
                        <tr>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Relief Pack</th>
                            <th class="px-3 py-2 text-left">Items Distributed</th>
                            <th class="px-3 py-2 text-left">Distributed By</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        @forelse ($rows as $row)
                            <tr class="border-b align-top hover:bg-pink-50">
                                <td class="px-3 py-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($row['date'])->format('M d, Y h:i A') }}</td>
                                <td class="px-3 py-2 font-semibold text-red-700">#{{ $row['pack'] }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($row['items'] as $name => $qty)
                                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ $name }} × {{ $qty }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if ($row['distributedBy'])
                                        <i class="fas fa-user-circle mr-1 text-red-400"></i>{{ $row['distributedBy'] }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-gray-500">No outbound records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <script>
        (() => {
            const versionUrl = @json(route('ledger.version'));
            const intervalMs = 10000;
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
