@extends('features.layout')

@section('title', $title . ' - AidFlow')
@section('page-title', $title)

@section('content')
    <div class="bg-white rounded-[18px] border-[3px] border-[#ef4444] p-4">
        <h3 class="text-red-700 text-xl font-semibold mb-3">{{ $title }} Inventory</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b-2 border-red-300 text-red-700">
                        <th class="py-2 pr-3">Date</th>
                        <th class="py-2 pr-3">Item</th>
                        <th class="py-2 pr-3">Category</th>
                        <th class="py-2 pr-3">Batch</th>
                        <th class="py-2 pr-3">Quantity</th>
                        <th class="py-2">Type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0b5b5]">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="py-2 pr-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($movement['date'])->format('m/d/y') }}</td>
                            <td class="py-2 pr-3 font-semibold">{{ $movement['name'] }}</td>
                            <td class="py-2 pr-3">{{ $movement['category'] }}</td>
                            <td class="py-2 pr-3">{{ $movement['batch'] ?? '—' }}</td>
                            <td class="py-2 pr-3 whitespace-nowrap">{{ number_format($movement['quantity']) }} {{ $movement['unit'] }}</td>
                            <td class="py-2 font-semibold {{ $direction === 'inbound' ? 'text-green-600' : 'text-blue-600' }}">{{ $movement['type'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-500">No {{ strtolower($title) }} records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
