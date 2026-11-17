@extends('features.layout')

@section('title', 'Relief Pack Plan - AidFlow')
@section('page-title', 'Relief Pack Plan')

@section('content')
    <div class="mx-auto max-w-5xl space-y-5">
        <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-2 font-semibold text-red-700 hover:text-red-900">
            &larr; Back to Inventory
        </a>

        @if (session('success'))
            <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Relief pack plan</h2>
                    <p class="mt-1 text-sm text-gray-600">Choose the items and quantities for one planned pack.</p>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50 px-5 py-3">
                    <p class="text-sm text-gray-600">Packs possible with current stock</p>
                    <p class="text-2xl font-bold text-red-700">{{ number_format($availablePacks) }}</p>
                </div>
            </div>
            <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Planning only: saving this plan does not reserve stock or change QR codes, scanned pack contents, or the standard pack count.
                The estimate excludes expired stock and does not include preassembled standard packs.
            </p>

            <form method="POST" action="{{ route('relief-pack-plan.store') }}" class="mt-5">
                @csrf
                <div id="pack-plan-items" class="space-y-3">
                    @foreach ($formItems as $index => $item)
                        <div class="pack-plan-row flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <label class="flex min-w-[220px] flex-1 flex-col gap-1 text-sm font-medium text-gray-700">
                                Item and unit
                                <select name="items[{{ $index }}][item]" required class="rounded-md border border-gray-300 bg-white px-3 py-2">
                                    <option value="">Select an item</option>
                                    @foreach ($options as $key => $option)
                                        <option value="{{ $key }}" @selected(($item['item'] ?? null) === $key)>{{ $option['name'] }} ({{ $option['unit'] }})</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="flex w-36 flex-col gap-1 text-sm font-medium text-gray-700">
                                Quantity per pack
                                <input type="number" name="items[{{ $index }}][quantity]" min="1" max="1000000" step="1" required
                                    value="{{ is_scalar($item['quantity'] ?? null) ? $item['quantity'] : '' }}"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-2">
                            </label>
                            <button type="button" data-remove-pack-item class="rounded-md border border-red-200 px-3 py-2 font-semibold text-red-700 hover:bg-red-50">Remove</button>
                        </div>
                    @endforeach
                </div>
                <template id="pack-plan-item-template">
                    <div class="pack-plan-row flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <label class="flex min-w-[220px] flex-1 flex-col gap-1 text-sm font-medium text-gray-700">
                            Item and unit
                            <select name="items[__INDEX__][item]" required class="rounded-md border border-gray-300 bg-white px-3 py-2">
                                <option value="">Select an item</option>
                                @foreach ($options as $key => $option)
                                    <option value="{{ $key }}">{{ $option['name'] }} ({{ $option['unit'] }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="flex w-36 flex-col gap-1 text-sm font-medium text-gray-700">
                            Quantity per pack
                            <input type="number" name="items[__INDEX__][quantity]" min="1" max="1000000" step="1" required
                                class="rounded-md border border-gray-300 bg-white px-3 py-2">
                        </label>
                        <button type="button" data-remove-pack-item class="rounded-md border border-red-200 px-3 py-2 font-semibold text-red-700 hover:bg-red-50">Remove</button>
                    </div>
                </template>
                <p id="pack-plan-message" role="status" class="mt-2 text-sm text-red-700"></p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" id="add-pack-plan-item" class="rounded-md border border-red-600 px-4 py-2 font-semibold text-red-700 hover:bg-red-50">Add item</button>
                    <button type="submit" class="rounded-md bg-red-700 px-4 py-2 font-semibold text-white hover:bg-red-800">Save plan</button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const rows = document.getElementById('pack-plan-items');
            const template = document.getElementById('pack-plan-item-template');
            const message = document.getElementById('pack-plan-message');
            let nextIndex = rows.children.length;

            document.getElementById('add-pack-plan-item').addEventListener('click', () => {
                if (rows.children.length >= 100) {
                    message.textContent = 'A plan can contain at most 100 items.';
                    return;
                }
                const row = template.content.firstElementChild.cloneNode(true);
                row.querySelectorAll('[name]').forEach((input) => {
                    input.name = input.name.replace('__INDEX__', nextIndex);
                });
                nextIndex++;
                rows.appendChild(row);
                message.textContent = '';
                row.querySelector('select').focus();
            });
            rows.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-pack-item]');
                if (!button) return;
                button.closest('.pack-plan-row').remove();
                message.textContent = '';
            });
        })();
    </script>
@endpush
