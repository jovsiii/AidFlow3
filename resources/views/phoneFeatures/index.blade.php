<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AidFlow | QR Code Scanner</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="flex min-h-screen flex-col bg-[#EFF2F3] text-[#1F1F1F]">
    <header class="w-full bg-[#960505] px-4 py-5 text-white shadow-md sm:px-6 sm:py-7">
        <div class="mx-auto flex w-full max-w-5xl items-center gap-3 sm:gap-5">
            <img src="{{ asset('assets/favicon.png') }}" alt="AidFlow logo"
                class="h-14 w-14 shrink-0 rounded-full object-cover sm:h-20 sm:w-20">
            <div class="min-w-0">
                <h1 class="text-3xl font-bold leading-tight sm:text-5xl">AidFlow</h1>
                <p class="mt-1 text-xs leading-snug text-red-50 sm:text-base">
                    City Social Welfare and Development of San Juan City
                </p>
            </div>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col px-4 py-6 sm:px-6 sm:py-10">
        <div class="mb-5 sm:mb-7">
            <h2 class="text-2xl font-bold text-gray-900 sm:text-4xl">Staff Menu</h2>
            <p class="mt-1 text-sm text-gray-600 sm:text-base">Choose a task to continue.</p>
        </div>

        <nav aria-label="Staff features" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
            <a href="{{ route('phoneFeatures.addInventory') }}"
                class="group flex min-h-20 items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-red-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 sm:min-h-28 sm:p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-xl text-[#960505] sm:h-14 sm:w-14">
                    <i class="fas fa-box" aria-hidden="true"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-base font-semibold text-gray-900 sm:text-lg">Add Inventory Items</span>
                    <span class="mt-1 block text-sm text-gray-500">Record new supplies</span>
                </span>
                <i class="fas fa-chevron-right text-sm text-gray-400 transition group-hover:translate-x-1 group-hover:text-[#960505]" aria-hidden="true"></i>
            </a>

            <a href="{{ route('phoneFeatures.evacuation') }}"
                class="group flex min-h-20 items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-red-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 sm:min-h-28 sm:p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-xl text-[#960505] sm:h-14 sm:w-14">
                    <i class="fas fa-campground" aria-hidden="true"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-base font-semibold text-gray-900 sm:text-lg">Evacuation Tent Scanner</span>
                    <span class="mt-1 block text-sm text-gray-500">Scan and update tent occupancy</span>
                </span>
                <i class="fas fa-chevron-right text-sm text-gray-400 transition group-hover:translate-x-1 group-hover:text-[#960505]" aria-hidden="true"></i>
            </a>

            <a href="{{ route('family-profiles.index') }}"
                class="group flex min-h-20 items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-red-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 sm:min-h-28 sm:p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-xl text-[#960505] sm:h-14 sm:w-14">
                    <i class="fas fa-house-user" aria-hidden="true"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-base font-semibold text-gray-900 sm:text-lg">Family Profiles</span>
                    <span class="mt-1 block text-sm text-gray-500">View and manage affected families</span>
                </span>
                <i class="fas fa-chevron-right text-sm text-gray-400 transition group-hover:translate-x-1 group-hover:text-[#960505]" aria-hidden="true"></i>
            </a>

            <a href="{{ route('phoneFeatures.reliefGoods') }}"
                class="group flex min-h-20 items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-red-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 sm:min-h-28 sm:p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-xl text-[#960505] sm:h-14 sm:w-14">
                    <i class="fas fa-qrcode" aria-hidden="true"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-base font-semibold text-gray-900 sm:text-lg">Relief Goods Scanner</span>
                    <span class="mt-1 block text-sm text-gray-500">Scan distributed relief packs</span>
                </span>
                <i class="fas fa-chevron-right text-sm text-gray-400 transition group-hover:translate-x-1 group-hover:text-[#960505]" aria-hidden="true"></i>
            </a>
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-8">
            @csrf
            <button type="submit"
                class="min-h-12 w-full rounded-xl bg-red-900 px-4 py-3 text-base font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2">
                <i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i> Logout
            </button>
        </form>
    </main>
</body>

</html>