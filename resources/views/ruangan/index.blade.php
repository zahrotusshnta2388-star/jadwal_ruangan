@extends('layouts.app')

@section('title', 'Jadwal Ruangan')
@section('page-title', 'Jadwal Penggunaan Ruangan')
@section('page-subtitle', 'Lihat jadwal penggunaan ruangan berdasarkan tanggal')

@section('content')
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-filter"></i> Filter Jadwal
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('ruangan.index') }}" method="GET" id="filterForm">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="tanggal" class="form-label">
                                        <i class="bi bi-calendar"></i> Pilih Tanggal
                                    </label>
                                    <input type="text" class="form-control datepicker" id="tanggal" name="tanggal"
                                        value="{{ request('tanggal', date('Y-m-d')) }}" required>
                                    <div class="form-text">Pilih tanggal untuk melihat jadwal</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="gedung" class="form-label">
                                        <i class="bi bi-building"></i> Filter Gedung
                                    </label>
                                    <select class="form-select" id="gedung" name="gedung">
                                        <option value="">Semua Gedung</option>
                                        <option value="3" {{ request('gedung') == '3' ? 'selected' : '' }}>Gedung 3
                                        </option>
                                        <option value="4" {{ request('gedung') == '4' ? 'selected' : '' }}>Gedung 4
                                        </option>
                                        <option value="F" {{ request('gedung') == 'F' ? 'selected' : '' }}>Gedung F
                                        </option>
                                        <option value="Lab" {{ request('gedung') == 'Lab' ? 'selected' : '' }}>
                                            Laboratorium</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Tambahkan di form filter, setelah filter gedung --}}
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="kelas" class="form-label">
                                        <i class="bi bi-people"></i> Filter Kelas
                                    </label>
                                    <select class="form-select" id="kelas" name="kelas">
                                        <option value="">Semua Kelas</option>
                                        @foreach ($allKelas ?? [] as $kelasOption)
                                            <option value="{{ $kelasOption }}"
                                                {{ request('kelas') == $kelasOption ? 'selected' : '' }}>
                                                {{ $kelasOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="semester" class="form-label">
                                    <i class="bi bi-journal-bookmark"></i> Semester
                                </label>
                                <select class="form-select" id="semester" name="semester">
                                    <option value="">Semua Semester</option>
                                    <option value="Ganjil" {{ request('semester') == 'Ganjil' ? 'selected' : '' }}>
                                        Ganjil</option>
                                    <option value="Genap" {{ request('semester') == 'Genap' ? 'selected' : '' }}>Genap
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Tampilkan Jadwal
                                </button>
                                <a href="{{ route('ruangan.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </a>
                            </div>
                        </div>
                </div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card container">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">
                            <i class="bi bi-table"></i> Tabel Jadwal Ruangan
                        </h5>
                        <small class="d-block">
                            Tanggal: {{ $selectedDate }}
                            @if ($kelas)
                                | Kelas: <strong>{{ $kelas }}</strong>
                            @endif
                        </small>
                    </div>
                    <button class="btn btn-light btn-sm" onclick="printTable()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
                <div class="card-body">
                    @if (isset($jadwals) && count($jadwals) > 0)
                        <!-- Table Jadwal Grid -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-jadwal" id="jadwalTable">
                                <thead class="ruangan-header">
                                    <tr>
                                        <th>Ruangan/Jam</th>
                                        @foreach ($timeSlots as $timeSlot)
                                            <th class="text-center">{{ $timeSlot }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ruangans as $ruangan)
                                        <tr>
                                            <td class="ruangan-header fw-bold">
                                                {{ $ruangan }}
                                                @php
                                                    // Hitung kelas di ruangan ini
                                                    $kelasCount = collect($jadwals)
                                                        ->where('ruangan', $ruangan)
                                                        ->count();
                                                @endphp
                                                @if ($kelasCount > 0)
                                                    <br>
                                                    <small class="text-muted">({{ $kelasCount }} kelas)</small>
                                                @endif
                                            </td>
                                            @foreach ($timeSlots as $timeSlot)
                                                @php
                                                    $jadwal = $scheduleGrid[$ruangan][$timeSlot] ?? null;
                                                @endphp
                                                <td class="ruangan-cell text-center {{ $jadwal ? 'occupied' : 'empty' }}"
                                                    data-bs-toggle="{{ $jadwal ? 'tooltip' : '' }}"
                                                    title="{{ $jadwal ? $jadwal->prodi . ' ' . $jadwal->semester . $jadwal->golongan . ' - ' . $jadwal->mata_kuliah : 'Kosong' }}">
                                                    @if ($jadwal)
                                                        <div class="schedule-info" style="line-height: 1.1;">
                                                            {{-- Baris 1: Kelas --}}
                                                            <small class="d-block fw-bold" style="font-size: 0.8rem;">
                                                                {{ $jadwal->prodi }}
                                                                {{ $jadwal->semester }}{{ $jadwal->golongan }}
                                                            </small>

                                                            {{-- Baris 2: Mata Kuliah --}}
                                                            <small class="d-block text-muted" style="font-size: 0.7rem;">
                                                                {{ $jadwal->mata_kuliah }}
                                                            </small>

                                                            {{-- Baris 3: Jam --}}
                                                            <small class="d-block text-primary" style="font-size: 0.65rem;">
                                                                {{ substr($jadwal->jam_mulai, 0, 5) }}
                                                            </small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Legend -->
                        <div class="mt-4">
                            <h6><i class="bi bi-info-circle"></i> Keterangan:</h6>
                            <div class="d-flex gap-3">
                                <div>
                                    <span class="badge bg-success">TIF 3 C</span> = Teknik Informatika, Semester 3,
                                    Golongan
                                    C
                                </div>
                                <div>
                                    <span class="badge bg-primary">MIF 3 A</span> = Manajemen Informatika, Semester 3,
                                    Golongan A
                                </div>
                                <div>
                                    <span class="badge bg-secondary">-</span> = Ruangan Kosong
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-calendar-x" style="font-size: 3rem; color: #6c757d;"></i>
                            </div>
                            <h4>Belum Ada Data Jadwal</h4>
                            <p class="text-muted mb-4">
                                Tidak ada jadwal untuk tanggal {{ request('tanggal', date('Y-m-d')) }}.
                                @if (request('gedung'))
                                    <br>Filter gedung: {{ request('gedung') }}
                                @endif
                            </p>
                            <div>
                                <a href="{{ route('admin.upload') }}" class="btn btn-primary">
                                    <i class="bi bi-upload"></i> Upload Data CSV
                                </a>
                                <a href="{{ route('ruangan.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-calendar"></i> Lihat Hari Ini
                                </a>
                            </div>
                        </div>


                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function printTable() {
            var printContents = document.getElementById('jadwalTable').outerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = `
            <html>
                <head>
                    <title>Jadwal Ruangan - {{ request('tanggal', date('Y-m-d')) }}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        .table { font-size: 12px; }
                        .table th, .table td { padding: 5px; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <h3>Jadwal Ruangan</h3>
                    <p>Tanggal: {{ request('tanggal', date('Y-m-d')) }}</p>
                    ${printContents}
                    <div class="mt-4">
                        <p><small>Dicetak pada: ${new Date().toLocaleString('id-ID')}</small></p>
                    </div>
                    <button class="btn btn-primary no-print" onclick="window.close()">Tutup</button>
                    <script>
                        window.onload = function() {
                            window.print();
                        }
                    <\/script>
                </body>
            </html>
        `;

            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }

        // Initialize tooltips
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
