@php
    $cmecOccupiedTents = $occupiedTents->map(function ($tent) {
        return [
            'tentId' => $tent['tentCode'] ?? $tent['tent_code'] ?? $tent['tent_id'] ?? '',
            'date' => !empty($tent['scannedAt'])
                ? \Carbon\Carbon::parse($tent['scannedAt'])->format('m/d/Y')
                : (!empty($tent['scanned_at']) ? \Carbon\Carbon::parse($tent['scanned_at'])->format('m/d/Y') : '--'),
        ];
    })->values();
@endphp
<script id="cmec-occupied-data" type="application/json">@json($cmecOccupiedTents)</script>
<div id="cmec-details-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center overflow-y-auto bg-black/50 p-3 sm:p-5"
    role="dialog" aria-modal="true" aria-labelledby="cmec-details-title">
    <div class="relative my-auto max-h-[calc(100dvh-1.5rem)] w-[900px] max-w-[95vw] overflow-y-auto rounded-2xl bg-white p-4 shadow-2xl sm:p-6">
        <button id="close-cmec-details" type="button" aria-label="Close CMEC details" class="absolute top-4 right-5 text-5xl leading-none">&times;</button>
        <h2 id="cmec-details-title" class="mb-5 text-2xl font-semibold text-red-700">Evacuation Center Detail: CMEC</h2>
        <p class="mb-4 text-sm text-gray-600">Approximate location within Corazon de Jesus.</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-5">
            <div class="flex items-center gap-4 rounded-2xl border-2 border-red-700 p-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-red-400"><i class="fas fa-campground text-3xl text-[#1f0000]"></i></div>
                <div><p class="text-gray-600">Total Tents</p><p id="cmec-total-tents" class="text-5xl">0</p></div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl border-2 border-red-700 p-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-red-400"><i class="fas fa-users text-3xl text-[#1f0000]"></i></div>
                <div><p class="text-gray-600">Occupied</p><p id="cmec-occupied-tents" class="text-5xl">0</p></div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl border-2 border-red-700 p-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-red-400"><i class="fas fa-door-open text-3xl text-[#1f0000]"></i></div>
                <div><p class="text-gray-600">Available</p><p id="cmec-available-tents" class="text-5xl">0</p></div>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-1 gap-4 sm:mt-6 sm:grid-cols-3">
            <div class="max-h-[45vh] overflow-auto rounded-xl border border-gray-200 sm:col-span-2">
                <table class="w-full min-w-[28rem] text-left">
                    <thead class="sticky top-0 z-10 bg-gray-100 shadow-sm"><tr><th class="p-2">Date</th><th class="p-2">Tent</th><th class="p-2">Status</th></tr></thead>
                    <tbody id="cmec-tent-table"></tbody>
                </table>
            </div>
            <div class="flex flex-col items-center justify-center rounded-xl border p-4">
                <h3 class="text-xl font-semibold text-red-700">Occupancy Level</h3>
                <div class="relative mt-4 h-52 w-52">
                    <svg class="rotate-[-90deg]" width="210" height="210" aria-hidden="true">
                        <circle cx="105" cy="105" r="90" stroke="#eee" stroke-width="12" fill="none" />
                        <circle id="cmec-occupancy-circle" cx="105" cy="105" r="90" stroke="#dc2626" stroke-width="12" fill="none" stroke-linecap="round" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div id="cmec-occupancy-percent" class="text-4xl font-bold text-red-700">0%</div>
                        <div class="text-gray-500">Occupied</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
