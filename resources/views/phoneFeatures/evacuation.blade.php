<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>AidFlow | Evacuation Tent Scanner</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
            min-height: 18rem;
            background: #030712;
        }

        #scanner__dashboard {
            padding: 1rem;
            background: #fff;
            color: #374151;
        }

        #scanner__dashboard select,
        #scanner__dashboard button {
            max-width: 100%;
            margin: .35rem;
            border: 1px solid #d1d5db;
            border-radius: .75rem;
            padding: .65rem .85rem;
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
    <div class="flex w-full items-center gap-3 bg-red-500 px-4 py-3 sm:px-6">
        <img src="{{ asset('assets/favicon.png') }}" alt="Logo"
            class="h-14 w-14 shrink-0 rounded-full object-cover sm:h-20 sm:w-20">
        <div>
            <h1 class="text-2xl font-bold leading-none text-white sm:text-4xl">AidFlow</h1>
            <p class="mt-1 max-w-xl text-xs leading-snug text-red-50 sm:text-base">City Social Welfare and Development
                of San Juan City</p>
        </div>
    </div>

    <main class="mx-auto flex w-full max-w-4xl flex-col gap-5 p-3 sm:p-6">
        <div class="rounded-3xl bg-white p-4 shadow-lg sm:p-7">
            <h1 class="text-3xl font-bold leading-tight text-black sm:text-5xl">Evacuation Tent Scanner</h1>
            <p class="mt-2 mb-5 text-base leading-relaxed text-gray-600 sm:text-lg">Scan tents to check occupancy and
                evacuation status quickly.</p>

            <div class="grid grid-cols-1 gap-6">
                <div class="rounded-3xl border border-gray-200 bg-gray-50 p-4 sm:p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">QR Scanner</h2>
                    <div class="relative mx-auto w-full max-w-xl overflow-hidden rounded-2xl bg-black shadow-inner">
                        <div id="scanner" class="w-full bg-white text-gray-600">
                            Scanner placeholder
                        </div>


                    </div>
                    <p id="scanner-status" class="mt-3 text-center text-sm text-gray-600">Align the QR code inside the
                        frame for
                        best results.</p>
                </div>
            </div>
        </div>

        <a href="{{ route('phoneFeatures') }}"
            class="block text-center px-4 py-4 bg-gray-100 rounded-3xl font-semibold text-gray-800 hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i> Back to Home Page
        </a>
        <form method="POST" action="{{ route('logout') }}" class="w-full p-4 text-center">
            @csrf
            <button type="submit"
                class="w-full px-4 py-3 bg-red-900 rounded-lg hover:bg-red-800 text-white font-bold text-2xl">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </button>
        </form>
    </main>

    <div id="scan-result-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4"
        role="alertdialog" aria-modal="true" aria-labelledby="scan-result-title"
        aria-describedby="scan-result-message">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 text-center shadow-2xl sm:p-8">
            <h2 id="scan-result-title" class="text-2xl font-bold text-gray-900"></h2>
            <p id="scan-result-message" class="mt-3 text-base text-gray-700"></p>
            <button id="continue-scanning" type="button"
                class="mt-6 w-full rounded-xl bg-red-600 px-5 py-3 text-lg font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300">
                Continue scanning
            </button>
        </div>
    </div>

    <script type="module">
        import { barangayList } from '/js/barangayList.js';
        const scanStatus = document.getElementById('scanner-status');
        const scanResultModal = document.getElementById('scan-result-modal');
        const scanResultTitle = document.getElementById('scan-result-title');
        const scanResultMessage = document.getElementById('scan-result-message');
        const continueScanningButton = document.getElementById('continue-scanning');
        let scanInProgress = false;
        let scanPaused = false;
        let scanner = null;
        let lastScan = { code: null, time: 0 };

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

        function findBarangay(tentCode) {
            return barangayList.find(barangay =>
                Object.prototype.hasOwnProperty.call(barangay.tents, tentCode)
            );
        }

        async function submitScan(tentCode, confirmUnoccupy = false) {
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
                        await submitScan(tentCode, true);
                        return;
                    }
                    showScanResult('Scan cancelled', `Tent ${tentCode} remains occupied. No changes were made.`);
                    return;
                }

                if (data?.success && data.action === 'occupied') {
                    showScanResult('Tent occupied', `Tent ${tentCode} was successfully marked occupied.`);
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
                await submitScan(tentCode);
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