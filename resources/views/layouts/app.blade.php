<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Jadwal Ruangan')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Datepicker CSS -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

    <style>
        body {
            padding-top: 20px;
            background-color: #f8f9fa;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
        }

        .card {
            box-shadow: 0 2px 4px rgba(0, 0, 0, .05);
            border: none;
            margin-bottom: 20px;
        }

        .table-jadwal {
            font-size: 0.85rem;
        }

        .ruangan-header {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .jam-header {
            writing-mode: vertical-lr;
            transform: rotate(180deg);
            text-align: center;
            padding: 10px 5px;
            background-color: #f8f9fa;
        }

        .ruangan-cell {
            min-width: 120px;
            height: 60px;
            border: 1px solid #dee2e6;
            padding: 5px;
            font-size: 0.8rem;
        }

        .occupied {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .empty {
            background-color: #f8f9fa;
        }

        .footer {
            margin-top: 40px;
            padding: 20px 0;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
        }

        .ruangan-cell {
            min-width: 100px;
            height: 70px;
            vertical-align: middle !important;
            font-size: 0.8rem;
        }

        .occupied {
            background-color: #d4edda !important;
            border-left: 3px solid #28a745 !important;
        }

        .schedule-info {
            line-height: 1.2;
        }
    </style>

    @yield('styles')
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="bi bi-calendar-week"></i> JTI Schedule
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('ruangan.index') ? 'active' : '' }}"
                            href="{{ route('ruangan.index') }}">
                            <i class="bi bi-table"></i> Jadwal Ruangan
                        </a>
                    </li>
                    @auth

                        @if (Auth::user()->role === 'admin')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.upload') ? 'active' : '' }}"
                                    href="{{ route('admin.upload') }}">
                                    <i class="bi bi-upload"></i> Upload CSV
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>

                <!-- Right side: User menu -->
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                                <li>
                                    <div class="user-info">
                                        <strong>{{ Auth::user()->name }}</strong><br>
                                        <small class="text-muted">{{ Auth::user()->email }}</small><br>
                                        <span class="badge bg-success">{{ Auth::user()->role }}</span>
                                    </div>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-person"></i> Profil
                                    </a>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                        @csrf
                                        <a class="dropdown-item" href="#"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="bi bi-box-arrow-right"></i> Logout
                                        </a>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right"></i> Login Admin
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Title -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-0">@yield('page-title')</h2>
                <p class="text-muted">@yield('page-subtitle')</p>
            </div>
        </div>

        <!-- Content -->
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; {{ date('Y') }} Sistem Jadwal Ruangan. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Laravel {{ app()->version() }} | PHP {{ phpversion() }}</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.id.min.js">
    </script>

    <script>
        $(document).ready(function() {
            // Initialize datepicker
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                language: 'id',
                autoclose: true,
                todayHighlight: true
            });

            // Set today's date as default
            $('.datepicker').datepicker('setDate', new Date());

            // Form validation
            $('form').on('submit', function() {
                $(this).find('button[type="submit"]').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Loading...');
            });
        });
    </script>

    @yield('scripts')
</body>

</html>
