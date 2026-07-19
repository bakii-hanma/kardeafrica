<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials._meta-pixel')

    <title>@yield('title', 'Kardafrica')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .bg-kardafrica-primary { background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%); }
        .text-kardafrica-primary { color: #44A08D; }
        
        /* Floating Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .float-animation { animation: float 3s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#1F2937]">
        <div>
            <a href="/">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" class="w-20 h-20 fill-current text-gray-500 float-animation" />
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            @yield('content')
        </div>
    </div>
</body>
</html>
