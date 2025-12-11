@extends('layouts.app')

@section('title', 'Admin Panel')
@section('page-title', 'Admin Panel')
@section('page-subtitle', 'Kelola data jadwal ruangan')

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-upload"></i> Upload Data
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="bi bi-file-earmark-spreadsheet" style="font-size: 4rem; color: #0d6efd;"></i>
                    </div>
                    <h4>Import Data CSV</h4>
                    <p class="text-muted">
                        Upload file CSV berisi data jadwal untuk ditampilkan di sistem.
                    </p>
                    <a href="{{ route('admin.upload') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-cloud-upload"></i> Go to Upload
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-database"></i> Data Saat Ini
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Total Jadwal
                            <span class="badge bg-primary rounded-pill">{{ $totalJadwal ?? '0' }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Tanggal Terbaru
                            <span class="badge bg-info rounded-pill">{{ $latestDate ?? '-' }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Total Ruangan
                            <span class="badge bg-success rounded-pill">{{ $totalRuangan ?? '0' }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Program Studi
                            <span class="badge bg-warning rounded-pill">{{ $totalProdi ?? '0' }}</span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('ruangan.index') }}" class="btn btn-outline-success w-100">
                            <i class="bi bi-eye"></i> Lihat Data
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear"></i> Management
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary text-start">
                            <i class="bi bi-trash"></i> Hapus Data Lama
                        </button>
                        <button class="btn btn-outline-primary text-start">
                            <i class="bi bi-download"></i> Export Data
                        </button>
                        <button class="btn btn-outline-primary text-start">
                            <i class="bi bi-back"></i> Backup Database
                        </button>
                        <button class="btn btn-outline-danger text-start">
                            <i class="bi bi-exclamation-triangle"></i> Reset Semua Data
                        </button>
                    </div>

                    <hr>

                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Informasi Format CSV</h6>
                        <p class="small mb-0">
                            File CSV harus memiliki header: No, Keterangan, Prodi, Smt, gol, Kode, MK, SKS, Dosen
                            Koordinator, Team Teaching, Hari, Jam, Ruang
                        </p>
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
                        <i class="bi bi-clock-history"></i> Riwayat Upload
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal Upload</th>
                                    <th>File Name</th>
                                    <th>Records</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="bi bi-database-slash"></i> Belum ada riwayat upload
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <h5><i class="bi bi-exclamation-triangle"></i> Perhatian!</h5>
                <ul class="mb-0">
                    <li>Hanya admin yang berwenang mengupload data</li>
                    <li>Pastikan format CSV sesuai dengan template</li>
                    <li>Data yang diupload akan menggantikan data lama pada tanggal yang sama</li>
                    <li>Lakukan backup data secara berkala</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
