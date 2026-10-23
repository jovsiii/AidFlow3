@extends('features.layout')

@section('title', 'QR Code Generator - AidFlow')
@section('page-title', 'QR Code Generator')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Generate QR Code</h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <form id="qrForm" class="space-y-4">
                <div>
                    <label for="qrType" class="block text-sm font-medium text-gray-700 mb-1">QR Code Type</label>
                    <select id="qrType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="tent">Evacuation / Tent Code</option>
                        <option value="food">Food Pack</option>
                    </select>
                </div>

                <div id="tentField" class="space-y-2">
                    <label for="tentCodeSelect" class="block text-sm font-medium text-gray-700 mb-1">Tent Code</label>
                    <select id="tentCodeSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></select>
                </div>

                <div id="foodField" class="space-y-2 hidden">
                    <label for="foodPackQuantity" class="block text-sm font-medium text-gray-700 mb-1">Number of Relief Pack QR Codes</label>
                    <input id="foodPackQuantity" type="number" min="1" max="{{ $availableReliefPacks }}" step="1" value="{{ $availableReliefPacks > 0 ? 1 : '' }}" @disabled($availableReliefPacks === 0) required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="availableReliefPacks" class="text-sm text-gray-600">
                        {{ $availableReliefPacks > 0 ? "{$availableReliefPacks} relief pack(s) available." : 'No complete relief packs are currently available.' }}
                    </p>
                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">Requirements for each relief pack</h3>
                        <ul class="mt-2 grid grid-cols-1 gap-1 text-sm text-gray-700 sm:grid-cols-2">
                            @foreach ($reliefPackRequirements as $item)
                                <li>{{ $item['name'] }}: {{ $item['quantity'] }} {{ $item['unit'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    <i class="fas fa-qrcode mr-2"></i> Generate QR
                </button>
            </form>

            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 flex flex-col items-center justify-center min-h-[320px]">
                <div id="qrCode" class="bg-white p-4 rounded-lg shadow-sm min-h-[220px] min-w-[220px] flex items-center justify-center"></div>
                <p id="qrPreviewLabel" class="mt-4 text-lg font-semibold text-gray-900">Evacuation / Tent Code</p>
                <p id="qrOutput" class="mt-2 text-sm text-gray-600 break-all text-center" aria-live="polite">No QR generated yet</p>
                <button id="downloadQrPdf" type="button"
                    class="mt-4 hidden rounded-lg bg-green-700 px-4 py-2 font-medium text-white hover:bg-green-800">
                    <i class="fas fa-file-pdf mr-2"></i>Download PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script type="module">
    import { barangayList } from '/js/barangayList.js';

    const qrType = document.getElementById('qrType');
    const tentField = document.getElementById('tentField');
    const foodField = document.getElementById('foodField');
    const tentCodeSelect = document.getElementById('tentCodeSelect');
    const foodPackQuantity = document.getElementById('foodPackQuantity');
    const qrPreviewLabel = document.getElementById('qrPreviewLabel');
    const qrOutput = document.getElementById('qrOutput');
    const qrCode = document.getElementById('qrCode');
    const downloadQrPdf = document.getElementById('downloadQrPdf');
    const availableReliefPacks = @json($availableReliefPacks);
    const PACK_COUNTER_KEY = 'aidflow_relief_pack_counter';
    let generatedQrCodes = [];

    function buildTentOptions() {
        const tentCodes = [];

        barangayList.forEach((barangay) => {
            Object.keys(barangay.tents).forEach((tentCode) => {
                tentCodes.push(tentCode);
            });
        });

        tentCodeSelect.innerHTML = tentCodes.map((tentCode) => `
            <option value="${tentCode}">${tentCode}</option>
        `).join('');

        if (tentCodes.length) {
            tentCodeSelect.value = tentCodes[0];
        }
    }

    function getNextFoodPackNumber() {
        const current = Number(localStorage.getItem(PACK_COUNTER_KEY) || '1');
        const next = Number.isSafeInteger(current) && current > 0 ? current : 1;
        localStorage.setItem(PACK_COUNTER_KEY, String(next + 1));
        return next;
    }

    function updateQrInputState() {
        const isFoodPack = qrType.value === 'food';

        tentField.classList.toggle('hidden', isFoodPack);
        foodField.classList.toggle('hidden', !isFoodPack);
        foodPackQuantity.required = isFoodPack;
    }

    function generateQrCode() {
        const isFoodPack = qrType.value === 'food';
        generatedQrCodes = [];
        downloadQrPdf.classList.add('hidden');

        if (!isFoodPack) {
            const qrText = tentCodeSelect.value;
            if (!qrText) {
                qrCode.replaceChildren();
                qrOutput.textContent = 'No evacuation tent codes are available.';
                return;
            }
            const qr = qrcode(0, 'M');
            qr.addData(qrText);
            qr.make();

            qrCode.innerHTML = qr.createImgTag(10, 10);
            qrCode.className = 'bg-white p-4 rounded-lg shadow-sm min-h-[220px] min-w-[220px] flex items-center justify-center';
            qrPreviewLabel.textContent = 'Evacuation / Tent Code';
            qrOutput.textContent = qrText;
            generatedQrCodes.push({ qr, label: `Evacuation Tent: ${qrText}`, cellSize: 10 });
            downloadQrPdf.classList.remove('hidden');
            return;
        }

        const quantity = Number(foodPackQuantity.value);
        if (!Number.isSafeInteger(quantity) || quantity < 1 || quantity > availableReliefPacks) {
            foodPackQuantity.setCustomValidity(
                availableReliefPacks > 0
                    ? `Enter a whole number between 1 and ${availableReliefPacks}.`
                    : 'There are no complete relief packs available to generate.'
            );
            foodPackQuantity.reportValidity();
            return;
        }
        foodPackQuantity.setCustomValidity('');

        const qrGrid = document.createElement('div');
        qrGrid.className = 'grid grid-cols-1 sm:grid-cols-2 gap-4 w-full';
        const packNumbers = [];

        for (let index = 0; index < quantity; index += 1) {
            const packNumber = getNextFoodPackNumber();
            const qr = qrcode(0, 'M');
            qr.addData(`#${packNumber}`);
            qr.make();

            const card = document.createElement('div');
            card.className = 'bg-white p-4 rounded-lg shadow-sm flex flex-col items-center';
            card.innerHTML = qr.createImgTag(6, 6);
            const label = document.createElement('p');
            label.className = 'mt-2 text-sm font-semibold text-gray-900';
            label.textContent = `Relief Pack: #${packNumber}`;
            card.appendChild(label);
            qrGrid.appendChild(card);
            packNumbers.push(packNumber);
            generatedQrCodes.push({ qr, label: `Relief Pack: #${packNumber}`, cellSize: 6 });
        }

        qrCode.className = 'w-full';
        qrCode.replaceChildren(qrGrid);
        qrPreviewLabel.textContent = `${quantity} Relief Pack QR Code${quantity === 1 ? '' : 's'}`;
        qrOutput.textContent = packNumbers.length === 1
            ? `#${packNumbers[0]}`
            : `#${packNumbers[0]} - #${packNumbers[packNumbers.length - 1]}`;
        downloadQrPdf.classList.remove('hidden');
    }

    async function qrAsPng(qr, cellSize) {
        const image = new Image();
        image.src = qr.createDataURL(cellSize, 4);
        await image.decode();

        const canvas = document.createElement('canvas');
        canvas.width = image.naturalWidth;
        canvas.height = image.naturalHeight;
        canvas.getContext('2d').drawImage(image, 0, 0);

        return canvas.toDataURL('image/png');
    }

    downloadQrPdf.addEventListener('click', async () => {
        downloadQrPdf.disabled = true;
        qrOutput.textContent = 'Preparing PDF...';

        try {
            if (!window.jspdf?.jsPDF || generatedQrCodes.length === 0) {
                throw new Error('PDF download is unavailable or no QR codes have been generated.');
            }

            const pdf = new window.jspdf.jsPDF({ unit: 'mm', format: 'a4' });
            const pageWidth = pdf.internal.pageSize.getWidth();
            const qrSize = 120;

            for (const [index, item] of generatedQrCodes.entries()) {
                if (index > 0) {
                    pdf.addPage();
                }

                pdf.setFontSize(18);
                pdf.text(item.label, pageWidth / 2, 45, { align: 'center' });
                pdf.addImage(await qrAsPng(item.qr, item.cellSize), 'PNG', (pageWidth - qrSize) / 2, 65, qrSize, qrSize);
            }

            const isFoodPack = qrType.value === 'food';
            const firstLabel = generatedQrCodes[0].label.replaceAll(/[^a-zA-Z0-9]+/g, '-').replace(/^-|-$/g, '').toLowerCase();
            pdf.save(`${isFoodPack ? 'relief-packs' : 'evacuation-qr'}-${firstLabel}.pdf`);
            qrOutput.textContent = 'PDF downloaded successfully.';
        } catch (error) {
            console.error(error);
            qrOutput.textContent = `Unable to download PDF: ${error.message}`;
        } finally {
            downloadQrPdf.disabled = false;
        }
    });

    qrType.addEventListener('change', () => {
        updateQrInputState();
        if (qrType.value !== 'food') {
            generateQrCode();
        } else {
            generatedQrCodes = [];
            downloadQrPdf.classList.add('hidden');
            qrCode.replaceChildren();
            qrCode.className = '';
            qrPreviewLabel.textContent = 'Relief Packs';
            qrOutput.textContent = 'Enter the number of relief pack QR codes to generate.';
        }
    });

    tentCodeSelect.addEventListener('change', () => {
        if (qrType.value !== 'food') {
            generateQrCode();
        }
    });

    document.getElementById('qrForm').addEventListener('submit', (event) => {
        event.preventDefault();
        generateQrCode();
    });

    buildTentOptions();
    updateQrInputState();
    generateQrCode();
</script>
@endsection
