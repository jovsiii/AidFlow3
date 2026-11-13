<div role="group" aria-label="Map legend" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs text-gray-800">
    <p class="mb-1 font-semibold">Map legend</p>
    <ul class="flex flex-wrap items-center gap-x-4 gap-y-2">
        <li class="flex items-center gap-1.5">
            <span aria-hidden="true" class="h-3 w-3 shrink-0 rounded-sm border" style="border-color: #1C4D23; background-color: #289432"></span>
            0% occupied
        </li>
        <li class="flex items-center gap-1.5">
            <span aria-hidden="true" class="h-3 w-3 shrink-0 rounded-sm border" style="border-color: #c2410c; background-color: #948628"></span>
            More than 0% to below 80% occupied
        </li>
        <li class="flex items-center gap-1.5">
            <span aria-hidden="true" class="h-3 w-3 shrink-0 rounded-sm border" style="border-color: #991b1b; background-color: #ef4444"></span>
            80% or more occupied
        </li>
        <li class="flex items-center gap-1.5">
            <img src="{{ asset('assets/CSWD.png') }}" alt="" class="h-4 w-4 shrink-0">
            CMEC evacuation center
        </li>
    </ul>
</div>
