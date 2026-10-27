<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AidFlow | Relief Goods Scanner</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/CSWD.png') }}">
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
        <img src="{{ asset('assets/CSWD.png') }}" alt="Logo"
            class="h-14 w-14 shrink-0 rounded-full object-cover sm:h-20 sm:w-20">
        <div>
            <h1 class="text-2xl font-bold leading-none text-white sm:text-4xl">AidFlow</h1>
            <p class="mt-1 max-w-xl text-xs leading-snug text-red-50 sm:text-base">City Social Welfare and Development
                of San Juan City</p>
        </div>
    </div>

    <main class="mx-auto flex w-full max-w-4xl flex-col gap-5 p-3 sm:p-6">
        <div class="rounded-3xl bg-white p-4 shadow-lg sm:p-7">
            <h1 class="text-3xl font-bold leading-tight text-black sm:text-5xl">Relief Goods Scanner</h1>
            <p class="mt-2 mb-5 text-base leading-relaxed text-gray-600 sm:text-lg">Scan relief goods packages to track
                inventory and verify distribution.</p>

            <div class="grid grid-cols-1 gap-6">
                <div class="rounded-3xl border border-gray-200 bg-gray-50 p-4 sm:p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Package Scanner</h2>
                    <div class="relative mx-auto w-full max-w-xl overflow-hidden rounded-2xl bg-black shadow-inner">
                        <div id="scanner" class="w-full bg-white text-gray-600">Scanner placeholder</div>
                    </div>
                    <p id="scanner-status" role="status" aria-live="polite"
                        class="mt-3 text-center text-sm text-gray-600">Align the package QR code inside the frame.</p>
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

    <script>
        const scannerStatus = document.getElementById('scanner-status');
        const scanResultModal = document.getElementById('scan-result-modal');
        const scanResultTitle = document.getElementById('scan-result-title');
        const scanResultMessage = document.getElementById('scan-result-message');
        const continueScanningButton = document.getElementById('continue-scanning');
        const reliefScanUrl = @json(route('phoneFeatures.reliefGoods.scan', absolute: false));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let lastScannedCode = null;
        let lastScannedAt = 0;
        let isSubmitting = false;
        let scanPaused = false;
        let scanner = null;

        function startScanner() {
            if (scanner || typeof Html5QrcodeScanner === 'undefined') return;

            scanner = new Html5QrcodeScanner('scanner', {
                fps: 10,
                qrbox: (width, height) => {
                    const size = Math.floor(Math.min(width, height) * 0.7);
                    return { width: size, height: size };
                },
                rememberLastUsedCamera: true,
                aspectRatio: 4 / 3,
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
            scannerStatus.textContent = 'Tap Start Scanning to scan another package.';
            scannerStatus.classList.remove('font-medium', 'text-blue-600', 'text-green-600', 'text-red-600');
            scannerStatus.classList.add('text-gray-600');
        });

        async function onScanSuccess(decodedText) {
            if (scanPaused || isSubmitting) return;

            const packageId = decodedText.trim();
            const now = Date.now();

            if (!/^#\d+$/.test(packageId)) {
                scanPaused = true;
                try {
                    await closeScanner();
                } catch (error) {
                    console.error(error);
                    showScanResult('Scanner error', 'The camera could not be stopped. Please reload the page before scanning again.');
                    return;
                }
                showScanResult('Scan failed', 'This QR code is not a valid relief package code.');
                return;
            }

            if (packageId === lastScannedCode && now - lastScannedAt < 2000) {
                return;
            }

            lastScannedCode = packageId;
            lastScannedAt = now;
            scanPaused = true;
            isSubmitting = true;
            scannerStatus.textContent = `Processing relief pack ${packageId}...`;
            scannerStatus.classList.remove('text-gray-600', 'text-red-600');
            scannerStatus.classList.add('font-medium', 'text-blue-600');

            try {
                await closeScanner();
                const response = await fetch(reliefScanUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ package_id: packageId }),
                });
                const result = await response.json().catch(() => null);

                scannerStatus.classList.remove('text-blue-600', 'text-red-600');
                if (!response.ok || !result?.success) {
                    const message = result?.message || 'The server could not record this scan. Please try again.';
                    scannerStatus.textContent = message;
                    scannerStatus.classList.add('text-red-600');
                    showScanResult('Scan failed', message);
                } else if (result.already_scanned) {
                    scannerStatus.textContent = result.message;
                    scannerStatus.classList.add('text-green-600');
                    showScanResult('Already distributed', result.message);
                } else {
                    const message = result.message || 'Relief pack processed.';
                    scannerStatus.textContent = message;
                    scannerStatus.classList.add('text-green-600');
                    showScanResult('Relief pack distributed', message);
                }
            } catch (error) {
                console.error(error);
                scannerStatus.textContent = 'A camera, network, or server error prevented this scan.';
                scannerStatus.classList.remove('text-blue-600', 'text-green-600');
                scannerStatus.classList.add('text-red-600');
                showScanResult('Scan failed', 'A camera, network, or server error prevented this scan. Please try again.');
            } finally {
                isSubmitting = false;
            }
        }

        function onScanFailure() {
            // Unreadable frames are normal while the QR code is being aligned.
        }

        if (typeof Html5QrcodeScanner !== 'undefined') {
            startScanner();
        } else {
            scannerStatus.textContent = 'Camera scanner is unavailable in this browser.';
            scannerStatus.classList.add('text-red-600');
        }
    </script>
</body>

</html>