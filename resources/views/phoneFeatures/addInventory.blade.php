<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AidFlow | Add Inventory Items</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex flex-col gap-4 min-h-screen bg-[#EFF2F3] text-[#1F1F1F] items-center">
    <div class="flex flex-row gap-2 w-full h-50 bg-red-500 items-center">
        <img accesskey="" src="{{ asset('assets/CSWD.png') }}" alt="Logo" class="w-30 h-30 rounded-full">
        <div>
            <h1 class="text-5xl font-bold text-white">AidFlow</h1>
            <p class="text-lg text-gray-100">
                City Social Welfare and Development of San Juan City
            </p>
        </div>
    </div>
    <main
        class=" bg-white/80 backdrop-blur-xl rounded-4xl border border-gray-200 shadow-xl shadow-gray-200/40 p-4 w-full max-w-2xl">
        <div class="text-center mb-6">
            <h2 class="text-4xl font-extrabold tracking-tight text-[#1F1F1F]">Add Inventory Item</h2>
            <p class="text-sm text-gray-500 mt-2">Add new stock quickly with all item details.</p>
        </div>

        <form method="POST" action="{{ route('phoneFeatures.addInventory.store') }}" class="space-y-4">
            @csrf
            @if (session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div>
                <label for="item_name" class="ml-4 font-bold">Item Name</label>
                <select id="item_name" name="item_name" required
                    class="w-full rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4 text-lg text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                    <option value="" disabled {{ old('item_name') ? '' : 'selected' }}>Item Name</option>
                    @foreach ($itemDefaults as $item => $defaults)
                        <option value="{{ $item }}" {{ old('item_name') === $item ? 'selected' : '' }}>{{ $item }}</option>
                    @endforeach
                    <option value="Other" {{ old('item_name') === 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                <input id="other_item_name" name="other_item_name" type="text" value="{{ old('other_item_name') }}"
                    placeholder="Enter item name" aria-label="Other item name"
                    class="mt-3 hidden w-full rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4 text-lg text-[#1F1F1F] placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
            </div>

            <div>
                <label for="quantity" class="ml-4 font-bold">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="0" placeholder="Quantity"
                    value="{{ old('quantity') }}" required
                    class="w-full rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4 text-lg text-[#1F1F1F] placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
            </div>

            <div>
                <label for="category" class="ml-4 font-bold">Category</label>
                <select id="category" name="category" required
                    class="w-full rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4 text-lg text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                    <option value="" disabled {{ old('category') ? '' : 'selected' }}>Category</option>
                    @foreach (['Food', 'Medicine', 'Drink', 'Equipment', 'Hygiene', 'Other'] as $category)
                        <option value="{{ $category }}" {{ old('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="unit" class="ml-4 font-bold">Unit of Measurement</label>
                <select id="unit" name="unit" required
                    class="w-full rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4 text-lg text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                    <option value="" disabled {{ old('unit') ? '' : 'selected' }}>Unit of Measurement</option>
                    @foreach (['Piece', 'Can', 'Box', 'Pack', 'Bottle', 'Gram', 'Set', 'Sachet', 'Litre', 'Kg'] as $unitOption)
                        <option value="{{ $unitOption }}" {{ old('unit') === $unitOption ? 'selected' : '' }}>{{ $unitOption }}</option>
                    @endforeach
                </select>
            </div>

            <fieldset class="space-y-3">
                <legend class="ml-4 font-bold">Batch</legend>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex cursor-pointer items-center gap-2 rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4">
                        <input type="radio" name="batch_option" value="existing"
                            {{ old('batch_option', $batches->isNotEmpty() ? 'existing' : 'new') === 'existing' ? 'checked' : '' }}>
                        Existing batch
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4">
                        <input type="radio" name="batch_option" value="new"
                            {{ old('batch_option', $batches->isNotEmpty() ? 'existing' : 'new') === 'new' ? 'checked' : '' }}>
                        New batch
                    </label>
                </div>
                <div id="existingBatchFields">
                    <select id="batch" name="batch" aria-label="Choose batch"
                        class="w-full rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4 text-lg focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                        <option value="">Choose a batch</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch }}" {{ old('batch') === $batch ? 'selected' : '' }}>{{ $batch }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="newBatchFields" class="hidden">
                    <input id="new_batch" name="new_batch" type="text" value="{{ old('new_batch') }}"
                        aria-label="New batch name" placeholder="New batch name or number"
                        class="w-full rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4 text-lg focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                </div>
            </fieldset>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="date_received" class="ml-4 font-bold">Date Received</label>
                    <input id="date_received" name="date_received" type="date"
                        class="w-full rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4 text-lg text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                </div>
                <div id="expirationDateField" class="hidden">
                    <label for="expiration_date" class="ml-4 font-bold">Expiration Date</label>
                    <input id="expiration_date" name="expiration_date" type="date"
                        value="{{ old('expiration_date', old('expirationDate')) }}"
                        class="w-full rounded-[28px] border border-gray-300 bg-gray-100 px-5 py-4 text-lg text-[#1F1F1F] focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                </div>
            </div>

            <button type="submit"
                class="w-full rounded-full bg-white border border-black px-6 py-4 text-xl font-semibold text-black shadow-sm hover:bg-gray-100 transition">
                Submit
            </button>
        </form>

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
    </div>
    <script>
        const batchOptions = document.querySelectorAll('input[name="batch_option"]');
        const existingBatchFields = document.getElementById('existingBatchFields');
        const newBatchFields = document.getElementById('newBatchFields');
        const batchSelect = document.getElementById('batch');
        const newBatchInput = document.getElementById('new_batch');
        const itemSelect = document.getElementById('item_name');
        const otherItemInput = document.getElementById('other_item_name');
        const categorySelect = document.getElementById('category');
        const expirationDateField = document.getElementById('expirationDateField');
        const expirationDateInput = document.getElementById('expiration_date');
        const categoriesWithExpiration = @json($expirationCategories);
        const itemDefaults = @json($itemDefaults);

        function updateItemFields() {
            const isOtherItem = itemSelect.value === 'Other';
            otherItemInput.classList.toggle('hidden', !isOtherItem);
            otherItemInput.required = isOtherItem;
        }

        function applyItemDefaults(overwrite = true) {
            const defaults = itemDefaults[itemSelect.value];
            if (!defaults) {
                return;
            }

            const quantityInput = document.getElementById('quantity');
            const unitSelect = document.getElementById('unit');
            if (overwrite || !quantityInput.value) {
                quantityInput.value = defaults.quantity;
            }
            if (overwrite || !categorySelect.value) {
                categorySelect.value = defaults.category;
            }
            if (overwrite || !unitSelect.value) {
                unitSelect.value = defaults.unit;
            }
        }

        function updateBatchFields() {
            const useNewBatch = document.querySelector('input[name="batch_option"]:checked')?.value === 'new';
            existingBatchFields.classList.toggle('hidden', useNewBatch);
            newBatchFields.classList.toggle('hidden', !useNewBatch);
            batchSelect.required = !useNewBatch;
            newBatchInput.required = useNewBatch;
        }

        function updateExpirationDateField() {
            const isRice = itemSelect.value === 'Rice';
            const requiresExpirationDate = !isRice && categoriesWithExpiration.includes(categorySelect.value);
            expirationDateField.classList.toggle('hidden', !requiresExpirationDate);
            expirationDateInput.required = requiresExpirationDate;
            if (isRice) {
                expirationDateInput.value = '';
            }
        }

        batchOptions.forEach(option => option.addEventListener('change', updateBatchFields));
        itemSelect.addEventListener('change', () => {
            updateItemFields();
            applyItemDefaults();
            updateExpirationDateField();
        });
        categorySelect.addEventListener('change', updateExpirationDateField);
        updateItemFields();
        applyItemDefaults(false);
        updateBatchFields();
        updateExpirationDateField();
    </script>
</body>

</html>
