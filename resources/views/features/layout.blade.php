<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AidFlow')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @vite(['resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>

<body class="bg-gray-50 flex flex-col min-h-screen">
    <header class="flex items-center gap-4 px-4 py-3 bg-gray-200 border-b shadow-sm">
        <button id="menu-toggle" aria-expanded="true"
            class="flex cursor-pointer h-12 w-12 items-center justify-center rounded-2xl border border-gray-200 bg-white text-2xl text-gray-700 shadow-sm transition hover:bg-gray-50">
            <i class="fas fa-bars"></i>
        </button>

        <div class="text-lg font-semibold text-slate-900">
            @yield('page-title', 'Dashboard')
        </div>

        <div class="flex-1"></div>

        <div class="flex items-center gap-2">
            <div class="text-right">
                <div class="font-semibold">
                    {{ session('user.fullName') ?? session('user')['fullName'] ?? 'Guest' }}
                </div>
                <div class="text-sm text-gray-600">
                    {{ ucfirst(session('user.role') ?? session('user')['role'] ?? 'guest') }}
                </div>
            </div>
        </div>
    </header>

    <div class="relative min-h-full flex-1">
        <!-- Sidebar -->


        <div id="sidebar"
            class="hidden absolute bg-[#960505] rounded-br rounded-tr shadow-2xl p-5 max-w-76 h-full z-50 flex flex-col">

            <div class="flex flex-row gap-2 items-center mb-4">
                <img accesskey="" src="{{ asset('assets/favicon.png') }}" alt="Logo" class="w-12 h-12 rounded-full">
                <div>
                    <h1 class="text-3xl font-bold text-white">AidFlow</h1>
                    <p class="text-sm text-gray-100">
                        City Social Welfare and Development of San Juan City
                    </p>
                </div>
            </div>

            <nav class="flex flex-1 flex-col justify-between">
                <div class="space-y-2">
                    @if((session('user')['role'] ?? '') === 'admin')
                        <a href="{{ route('dashboard') }}" @class(['block px-4 py-3 rounded-lg font-medium', 'bg-white text-[#960505] shadow' => request()->routeIs('dashboard'), 'text-white hover:bg-white hover:text-[#960505] transition' => !request()->routeIs('dashboard')])>
                            <i class="fas fa-chart-line mr-2"></i> Dashboard
                        </a>
                        <a href="{{ route('evacuation.index') }}" @class(['block px-4 py-3 rounded-lg font-medium', 'bg-white text-[#960505] shadow' => request()->routeIs('evacuation.*'), 'text-white hover:bg-white hover:text-[#960505] transition' => !request()->routeIs('evacuation.*')])>
                            <i class="fas fa-exclamation-triangle mr-2"></i> Evacuation
                        </a>
                    @endif

                    <a href="{{ route('inventory.index') }}" @class(['block px-4 py-3 rounded-lg font-medium', 'bg-white text-[#960505] shadow' => request()->routeIs('inventory.*'), 'text-white hover:bg-white hover:text-[#960505] transition' => !request()->routeIs('inventory.*')])>
                        <i class="fas fa-box mr-2"></i> Inventory
                    </a>

                    <a href="{{ route('qrcode.index') }}" @class([
                        'block px-4 py-3 rounded-lg font-medium',
                        'bg-white text-[#960505] shadow' => request()->routeIs('qrcode.*'),
                        'text-white hover:bg-white hover:text-[#960505] transition' => !request()->routeIs('qrcode.*')
                    ])>
                        <i class="fas fa-qrcode mr-2"></i> QR Code
                    </a>

                    @if((session('user')['role'] ?? '') === 'admin')
                        <a href="{{ route('users.index') }}" @class(['block px-4 py-3 rounded-lg font-medium', 'bg-white text-[#960505] shadow' => request()->routeIs('users.*'), 'text-white hover:bg-white hover:text-[#960505] transition' => !request()->routeIs('users.*')])>
                            <i class="fas fa-users mr-2"></i> Users
                        </a>
                    @endif
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full px-4 py-3 bg-red-900 rounded-lg hover:bg-red-800 text-white font-medium text-left">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </form>
            </nav>
        </div>

        <!-- Main Content -->
        <main class="flex-1 p-4">
            @yield('content')
        </main>
    </div>

    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const toggleIcon = menuToggle.querySelector('i');

        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('hidden');
            const isOpen = !sidebar.classList.contains('hidden');
            toggleIcon.classList.toggle('fa-bars', !isOpen);
            toggleIcon.classList.toggle('fa-times', isOpen);
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        });

        // // Close sidebar on medium screens and up
        // if (window.innerWidth >= 768) {
        //     sidebar.classList.remove('hidden');
        // }
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    @stack('scripts')
</body>

</html>