<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AidFlow - Login</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/CSWD.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center relative overflow-hidden p-4"
        style="background-image: url('{{ asset('assets/login-bg.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        <div class="absolute inset-0 bg-black/55 backdrop-blur-sm"></div>

        <div class="relative z-10 w-full max-w-md">
            <div class="bg-white/10 backdrop-blur-md border-2 border-white/20 rounded-3xl px-12 py-16 shadow-2xl">
                <h1 class="text-4xl font-bold text-red-400 text-center mb-2 tracking-wide">
                    SAN JUAN CITY
                </h1>

                <h4 class="text-xs font-bold text-gray-300 text-center mb-4 tracking-wide">
                    City Social Welfare and Development Department
                </h4>

                <h1 class="text-2xl font-bold text-red-400 text-center mb-6 tracking-wide">
                    Sign In
                </h1>

                {{-- @if($errors->any())
                <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-300 text-sm">
                    @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                    @endforeach
                </div>
                @endif --}}

                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    <div class="relative">
                        <input type="text" name="username" placeholder="Username" value="{{ old('username') }}"
                            class="w-full bg-red-500/20 border-2 border-white/30 rounded-xl px-4 py-4 pr-12 text-white placeholder-white/70 font-medium text-base outline-none transition-all focus:bg-red-500/30 focus:border-white/60" />
                        @error('username')
                            <div class="text-red-300 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="relative">
                        <input type="password" name="password" placeholder="Password"
                            class="w-full bg-red-500/20 border-2 border-white/30 rounded-xl px-4 py-4 pr-12 text-white placeholder-white/70 font-medium text-base outline-none transition-all focus:bg-red-500/30 focus:border-white/60" />
                        @error('password')
                            <div class="text-red-300 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit"
                        class="w-full bg-red-500/80 hover:bg-red-600 text-white font-bold py-3 rounded-xl transition-all duration-200 text-base">
                        Sign In
                    </button>
                </form>

                {{-- <div class="mt-6 text-center">
                    <p class="text-gray-300 text-sm">
                        Don't have an account?
                        <a href="{{ route('register') }}" class="text-red-400 hover:text-red-300 font-semibold">
                            Sign Up
                        </a>
                    </p>
                </div> --}}
            </div>
        </div>
    </div>
</body>

</html>