<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AidFlow | QR Code Scanner</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex flex-col gap-4 min-h-screen bg-[#EFF2F3] text-[#1F1F1F] items-center">
    <div class="flex flex-row gap-2 w-full h-50 bg-red-500 items-center">
        <img accesskey="" src="{{ asset('assets/favicon.png') }}" alt="Logo" class="w-30 h-30 rounded-full">
        <div>
            <h1 class="text-5xl font-bold text-white">AidFlow</h1>
            <p class="text-lg text-gray-100">
                City Social Welfare and Development of San Juan City
            </p>
        </div>
    </div>
    <h1 class="text-5xl font-bold text-black">QR Code Scanner</h1>
    <nav class="flex flex-1 flex-col w-full items-center text-center p-4">
        <div class="space-y-2">
            <a href="{{ route('phoneFeatures.addInventory') }}"
                class="bg-red-500 text-white block px-4 py-3 rounded-lg font-bold tracking-widest text-2xl">
                <i class="fas fa-box mr-2"></i> ADD INVENTORY ITEMS
            </a>
            <a href="{{ route('phoneFeatures.evacuation') }}"
                class="bg-red-500 text-white block px-4 py-3 rounded-lg font-bold tracking-widest text-2xl">
                <i class="fas fa-box mr-2"></i> EVACUATION TENT SCANNER
            </a>
            <a href="{{ route('phoneFeatures.reliefGoods') }}"
                class="bg-red-500 text-white block px-4 py-3 rounded-lg font-bold tracking-widest text-2xl">
                <i class="fas fa-box mr-2"></i> RELIEF GOODS SCANNER
            </a>
        </div>
    </nav>
    <form method="POST" action="{{ route('logout') }}" class="w-full p-4 text-center">
        @csrf
        <button type="submit"
            class="w-full px-4 py-3 bg-red-900 rounded-lg hover:bg-red-800 text-white font-bold text-2xl">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </button>
    </form>
</body>

</html>