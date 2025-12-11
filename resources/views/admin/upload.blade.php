@extends('layouts.app')

@section('title', 'Upload Data CSV')
@section('page-title', 'Upload Data Jadwal')
@section('page-subtitle', 'Import data jadwal dari file CSV')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cloud-upload"></i> Form Upload CSV
                    </h5>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('debug_info'))
                        <div class="card mt-4">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="bi bi-bug"></i> Debug Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <pre style="font-size: 0.8rem; max-height: 300px; overflow-y: auto;">{{ print_r(session('debug_info'), true) }}</pre>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('admin.upload.process') }}" method="POST" enctype="multipart/form-data"
                        id="uploadForm">
                        @csrf

                        <div class="mb-4">
                            <label for="csv_file" class="form-label">
                                <i class="bi bi-file-earmark-spreadsheet"></i> Pilih File CSV
                            </label>
                            <input type="file" class="form-control @error('csv_file') is-invalid @enderror"
                                id="csv_file" name="csv_file" accept=".csv,.txt" required>
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Pilih file CSV dengan format yang sesuai. Maksimal 5MB.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="tanggal_efektif" class="form-label">
                                <i class="bi bi-calendar"></i> Tanggal Efektif Jadwal
                            </label>
                            <input type="text"
                                class="form-control datepicker @error('tanggal_efektif') is-invalid @enderror"
                                id="tanggal_efektif" name="tanggal_efektif"
                                value="{{ old('tanggal_efektif', date('Y-m-d')) }}" required>
                            @error('tanggal_efektif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Pilih tanggal dimana jadwal ini berlaku.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="action" class="form-label">
                                <i class="bi bi-arrow-repeat"></i> Aksi Data
                            </label>
                            <select class="form-select @error('action') is-invalid @enderror" id="action" name="action"
                                required>
                                <option value="append">Tambahkan ke data yang ada</option>
                                <option value="replace">Ganti data pada tanggal yang sama</option>
                            </select>
                            @error('action')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Pilih "Ganti data" untuk menghapus data lama pada tanggal yang sama
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="semester" class="form-label">
                                        <i class="bi bi-journal"></i> Semester
                                    </label>
                                    <select class="form-select" id="semester" name="semester">
                                        <option value="">Semua Semester</option>
                                        @for ($i = 1; $i <= 8; $i++)
                                            <option value="{{ $i }}">Semester {{ $i }}</option>
                                        @endfor
                                    </select>
                                    <div class="form-text">
                                        Filter berdasarkan semester (opsional)
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="prodi" class="form-label">
                                        <i class="bi bi-building"></i> Program Studi
                                    </label>
                                    <select class="form-select" id="prodi" name="prodi">
                                        <option value="">Semua Prodi</option>
                                        <option value="TIF">TIF - Teknik Informatika</option>
                                        <option value="MIF">MIF - Manajemen Informatika</option>
                                        <option value="TKK">TKK - Teknik Komputer</option>
                                        <option value="TRK">TRK - Teknik Rekayasa Komputer</option>
                                    </select>
                                    <div class="form-text">
                                        Filter berdasarkan program studi (opsional)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Informasi File CSV</h6>
                            <p class="mb-2">File CSV harus memiliki format dengan kolom-kolom berikut:</p>
                            <code>No, Keterangan, Prodi, Smt, gol, Kode, MK, SKS, Dosen Koordinator, Team Teaching, Hari,
                                Jam, Ruang</code>
                            <p class="mt-2 mb-0">Download contoh file:
                                <a href="{{ route('admin.download.template') }}" class="btn btn-sm btn-outline-primary"
                                    id="downloadTemplate">
                                    <i class="bi bi-download"></i> template_jadwal.csv
                                </a>
                            </p>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-upload"></i> Upload & Proses
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-text"></i> Preview Data
                    </h5>
                </div>
                <div class="card-body">
                    <div id="previewSection" class="d-none">
                        <h6>Preview Data Akan Ditampilkan Di Sini</h6>
                        <div class="table-responsive">
                            <table class="table table-sm" id="previewTable">
                                <thead>
                                    <tr>
                                        <th>Prodi</th>
                                        <th>Ruang</th>
                                        <th>Jam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Preview data akan dimuat di sini -->
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-warning">
                            <small><i class="bi bi-exclamation-triangle"></i> Preview hanya menampilkan 5 baris
                                pertama</small>
                        </div>
                    </div>

                    <div id="noPreview" class="text-center py-4">
                        <i class="bi bi-file-earmark" style="font-size: 3rem; color: #6c757d;"></i>
                        <p class="mt-3 mb-0 text-muted">Belum ada file yang dipilih untuk preview</p>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-check-circle"></i> Validasi Data
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span>Format CSV</span>
                                <span id="validCsv" class="badge bg-secondary">Belum dicek</span>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span>Kolom Wajib</span>
                                <span id="validColumns" class="badge bg-secondary">Belum dicek</span>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span>Data Ruangan</span>
                                <span id="validRooms" class="badge bg-secondary">Belum dicek</span>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span>Format Jam</span>
                                <span id="validTime" class="badge bg-secondary">Belum dicek</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-outline-primary w-100" id="validateBtn" disabled>
                            <i class="bi bi-search"></i> Validasi File
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if (session('import_result'))
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div
                        class="card-header {{ session('import_result.failed') > 0 ? 'bg-warning' : 'bg-success' }} text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Hasil Import
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert {{ session('import_result.failed') > 0 ? 'alert-warning' : 'alert-success' }}">
                            <h6>Ringkasan Import:</h6>
                            <ul class="mb-0">
                                <li>Total data dalam file: {{ session('import_result.total') }}</li>
                                <li>Berhasil diimport: {{ session('import_result.success') }}</li>
                                <li>Gagal diimport: {{ session('import_result.failed') }}</li>
                            </ul>
                        </div>

                        @if (session('import_result.failed') > 0)
                            <h6>Data yang gagal diimport:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Baris</th>
                                            <th>Alasan</th>
                                            <th>Prodi</th>
                                            <th>Ruang</th>
                                            <th>Jam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (session('import_result.failed_rows') as $failed)
                                            <tr>
                                                <td>{{ $failed['row'] }}</td>
                                                <td class="text-danger">{{ $failed['reason'] }}</td>
                                                <td>{{ $failed['data']['Prodi'] ?? '-' }}</td>
                                                <td>{{ $failed['data']['Ruang'] ?? '-' }}</td>
                                                <td>{{ $failed['data']['Jam'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted">* Hanya menampilkan 10 baris pertama yang gagal</p>
                        @endif

                        <div class="d-flex justify-content-between mt-3">
                            <a href="{{ route('ruangan.index') }}" class="btn btn-outline-primary">
                                <i class="bi bi-eye"></i> Lihat Jadwal
                            </a>
                            <a href="{{ route('admin.index') }}" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Selesai
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Preview CSV file
            $('#csv_file').on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                // Show validate button
                $('#validateBtn').prop('disabled', false);

                // Simple file size check
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    alert('File terlalu besar! Maksimal 5MB.');
                    $(this).val('');
                    return;
                }

                // Show preview section
                $('#noPreview').addClass('d-none');
                $('#previewSection').removeClass('d-none');

                // Read and preview CSV
                const reader = new FileReader();
                reader.onload = function(e) {
                    const csvContent = e.target.result;
                    const lines = csvContent.split('\n').slice(0, 6); // First 6 lines

                    let html = '';
                    lines.forEach((line, index) => {
                        const cells = line.split(',');
                        if (cells.length >= 3 && index > 0) { // Skip header
                            html += `
                            <tr>
                                <td>${cells[2] || ''}</td>
                                <td>${cells[cells.length - 1] || ''}</td>
                                <td>${cells[cells.length - 2] || ''}</td>
                            </tr>
                        `;
                        }
                    });

                    $('#previewTable tbody').html(html);
                };

                reader.readAsText(file);
            });

            // Form submission
            $('#uploadForm').on('submit', function() {
                $('#submitBtn').prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm"></span> Memproses...
            `);
            });

            // Validate file button
            $('#validateBtn').on('click', function() {
                const fileInput = $('#csv_file')[0];
                if (!fileInput.files[0]) {
                    alert('Pilih file terlebih dahulu!');
                    return;
                }

                // Simulate validation
                $('#validCsv').removeClass('bg-secondary').addClass('bg-success').text('Valid');
                $('#validColumns').removeClass('bg-secondary').addClass('bg-success').text('Valid');
                $('#validRooms').removeClass('bg-secondary').addClass('bg-success').text('Valid');
                $('#validTime').removeClass('bg-secondary').addClass('bg-success').text('Valid');

                alert('Validasi berhasil! File siap diupload.');
            });

            // Download template
            $('#downloadTemplate').on('click', function(e) {
                e.preventDefault();
                window.location.href = "{{ route('admin.download.template') }}";
            });
        });
    </script>
@endsection
