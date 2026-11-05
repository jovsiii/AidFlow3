<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>AidFlow | Evacuation Tent Scanner</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <style>
        #scanner {
            border: 0 !important;
            background: #fff;
        }

        #scanner video {
            width: 100% !important;
            height: auto !important;
            object-fit: cover;
        }

        #scanner__scan_region {
            min-height: 15rem;
            background: #030712;
        }

        #scanner__dashboard {
            padding: .75rem;
            background: #fff;
            color: #374151;
        }

        #scanner__dashboard select,
        #scanner__dashboard button {
            max-width: 100%;
            margin: .25rem;
            border: 1px solid #d1d5db;
            border-radius: .75rem;
            padding: .65rem .75rem;
            background: #fff;
            color: #1f2937;
        }

        #scanner__dashboard button {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
            font-weight: 600;
        }

        #scanner img[alt="Info icon"] {
            display: none;
        }

        #scanner a {
            color: #b91c1c !important;
        }
    </style>
</head>

<body class="min-h-screen bg-[#EFF2F3] text-[#1F1F1F]">
    <header class="w-full bg-[#960505] px-4 py-4 text-white shadow-md sm:px-6 sm:py-5">
        <div class="mx-auto flex w-full max-w-3xl items-center gap-3 sm:gap-4">
            <img src="{{ asset('assets/favicon.png') }}" alt="AidFlow logo"
                class="h-12 w-12 shrink-0 rounded-full object-cover sm:h-16 sm:w-16">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold leading-tight sm:text-4xl">AidFlow</h1>
                <p class="mt-1 text-xs leading-snug text-red-50 sm:text-sm">City Social Welfare and Development of San Juan City</p>
            </div>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-3 py-4 sm:px-6 sm:py-7">
        <a href="{{ route('phoneFeatures') }}"
            class="mb-4 inline-flex min-h-11 w-fit items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#960505]">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to menu
        </a>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-7">
            <div class="mb-5">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 sm:text-3xl">Evacuation Tent Scanner</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-600 sm:text-base">Scan a tent to update its occupancy and family profile.</p>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 sm:rounded-3xl sm:p-5">
                    <h3 class="mb-3 text-base font-semibold text-gray-900 sm:text-lg">QR Scanner</h3>
                    <div class="relative mx-auto w-full max-w-xl overflow-hidden rounded-xl bg-black shadow-inner sm:rounded-2xl">
                        <div id="scanner" class="w-full bg-white text-sm text-gray-600">
                            Scanner placeholder
                        </div>
                    </div>
                    <p id="scanner-status" role="status" aria-live="polite" class="mt-3 text-center text-sm text-gray-600">
                        Align the QR code inside the frame for best results.
                    </p>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-5">
            @csrf
            <button type="submit"
                class="min-h-12 w-full rounded-xl bg-red-900 px-4 py-3 text-base font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2">
                <i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i> Logout
            </button>
        </form>
    </main>

    <div id="scan-details-modal" class="fixed inset-0 z-50 hidden items-end justify-center bg-black/60 p-0 sm:items-center sm:p-4"
        role="dialog" aria-modal="true" aria-labelledby="scan-details-title">
        <form id="scan-details-form" class="max-h-[90dvh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:max-h-[none] sm:rounded-3xl sm:p-8">
            <h2 id="scan-details-title" class="text-2xl font-bold text-gray-900">Family Details</h2>
            <p class="mt-2 text-sm text-gray-600">Enter the household details for tent <span id="scan-details-tent"
                    class="font-semibold"></span>.</p>
            <label for="scan-full-name" class="mt-5 block text-sm font-semibold text-gray-700">Full name</label>
            <input id="scan-full-name" name="full_name" type="text" maxlength="255" required autocomplete="name"
                aria-describedby="scan-full-name-error"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-base focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-200">
            <p id="scan-full-name-error" class="mt-1 hidden text-sm text-red-700" role="alert" aria-live="polite">
                Use letters and spaces only.
            </p>
            <label for="scan-household-size" class="mt-4 block text-sm font-semibold text-gray-700">Household size</label>
            <input id="scan-household-size" name="household_size" type="number" min="1" max="100" step="1"
                value="1" required
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-base focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-200">
            <div class="mt-5 grid grid-cols-1 gap-2 sm:mt-6 sm:grid-cols-2 sm:gap-3">
                <button id="cancel-scan-details" type="button"
                    class="min-h-12 rounded-xl border border-gray-300 px-4 py-3 font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                    class="min-h-12 rounded-xl bg-red-600 px-4 py-3 font-semibold text-white hover:bg-red-700">
                    Save and scan
                </button>
            </div>
        </form>
    </div>

    <div id="scan-result-modal" class="fixed inset-0 z-50 hidden items-end justify-center bg-black/60 p-0 sm:items-center sm:p-4"
        role="alertdialog" aria-modal="true" aria-labelledby="scan-result-title"
        aria-describedby="scan-result-message">
        <div class="w-full max-w-md rounded-t-2xl bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] text-center shadow-2xl sm:rounded-3xl sm:p-8">
            <h2 id="scan-result-title" class="text-2xl font-bold text-gray-900"></h2>
            <p id="scan-result-message" class="mt-3 text-base text-gray-700"></p>
            <button id="continue-scanning" type="button"
                class="mt-6 w-full rounded-xl bg-red-600 px-5 py-3 text-lg font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300">
                Continue scanning
            </button>
        </div>
    </div>

    <script type="module">
        import { barangayList, tentCountsReady } from '/js/barangayList.js';
        await tentCountsReady;
        const scanStatus = document.getElementById('scanner-status');
        const scanResultModal = document.getElementById('scan-result-modal');
        const scanResultTitle = document.getElementById('scan-result-title');
        const scanResultMessage = document.getElementById('scan-result-message');
        const continueScanningButton = document.getElementById('continue-scanning');
        const scanDetailsModal = document.getElementById('scan-details-modal');
        const scanFullName = document.getElementById('scan-full-name');
        const scanFullNameError = document.getElementById('scan-full-name-error');
        const scanHouseholdSize = document.getElementById('scan-household-size');
        const scanDetailsForm = document.getElementById('scan-details-form');
        const scanDetailsTent = document.getElementById('scan-details-tent');
        const validFullNamePattern = /^[\p{L} ]+$/u;
        let scanInProgress = false;
        let scanPaused = false;
        let scanner = null;
        let lastScan = { code: null, time: 0 };
        let currentTentCode = null;

        function startScanner() {
            if (scanner || typeof Html5QrcodeScanner === 'undefined') return;

            scanner = new Html5QrcodeScanner('scanner', {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                rememberLastUsedCamera: true,
            }, false);
            scanner.render(onScanSuccess, onScanFailure);
        }

        async function closeScanner() {
            if (!scanner) return;

            await scanner.clear();
            scanner = null;
        }

        function showScanResult(title, message) {
            scanPaused = true;
            scanResultTitle.textContent = title;
            scanResultMessage.textContent = message;
            scanResultModal.classList.remove('hidden');
            scanResultModal.classList.add('flex');
            continueScanningButton.focus();
        }

        continueScanningButton.addEventListener('click', () => {
            scanResultModal.classList.add('hidden');
            scanResultModal.classList.remove('flex');
            startScanner();
            scanPaused = false;
            scanStatus.textContent = 'Tap Start Scanning to scan another tent.';
        });

        function closeScanDetails() {
            scanDetailsModal.classList.add('hidden');
            scanDetailsModal.classList.remove('flex');
        }

        function openScanDetails(tentCode) {
            if (!findBarangay(tentCode)) {
                showScanResult('Scan failed', `Tent ${tentCode} was not found in any barangay.`);
                return;
            }

            currentTentCode = tentCode;
            scanDetailsForm.reset();
            setFullNameError(false);
            scanHouseholdSize.value = '1';
            scanDetailsTent.textContent = tentCode;
            scanDetailsModal.classList.remove('hidden');
            scanDetailsModal.classList.add('flex');
            scanFullName.focus();
        }

        document.getElementById('cancel-scan-details').addEventListener('click', () => {
            closeScanDetails();
            showScanResult('Scan cancelled', 'No occupancy or family profile changes were made.');
        });

        function setFullNameError(hasError) {
            scanFullNameError.classList.toggle('hidden', !hasError);
            scanFullName.setAttribute('aria-invalid', String(hasError));
            scanFullName.classList.toggle('border-red-600', hasError);
            scanFullName.classList.toggle('border-gray-300', !hasError);
        }

        function isValidFullName(value) {
            const name = value.trim();
            return name.length > 0 && validFullNamePattern.test(name);
        }

        scanFullName.addEventListener('input', () => {
            setFullNameError(scanFullName.value.length > 0 && !isValidFullName(scanFullName.value));
        });

        scanDetailsForm.addEventListener('submit', async event => {
            event.preventDefault();
            const validFullName = isValidFullName(scanFullName.value);
            setFullNameError(!validFullName);
            if (!validFullName) {
                scanFullName.focus();
                return;
            }

            const familyDetails = {
                full_name: scanFullName.value.trim(),
                household_size: Number(scanHouseholdSize.value),
            };
            if (!Number.isInteger(familyDetails.household_size)
                || familyDetails.household_size < 1 || familyDetails.household_size > 100) {
                scanDetailsForm.reportValidity();
                return;
            }

            closeScanDetails();
            await submitScan(currentTentCode, familyDetails);
        });

        function findBarangay(tentCode) {
            return barangayList.find(barangay =>
                Object.prototype.hasOwnProperty.call(barangay.tents, tentCode)
            );
        }

        async function submitScan(tentCode, familyDetails, confirmUnoccupy = false) {
            if (scanInProgress) return;
            scanInProgress = true;

            try {
                const barangay = findBarangay(tentCode);
                if (!barangay) {
                    showScanResult('Scan failed', `Tent ${tentCode} was not found in any barangay.`);
                    return;
                }
                const response = await fetch('{{ route("phoneFeatures.evacuation.scan", absolute: false) }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        tent_code: tentCode,
                        barangay_code: barangay.code,
                        barangay_name: barangay.name,
                        full_name: familyDetails.full_name,
                        household_size: familyDetails.household_size,
                        confirm_unoccupy: confirmUnoccupy
                    })
                });

                const text = await response.text();
                let data = null;

                if (text) {
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Not JSON:', text.slice(0, 300));
                        showScanResult('Scan failed', 'The server returned an unexpected response. Please log in again and try again.');
                        return;
                    }
                }

                if (!response.ok) {
                    showScanResult('Scan failed', data?.message || 'The server could not record this scan. Please try again.');
                    return;
                }

                if (data?.requires_confirmation) {
                    if (window.confirm(data.message)) {
                        scanInProgress = false;
                        await submitScan(tentCode, familyDetails, true);
                        return;
                    }
                    showScanResult('Scan cancelled', `Tent ${tentCode} remains occupied. No changes were made.`);
                    return;
                }

                if (data?.success && data.action === 'occupied') {
                    showScanResult('Tent occupied', `Tent ${tentCode} was marked occupied. The family profile for ${familyDetails.full_name} (${familyDetails.household_size} people) was saved.`);
                } else if (data?.success && data.action === 'unoccupied') {
                    showScanResult('Tent marked vacant', `Tent ${tentCode} was successfully marked vacant.`);
                } else {
                    showScanResult('Scan failed', data?.message || 'Unable to record this scan. Please try again.');
                }
            } catch (error) {
                console.error(error);
                showScanResult('Scan failed', 'A network or server error prevented this scan. Please try again.');
            } finally {
                scanInProgress = false;
            }
        }

        async function onScanSuccess(decodedText) {
            if (scanPaused || scanInProgress) return;

            const tentCode = decodedText.trim().toUpperCase();
            if (!tentCode) return;

            const now = Date.now();
            if (lastScan.code === tentCode && now - lastScan.time < 2500) return;
            lastScan = { code: tentCode, time: now };

            scanPaused = true;
            scanStatus.textContent = `Scanned: ${tentCode}`;
            try {
                await closeScanner();
                openScanDetails(tentCode);
            } catch (error) {
                console.error(error);
                showScanResult('Scanner error', 'The camera could not be stopped. Please reload the page before scanning again.');
            }
        }

        function onScanFailure() {
            // Called repeatedly by the scanner while it searches for a QR code.
        }

        if (typeof Html5QrcodeScanner !== 'undefined') {
            startScanner();
        } else {
            scanStatus.textContent = 'Camera scanner is unavailable in this browser.';
        }
    </script>
</body>

</html>