@extends('layouts.app')

@section('title', 'Beranda - Sistem Jadwal Ruangan')
@section('page-title', 'Beranda')
@section('page-subtitle', 'Sistem Informasi Jadwal Penggunaan Ruangan')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle"></i> Tentang Sistem
                    </h5>
                </div>
                <div class="card-body">
                    <p>Sistem ini dibuat untuk mengelola jadwal penggunaan ruangan di lingkungan kampus.</p>

                    <h6>Fitur Utama:</h6>
                    <ul>
                        <li><strong>Monitoring Ruangan:</strong> Lihat jadwal penggunaan ruangan per hari</li>
                        <li><strong>Filter Tanggal:</strong> Cari jadwal berdasarkan tanggal tertentu</li>
                        <li><strong>Admin Panel:</strong> Upload data jadwal dari file CSV</li>
                        <li><strong>Tabel Interaktif:</strong> Tampilan grid ruangan vs jam</li>
                    </ul>

                    <h6>Cara Menggunakan:</h6>
                    <ol>
                        <li>Pergi ke halaman <a href="{{ route('ruangan.index') }}">Ruangan</a> untuk melihat jadwal</li>
                        <li>Gunakan filter tanggal untuk melihat jadwal hari tertentu</li>
                        <li>Admin dapat mengupload data melalui halaman <a href="{{ route('admin.index') }}">Admin</a></li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning-charge"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('ruangan.index') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-building"></i> Lihat Jadwal Ruangan
                        </a>
                        <a href="{{ route('admin.index') }}" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-upload"></i> Upload Data Jadwal
                        </a>
                        <a href="{{ route('admin.upload') }}" class="btn btn-outline-success btn-lg">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Import CSV
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-check"></i> Statistik Hari Ini
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h1 class="display-4">{{ date('d') }}</h1>
                        <h5>{{ date('F Y') }}</h5>
                        <p class="text-muted">{{ \Carbon\Carbon::now()->translatedFormat('l') }}</p>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h6>Total Ruangan</h6>
                            <h4>--</h4>
                        </div>
                        <div class="col-6">
                            <h6>Jadwal Hari Ini</h6>
                            <h4>--</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history"></i> Jadwal Hari Ini
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Belum ada data jadwal. Silakan upload data melalui halaman Admin.
                    </div>
                    <a href="{{ route('admin.upload') }}" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Upload Data Pertama
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
