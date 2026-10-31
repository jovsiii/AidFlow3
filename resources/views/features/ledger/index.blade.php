@extends('features.layout')

@section('title', 'Inventory Ledger - AidFlow')
@section('page-title', 'Inventory Ledger')

@section('content')
    <div class="bg-white rounded-[18px] border-[3px] border-[#ef4444] p-4">
        <div class="mb-4 flex gap-3" role="tablist" aria-label="Ledger tables">
            <a href="{{ route('ledger.index', ['tab' => 'inbound']) }}" role="tab"
                @class(['px-5 py-2 rounded-lg font-semibold border-2 border-red-600', 'bg-red-600 text-white' => $tab === 'inbound', 'text-red-700 hover:bg-red-50' => $tab !== 'inbound'])>
                <i class="fas fa-arrow-down mr-2"></i> Inbound
            </a>
            <a href="{{ route('ledger.index', ['tab' => 'outbound']) }}" role="tab"
                @class(['px-5 py-2 rounded-lg font-semibold border-2 border-red-600', 'bg-red-600 text-white' => $tab === 'outbound', 'text-red-700 hover:bg-red-50' => $tab !== 'outbound'])>
                <i class="fas fa-arrow-up mr-2"></i> Outbound
            </a>
        </div>

        <div class="overflow-x-auto">
            @if ($tab === 'inbound')
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b-2 border-red-300 text-red-700">
                            <th class="py-2 pr-3">Date</th>
                            <th class="py-2 pr-3">Item</th>
                            <th class="py-2 pr-3">Category</th>
                            <th class="py-2 pr-3">Batch</th>
                            <th class="py-2 pr-3">Quantity</th>
                            <th class="py-2">Received By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0b5b5]">
                        @forelse ($inbound as $row)
                            <tr>
                                <td class="py-2 pr-3 whitespace-nowrap">{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('m/d/y') : '—' }}</td>
                                <td class="py-2 pr-3 font-semibold">{{ $row['name'] }}</td>
                                <td class="py-2 pr-3">{{ $row['category'] }}</td>
                                <td class="py-2 pr-3">{{ $row['batch'] ?? '—' }}</td>
                                <td class="py-2 pr-3 whitespace-nowrap">{{ number_format($row['quantity']) }} {{ $row['unit'] }}</td>
                                <td class="py-2 font-semibold text-green-700">{{ $row['receivedBy'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-gray-500">No inbound records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b-2 border-red-300 text-red-700">
                            <th class="py-2 pr-3">Date</th>
                            <th class="py-2 pr-3">Relief Pack</th>
                            <th class="py-2 pr-3">Items Distributed</th>
                            <th class="py-2">Distributed By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0b5b5]">
                        @forelse ($outbound as $row)
                            <tr>
                                <td class="py-2 pr-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($row['date'])->format('m/d/y h:i A') }}</td>
                                <td class="py-2 pr-3 font-semibold">#{{ $row['pack'] }}</td>
                                <td class="py-2 pr-3">
                                    {{ collect($row['items'])->map(fn ($qty, $name) => "$name × $qty")->implode(', ') }}
                                </td>
                                <td class="py-2 font-semibold text-blue-700">{{ $row['distributedBy'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-gray-500">No outbound records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
