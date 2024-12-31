<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Attendance System') }}</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <!-- <link href="{{ asset('css/app.css') }}" rel="stylesheet"> -->
    <!-- <link href="{{ asset('css/chat.css') }}" rel="stylesheet"> -->
    @stack('styles')
    @livewireStyles
</head>

<body class="d-flex flex-column min-vh-100">
    <div>

    </div>
    @if(auth()->check() && auth()->user()->currentTeam)
    @livewire('navigation-menu')
@endif

    <div class="wrapper flex-grow-1 d-flex flex-column">
        @if(session('success'))
        <script>
            toastr.success("{{ session('success') }}");
        </script>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex">
                <div class="me-2">
                    <i class="fas fa-exclamation-circle fa-lg"></i>
                </div>
                <div>
                    <ul class="mb-0 ps-0" style="list-style: none;">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif


        @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif


        <main class="flex-grow-1 py-4">
            @yield('content')

            @isset($slot)
            {{ $slot }}
            @endisset

        </main>

        @include('layouts.footer')
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>

    <script src="{{ asset('js/app.js' ) }}"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>





    @push('styles')
    <style>
        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item.unread {
            background-color: #f0f7ff;
        }

        .notification-content {
            margin-bottom: 5px;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .dropdown-menu {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
    @endpush
    <main>

    </main>

    @stack('scripts')
    @stack('modals')

    @livewireScripts
</body>

</html>
