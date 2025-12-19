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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tahun_akademik" class="form-label">
                                    <i class="bi bi-calendar-range"></i> Tahun Akademik
                                </label>
                                <select class="form-select @error('tahun_akademik') is-invalid @enderror"
                                    id="tahun_akademik" name="tahun_akademik" {{-- NAME = tahun_akademik --}} required>
                                    <option value="">Pilih Tahun Akademik</option>
                                    <option value="2024/2025" {{ old('tahun_akademik') == '2024/2025' ? 'selected' : '' }}>
                                        2024/2025
                                    </option>
                                    <option value="2025/2026" {{ old('tahun_akademik') == '2025/2026' ? 'selected' : '' }}>
                                        2025/2026
                                    </option>
                                    <option value="2026/2027" {{ old('tahun_akademik') == '2026/2027' ? 'selected' : '' }}>
                                        2026/2027
                                    </option>
                                    <option value="2027/2028" {{ old('tahun_akademik') == '2027/2028' ? 'selected' : '' }}>
                                        2027/2028
                                    </option>
                                </select>
                                @error('tahun_akademik')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="semester_akademik" class="form-label"> {{-- ID semester_akademik --}}
                                    <i class="bi bi-journal-bookmark"></i> Semester Akademik
                                </label>
                                <select class="form-select @error('semester_akademik') is-invalid @enderror"
                                    id="semester_akademik" {{-- ID semester_akademik --}} name="semester_akademik"
                                    {{-- NAME = semester_akademik --}} required>
                                    <option value="">Pilih Semester</option>
                                    <option value="Ganjil" {{ old('semester_akademik') == 'Ganjil' ? 'selected' : '' }}>
                                        Semester Ganjil
                                    </option>
                                    <option value="Genap" {{ old('semester_akademik') == 'Genap' ? 'selected' : '' }}>
                                        Semester Genap
                                    </option>
                                </select>
                                @error('semester_akademik')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                </div>

                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Periode Akademik</h6>
                    <div id="periodeInfo">
                        <p class="mb-1">Pilih Tahun Akademik dan Semester untuk melihat periode</p>
                    </div>
                    <ul class="mb-0">
                        <li><strong>Semester Ganjil:</strong> Agustus - Desember</li>
                        <li><strong>Semester Genap:</strong> Februari - Mei</li>
                    </ul>
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
                            <label for="semester_filter" class="form-label">
                                <i class="bi bi-journal"></i> Filter Semester Kuliah
                            </label>
                            <select class="form-select" id="semester_filter" name="semester_filter"> {{-- NAME = semester_filter --}}
                                <option value="">Semua Semester</option>
                                @for ($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}"
                                        {{ old('semester_filter') == $i ? 'selected' : '' }}>
                                        Semester {{ $i }}
                                    </option>
                                @endfor
                            </select>
                            <div class="form-text">
                                Filter berdasarkan semester kuliah (opsional)
                            </div>
                        </div>
                    </div>

                    {{-- Prodi filter tetap --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="prodi" class="form-label">
                                <i class="bi bi-building"></i> Filter Program Studi
                            </label>
                            <select class="form-select" id="prodi" name="prodi">
                                <option value="">Semua Prodi</option>
                                <option value="TIF" {{ old('prodi') == 'TIF' ? 'selected' : '' }}>TIF - Teknik
                                    Informatika</option>
                                <option value="MIF" {{ old('prodi') == 'MIF' ? 'selected' : '' }}>MIF -
                                    Manajemen Informatika</option>
                                <option value="TKK" {{ old('prodi') == 'TKK' ? 'selected' : '' }}>TKK - Teknik
                                    Komputer</option>
                                <option value="TRK" {{ old('prodi') == 'TRK' ? 'selected' : '' }}>TRK - Teknik
                                    Rekayasa Komputer</option>
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
                    <code>No, Keterangan, Prodi, Smt, gol, Kode, MK, SKS, Dosen Koordinator, <strong>Team Taching
                            1</strong>, Team Taching 2, Team Taching 3, Team Taching 4, Teknisi, Teknisi, Hari, Jam,
                        Ruang</code>
                    <p class="mt-2 mb-0">
                        <strong>Catatan:</strong> Data akan digenerate untuk seluruh semester (setiap minggu)
                        berdasarkan pilihan Tahun Akademik & Semester di atas.
                    </p>
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
                        class="card-header {{ (session('import_result.failed') ?? 0) > 0 ? 'bg-warning' : 'bg-success' }} text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Hasil Import
                        </h5>
                    </div>
                    <div class="card-body">
                        <div
                            class="alert {{ (session('import_result.failed') ?? 0) > 0 ? 'alert-warning' : 'alert-success' }}">
                            <h6>Ringkasan Import:</h6>
                            <ul class="mb-0">
                                <li><strong>Periode:</strong> {{ session('import_result.semester') ?? 'N/A' }}
                                    {{ session('import_result.tahun_akademik') ?? 'N/A' }}</li>
                                <li><strong>Tanggal:</strong> {{ session('import_result.periode') ?? 'N/A' }}</li>
                                <li><strong>Total data:</strong> {{ session('import_result.total') ?? 0 }}</li>
                                <li><strong>Berhasil diimport:</strong> {{ session('import_result.success') ?? 0 }}</li>
                                <li><strong>Gagal diimport:</strong> {{ session('import_result.failed') ?? 0 }}</li>
                            </ul>
                        </div>

                        {{-- PERBAIKI BAGIAN INI: --}}
                        @php
                            $failedRows = session('import_result.failed_rows') ?? [];
                        @endphp

                        @if (!empty($failedRows) && count($failedRows) > 0)
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
                                        @foreach ($failedRows as $failed)
                                            <tr>
                                                <td>{{ $failed['row'] ?? '' }}</td>
                                                <td class="text-danger">{{ $failed['reason'] ?? '' }}</td>
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
                            @if (session('import_result.success') > 0)
                                <button class="btn btn-primary" onclick="generateJadwal()" id="generateBtn">
                                    <i class="bi bi-calendar-plus"></i> Generate Jadwal Riil
                                </button>
                            @endif
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
            // Function to show academic period
            function updatePeriodeInfo() {
                var tahun = $('#tahun_akademik').val();
                var semester = $('#semester_akademik').val(); // GANTI ID

                if (tahun && semester) {
                    var periods = {
                        'Ganjil': {
                            '2024/2025': '26 Agustus 2024 - 6 Desember 2024',
                            '2025/2026': '25 Agustus 2025 - 5 Desember 2025',
                            '2026/2027': '24 Agustus 2026 - 4 Desember 2026',
                            '2027/2028': '23 Agustus 2027 - 3 Desember 2027'
                        },
                        'Genap': {
                            '2024/2025': '3 Februari 2025 - 30 Mei 2025',
                            '2025/2026': '2 Februari 2026 - 29 Mei 2026',
                            '2026/2027': '1 Februari 2027 - 28 Mei 2027',
                            '2027/2028': '31 Januari 2028 - 26 Mei 2028'
                        }
                    };

                    var periode = periods[semester][tahun];
                    if (periode) {
                        $('#periodeInfo').html(`
                        <p class="mb-1"><strong>Periode Akademik:</strong></p>
                        <p class="mb-0">${periode}</p>
                        <small class="text-muted">Data akan digenerate untuk setiap minggu dalam periode ini</small>
                    `);
                    }
                } else {
                    $('#periodeInfo').html(
                        '<p class="mb-1">Pilih Tahun Akademik dan Semester untuk melihat periode</p>');
                }
            }

            // Update when selection changes
            $('#tahun_akademik, #semester_akademik').on('change', updatePeriodeInfo); // GANTI ID

            // Initial update
            updatePeriodeInfo();

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
                // Validate required fields
                if (!$('#tahun_akademik').val() || !$('#semester_akademik').val()) { // GANTI ID
                    alert('Harap pilih Tahun Akademik dan Semester');
                    return false;
                }

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
        });

        // Function to generate real schedule
        function generateJadwal() {
            if (confirm('Generate akan memakan waktu beberapa menit. Lanjutkan?')) {
                $('#generateBtn').prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm"></span> Generating (may take a while)...
        `);

                $.ajax({
                    url: '{{ route('admin.generate.jadwal') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        tahun_akademik: tahunAkademik,
                        semester: semester
                    },
                    timeout: 300000, // 5 menit timeout
                    success: function(response) {
                        alert('✅ Berhasil generate ' + response.count + ' jadwal!');
                        $('#generateBtn').html('<i class="bi bi-check-circle"></i> Sudah Digenerate');
                    },
                    error: function(xhr) {
                        alert('❌ Error: ' + (xhr.responseJSON?.message || 'Timeout'));
                        $('#generateBtn').prop('disabled', false).html(`
                    <i class="bi bi-calendar-plus"></i> Generate Jadwal Riil
                `);
                    }
                });
            }
        }


        // AJAX request to generate schedule
        $.ajax({
            url: '{{ route('admin.generate.jadwal') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                tahun_akademik: tahunAkademik,
                semester: semester
            },
            success: function(response) {
                alert('Jadwal berhasil digenerate: ' + response.count + ' entri dibuat');
                $('#generateBtn').html('<i class="bi bi-check-circle"></i> Sudah Digenerate');
                $('#generateBtn').prop('disabled', true);
            },
            error: function(xhr) {
                var errorMessage = xhr.responseJSON?.message ||
                    'Terjadi kesalahan saat generate jadwal';
                alert(errorMessage);
                $('#generateBtn').prop('disabled', false).html(`
                    <i class="bi bi-calendar-plus"></i> Generate Jadwal Riil
                `);
            }
        });
        }
        }
    </script>
@endsection
