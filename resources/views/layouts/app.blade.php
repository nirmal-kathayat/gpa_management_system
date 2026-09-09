{{--
    The application shell. Everything visible lives in layouts/partials:
    sidebar, header (topbar), footer and the shared scripts.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GPA Management System')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/custom/gridtable.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body>
    @auth
        @include('layouts.partials.sidebar')

        <div class="main-content" id="mainContent">
            @include('layouts.partials.header')

            <div class="page-body">
                @if(session('success'))
                    <div class="flash flash-success no-print">
                        <i class="fas fa-circle-check"></i>{{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="flash flash-error no-print">
                        <i class="fas fa-circle-exclamation"></i>{{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </div>

        @include('layouts.partials.footer')
    @else
        <div class="container py-4">
            @yield('content')
        </div>
    @endauth

    {{-- TableHelper needs jQuery (see TABLE-HELPER-GUIDE.md §1) and Bootstrap 5. --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/table-helper.js') }}"></script>
    @include('layouts.partials.scripts')
    @stack('scripts')
</body>

</html>
