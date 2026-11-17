@extends('features.layout')

@section('title', 'Family Profile Summary - AidFlow')
@section('page-title', 'Family Profile Summary')

@section('content')
    <style>
        .family-summary-table { width: max-content; min-width: 100%; }
        .family-summary-table th, .family-summary-table td {
            min-width: 10rem; max-width: 18rem; padding: .75rem 1rem; vertical-align: top;
            border-right: 1px solid #f3f4f6; white-space: pre-wrap; overflow-wrap: anywhere;
        }
        .family-summary-table th:first-child { position: sticky; left: 0; z-index: 1; background: white; text-align: left; }
        .family-summary-table thead th:first-child { z-index: 2; background: #fef2f2; }
    </style>
    <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('family-profiles.index') }}" class="font-semibold text-[#960505] hover:underline">&larr; Back to Family Profiles</a>
            <button type="button" id="family-summary-export" disabled
                class="rounded-lg bg-[#960505] px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-50">
                Export to PDF
            </button>
        </div>
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" aria-labelledby="summary-title">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-gray-200 px-5 py-5">
                <div>
                    <h2 id="summary-title" class="text-xl font-bold text-gray-900">Family Profile Summary</h2>
                    <p class="mt-1 text-sm text-gray-500">All family profiles. Scroll horizontally to see every field and family member.</p>
                </div>
                <div class="text-sm font-medium text-gray-600">
                    <span id="family-summary-count">Families: &mdash;</span> &middot;
                    <span id="family-summary-asof">As of: waiting for data</span>
                </div>
            </div>
            <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Family profile summary table">
                <table class="family-summary-table border-collapse text-left text-sm">
                    <thead id="family-summary-head" class="bg-red-50 text-xs uppercase text-red-900"></thead>
                    <tbody id="family-summary-body" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
            <p id="family-summary-status" class="px-5 py-4 text-sm text-gray-600" role="status" aria-live="polite">Loading family profiles...</p>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
    <script type="module">
        import { setupFamilySummary } from '/js/familyProfileSummary.js';
        setupFamilySummary({
            button: document.getElementById('family-summary-export'),
            status: document.getElementById('family-summary-status'),
            head: document.getElementById('family-summary-head'),
            body: document.getElementById('family-summary-body'),
            count: document.getElementById('family-summary-count'),
            asOf: document.getElementById('family-summary-asof'),
        }, @json(route('family-profiles.summary.data')));
    </script>
@endpush
