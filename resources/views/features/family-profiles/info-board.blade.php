@extends('features.layout')

@section('title', 'Family Profile Info Board - AidFlow')
@section('page-title', 'Family Profile Info Board')

@section('content')
    <style>
        .info-board { max-width: 1100px; margin: 2rem auto; }
        .info-board a, .info-board button { cursor: pointer; }
        .info-board-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; }
        .info-board-back { color: #960505; font-weight: 600; text-decoration: none; }
        .info-board-card { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 1rem; background: white; box-shadow: 0 4px 20px rgba(15, 23, 42, .06); }
        .info-board-heading { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; padding: 1.5rem; border-bottom: 1px solid #e5e7eb; }
        .info-board-heading h2 { margin: 0; color: #111827; font-size: 1.35rem; font-weight: 700; }
        .info-board-heading p { margin: .4rem 0 0; color: #6b7280; font-size: .875rem; }
        .info-board-asof { color: #374151; font-size: .875rem; font-weight: 600; }
        .info-board-totals { display: flex; flex-wrap: wrap; gap: 1rem; padding: 1.25rem 1.5rem; background: #fdf7f7; }
        .info-board-total { flex: 1 1 180px; padding: 1rem; border: 1px solid #f3d9d9; border-radius: .75rem; background: white; }
        .info-board-total span { display: block; color: #6b7280; font-size: .8rem; }
        .info-board-total strong { display: block; margin-top: .25rem; color: #960505; font-size: 1.6rem; }
        .info-board-scroll { overflow-x: auto; }
        .info-board table { width: 100%; min-width: 620px; border-collapse: collapse; text-align: left; }
        .info-board th { padding: .85rem 1.5rem; background: #f9fafb; color: #6b7280; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; }
        .info-board td { padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6; color: #374151; font-size: .9rem; }
        .info-board td:first-child { color: #111827; font-weight: 600; }
        .info-board .info-board-number { text-align: right; font-variant-numeric: tabular-nums; }
        .info-board-status { padding: 1rem 1.5rem; color: #6b7280; font-size: .875rem; }
        .info-board-status.error { color: #b91c1c; background: #fef2f2; }
        .info-board-note { padding: 0 1.5rem 1.25rem; color: #6b7280; font-size: .8rem; }
        .info-board-actions { display: flex; flex-wrap: wrap; gap: .75rem; }
        .info-board-menu { position: relative; }
        .info-board-menu summary { list-style: none; cursor: pointer; border: 1px solid #960505; border-radius: .5rem; background: #960505; padding: .5rem .85rem; color: white; font-weight: 600; }
        .info-board-menu summary::-webkit-details-marker { display: none; }
        .info-board-menu summary:hover { background: #7f1d1d; }
        .info-board-menu-options { position: absolute; right: 0; z-index: 20; margin-top: .35rem; min-width: 190px; overflow: hidden; border: 1px solid #e5e7eb; border-radius: .5rem; background: white; box-shadow: 0 8px 24px rgba(15, 23, 42, .14); }
        .info-board-csv { display: block; padding: .65rem .85rem; color: #374151; font-weight: 600; text-decoration: none; }
        .info-board-csv:hover { background: #fef2f2; color: #960505; }
        .info-board-delete { width: 100%; border: 0; border-top: 1px solid #fee2e2; background: white; padding: .65rem .85rem; text-align: left; color: #b91c1c; font-weight: 600; }
        .info-board-delete:hover { background: #fef2f2; }
    </style>

    <div class="info-board">
        <div class="info-board-header">
            <a class="info-board-back" href="{{ route('family-profiles.index') }}">&larr; Back to Family Profiles</a>
            <div class="info-board-actions">
                <details class="info-board-menu">
                    <summary><i class="fas fa-right-left mr-2" aria-hidden="true"></i>Export and Delete</summary>
                    <div class="info-board-menu-options">
                        <a class="info-board-csv" href="{{ route('family-profiles.summary.csv') }}">
                            <i class="fas fa-download mr-2" aria-hidden="true"></i>Export CSV
                        </a>
                        @if(mb_strtolower((string) session('user.role', '')) === 'admin')
                            <form method="POST" action="{{ route('family-profiles.destroy-all') }}"
                                onsubmit="return confirm('Delete all displayed family profiles? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="info-board-delete">
                                    <i class="fas fa-trash mr-2" aria-hidden="true"></i>Delete All
                                </button>
                            </form>
                        @endif
                    </div>
                </details>
            </div>
        </div>
        <section class="info-board-card" aria-labelledby="board-title">
            <div class="info-board-heading">
                <div>
                    <h2 id="board-title">Family Profile Info Board</h2>
                    <p>Family profiles grouped by barangay and evacuation center.</p>
                </div>
                <div class="info-board-asof" id="board-as-of">As of: waiting for data</div>
            </div>
            <div class="info-board-totals">
                <div class="info-board-total"><span>Number of families</span><strong id="board-families">&mdash;</strong></div>
                <div class="info-board-total"><span>Number of individuals</span><strong id="board-individuals">&mdash;</strong></div>
            </div>
            <div class="info-board-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Barangay (address)</th>
                            <th scope="col">Barangay evacuation center</th>
                            <th scope="col" class="info-board-number">Families</th>
                            <th scope="col" class="info-board-number">Individuals</th>
                        </tr>
                    </thead>
                    <tbody id="board-rows"></tbody>
                </table>
            </div>
            <p id="board-status" class="info-board-status" role="status" aria-live="polite">Loading family profiles...</p>
            <p id="board-note" class="info-board-note">Individual counts are based on recorded household sizes. Checking for live updates; automatic refresh is available as a fallback.</p>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const url = @json(route('family-profiles.info-board.data'));
            const rows = document.getElementById('board-rows');
            const status = document.getElementById('board-status');
            const asOf = document.getElementById('board-as-of');
            const families = document.getElementById('board-families');
            const individuals = document.getElementById('board-individuals');
            const note = document.getElementById('board-note');
            const formatter = new Intl.NumberFormat();
            let currentRefresh = null;
            let pending = false;
            let live = false;
            let missingSizes = 0;

            function updateNote() {
                note.textContent = (missingSizes
                    ? '* Individual counts exclude ' + formatter.format(missingSizes) + ' profile(s) without a valid household size. '
                    : 'Individual counts are based on recorded household sizes. ')
                    + (live ? 'Live updates connected.' : 'Live updates unavailable; automatically refreshing every 15 seconds.');
            }

            function refresh() {
                if (currentRefresh) {
                    pending = true;
                    return currentRefresh;
                }
                currentRefresh = refreshUntilCurrent().finally(() => { currentRefresh = null; });
                return currentRefresh;
            }

            async function refreshUntilCurrent() {
                let success = false;
                do {
                    pending = false;
                    success = await fetchBoard();
                } while (pending);
                return success;
            }

            async function fetchBoard() {
                try {
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                    if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                        throw new Error('Unable to load the info board.');
                    }
                    const data = await response.json();
                    const fragment = document.createDocumentFragment();
                    let totalFamilies = 0;
                    let totalIndividuals = 0;
                    let missing = 0;
                    data.rows.forEach((row) => {
                        const tr = document.createElement('tr');
                        [row.barangay, row.evacuationCenter, formatter.format(row.families), formatter.format(row.individuals)].forEach((value, index) => {
                            const cell = document.createElement('td');
                            cell.textContent = value;
                            if (index >= 2) cell.className = 'info-board-number';
                            if (index === 3 && row.missingHouseholdSizes) cell.textContent += ' *';
                            tr.appendChild(cell);
                        });
                        fragment.appendChild(tr);
                        totalFamilies += row.families;
                        totalIndividuals += row.individuals;
                        missing += row.missingHouseholdSizes;
                    });
                    rows.replaceChildren(fragment);
                    families.textContent = formatter.format(totalFamilies);
                    individuals.textContent = formatter.format(totalIndividuals) + (missing ? ' *' : '');
                    asOf.textContent = 'As of: ' + new Intl.DateTimeFormat('en-PH', {
                        dateStyle: 'medium', timeStyle: 'medium', timeZone: 'Asia/Manila'
                    }).format(new Date(data.asOf));
                    status.classList.remove('error');
                    status.textContent = data.rows.length ? '' : 'No family profiles have been added yet.';
                    status.hidden = data.rows.length > 0;
                    missingSizes = missing;
                    updateNote();
                    return true;
                } catch (error) {
                    status.hidden = false;
                    status.classList.add('error');
                    status.textContent = 'Unable to refresh the info board. Displayed data may be out of date; retrying automatically.';
                    console.error('Info board refresh failed:', error);
                    return false;
                }
            }
            document.addEventListener('family-board-change', refresh);
            document.addEventListener('family-board-connection', (event) => {
                live = event.detail;
                updateNote();
            });
            document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
            refresh();
            setInterval(() => { if (!document.hidden) refresh(); }, 15000);
        })();
    </script>
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.9.0/firebase-app.js';
        import { getDatabase, onValue, ref } from 'https://www.gstatic.com/firebasejs/10.9.0/firebase-database.js';

        try {
            const databaseURL = @json(config('firebase.family_profiles_database_url'));
            if (!databaseURL) throw new Error('Firebase database URL is not configured.');
            const app = initializeApp({ databaseURL }, 'family-info-board');
            const database = getDatabase(app);
            let connected = false;
            let watching = false;
            const updateConnection = () => document.dispatchEvent(new CustomEvent('family-board-connection', {
                detail: connected && watching
            }));
            onValue(ref(database, '.info/connected'), (snapshot) => {
                connected = snapshot.val() === true;
                updateConnection();
            }, (error) => {
                console.error('Unable to monitor Firebase connection:', error);
                connected = false;
                updateConnection();
            });
            onValue(ref(database, 'familyProfiles'), () => {
                watching = true;
                updateConnection();
                document.dispatchEvent(new Event('family-board-change'));
            }, (error) => {
                console.error('Unable to watch family profiles:', error);
                watching = false;
                updateConnection();
            });
        } catch (error) {
            console.error('Unable to initialize live family profile updates:', error);
            document.dispatchEvent(new CustomEvent('family-board-connection', { detail: false }));
        }
    </script>
@endpush
