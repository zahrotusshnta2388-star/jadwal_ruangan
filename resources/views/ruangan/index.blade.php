@extends('layouts.app')

@section('title', 'Jadwal Ruangan')
@section('page-title', 'Jadwal Penggunaan Ruangan')
@section('page-subtitle', 'Lihat jadwal penggunaan ruangan berdasarkan tanggal')

@push('styles')
    <style>
        /* CSS untuk tombol CRUD */
        .action-buttons {
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px dashed #ddd;
        }

        .action-buttons .btn-sm {
            padding: 2px 6px;
            font-size: 0.7rem;
            margin: 2px;
        }

        .ruangan-cell {
            min-height: 120px;
            vertical-align: top !important;
            padding: 8px 5px !important;
        }

        .ruangan-cell:hover .action-buttons {
            opacity: 1 !important;
        }

        .occupied {
            background-color: #e8f5e9 !important;
            border-left: 3px solid #4caf50 !important;
        }

        .empty {
            background-color: #f8f9fa !important;
        }

        .empty:hover {
            background-color: #f0f8ff !important;
        }
    </style>
@endpush

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
            <div class="card">
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
                                                            <small class="d-block fw-bold" style="font-size: 0.8rem;">
                                                                {{ $jadwal->prodi }}
                                                                {{ $jadwal->semester }}{{ $jadwal->golongan }}
                                                            </small>

                                                            <small class="d-block text-muted" style="font-size: 0.7rem;">
                                                                {{ $jadwal->mata_kuliah }}
                                                            </small>

                                                            <small class="d-block text-primary" style="font-size: 0.65rem;">
                                                                {{ substr($jadwal->jam_mulai, 0, 5) }} -
                                                                {{ substr($jadwal->jam_selesai, 0, 5) }}
                                                            </small>

                                                            {{-- ACTION BUTTONS --}}
                                                            <div class="mt-1 action-buttons">
                                                                <button class="btn btn-sm btn-outline-warning edit-btn"
                                                                    data-id="{{ $jadwal->id }}" data-bs-toggle="tooltip"
                                                                    title="Edit jadwal">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-danger delete-btn"
                                                                    data-id="{{ $jadwal->id }}"
                                                                    data-bs-toggle="tooltip" title="Hapus jadwal">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @else
                                                        {{-- CELL KOSONG --}}
                                                        <div class="empty-cell">
                                                            <span class="text-muted">-</span>
                                                            <div class="mt-1">
                                                                <button class="btn btn-sm btn-outline-success add-btn"
                                                                    data-ruangan="{{ $ruangan }}"
                                                                    data-jam="{{ $timeSlot }}"
                                                                    data-bs-toggle="tooltip" title="Tambah jadwal">
                                                                    <i class="bi bi-plus"></i> Tambah
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <h6><i class="bi bi-info-circle"></i> Keterangan:</h6>
                            <div class="d-flex gap-3">
                                <div>
                                    <span class="badge bg-success">TIF 3 C</span> = Teknik Informatika, Semester 3,
                                    Golongan C
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

    {{-- MODAL TAMBAH/EDIT JADWAL (VERSI DURASI 1-11 JAM) --}}
    <div class="modal fade" id="jadwalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jadwalModalLabel">Tambah Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="jadwalForm" autocomplete="off">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <input type="hidden" name="id" id="jadwalId">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" id="modal_tanggal" required>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Ruangan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="ruangan" id="modal_ruangan"
                                        readonly required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Durasi (Jam) <span class="text-danger">*</span></label>
                                    <select class="form-select" id="durasi_jam">
                                        <option value="1">1 Jam</option>
                                        <option value="2" selected>2 Jam</option>
                                        <option value="3">3 Jam</option>
                                        <option value="4">4 Jam</option>
                                        <option value="5">5 Jam</option>
                                        <option value="6">6 Jam</option>
                                        <option value="7">7 Jam</option>
                                        <option value="8">8 Jam</option>
                                        <option value="9">9 Jam</option>
                                        <option value="10">10 Jam</option>
                                        <option value="11">11 Jam</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                                    <select class="form-select" name="jam_mulai" id="modal_jam_mulai" required>
                                        <option value="">Pilih Jam Mulai</option>
                                        <option value="07:00">07:00</option>
                                        <option value="08:00">08:00</option>
                                        <option value="09:00">09:00</option>
                                        <option value="10:00">10:00</option>
                                        <option value="11:00">11:00</option>
                                        <option value="12:00">12:00</option>
                                        <option value="13:00">13:00</option>
                                        <option value="14:00">14:00</option>
                                        <option value="15:00">15:00</option>
                                        <option value="16:00">16:00</option>
                                        <option value="17:00">17:00</option>
                                        <option value="18:00">18:00</option>
                                        <option value="19:00">19:00</option>
                                        <option value="20:00">20:00</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="jam_selesai" id="modal_jam_selesai"
                                        readonly required>
                                    <small class="text-muted" id="durasi_info">Durasi: 2 Jam</small>
                                    <div class="form-text text-warning" id="jam_peringatan" style="display: none;">
                                        <i class="bi bi-exclamation-triangle"></i> Jam selesai melebihi jam 20:00
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="form-label">Prodi <span class="text-danger">*</span></label>
                                    <select class="form-select" name="prodi" id="modal_prodi" required>
                                        <option value="">Pilih Prodi</option>
                                        <option value="TIF">TIF</option>
                                        <option value="MIF">MIF</option>
                                        <option value="D3TI">D3TI</option>
                                        <option value="SIB">SIB</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="form-label">Semester <span class="text-danger">*</span></label>
                                    <select class="form-select" name="semester" id="modal_semester" required>
                                        <option value="">Pilih</option>
                                        @for ($i = 1; $i <= 8; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="form-label">Golongan <span class="text-danger">*</span></label>
                                    <select class="form-select" name="golongan" id="modal_golongan" required>
                                        <option value="">Pilih</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                        <option value="E">E</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kode Mata Kuliah</label>
                            <input type="text" class="form-control" name="kode_mk" id="modal_kode_mk">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mata Kuliah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="mata_kuliah" id="modal_mata_kuliah"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SKS <span class="text-danger">*</span></label>
                            <select class="form-select" name="sks" id="modal_sks" required>
                                <option value="">Pilih</option>
                                @for ($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    {{-- Setelah input SKS --}}
                    <div class="mb-3">
                        <label class="form-label">Dosen Koordinator</label>
                        <input type="text" class="form-control" name="dosen_koordinator"
                            id="modal_dosen_koordinator">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Team Teaching</label>
                        <textarea class="form-control" name="team_teaching" id="modal_team_teaching" rows="3"
                            placeholder="Masukkan nama dosen, pisahkan dengan koma"></textarea>
                        <div class="form-text">Pisahkan dengan koma: Dr. John Doe, M.Si, Dr. Jane Smith</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teknisi</label>
                        <input type="text" class="form-control" name="teknisi" id="modal_teknisi">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="saveButton">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL HAPUS --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus jadwal ini?</p>
                    <input type="hidden" id="deleteId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Fungsi untuk menghitung jam selesai
        function calculateJamSelesai() {
            let jamMulai = $('#modal_jam_mulai').val();
            let durasi = $('#durasi_jam').val();

            if (jamMulai) {
                let parts = jamMulai.split(':');
                let hour = parseInt(parts[0]);
                let minute = parseInt(parts[1]);

                // Tambah durasi
                hour += parseInt(durasi);

                // Format ke HH:MM
                let jamSelesai = hour.toString().padStart(2, '0') + ':' + minute.toString().padStart(2, '0');
                $('#modal_jam_selesai').val(jamSelesai);
                $('#durasi_info').text('Durasi: ' + durasi + ' Jam (' + jamMulai + ' - ' + jamSelesai + ')');

                // Tampilkan peringatan jika melebihi jam 20:00
                if (hour > 20) {
                    $('#jam_peringatan').show();
                    $('#saveButton').prop('disabled', true);
                } else {
                    $('#jam_peringatan').hide();
                    $('#saveButton').prop('disabled', false);
                }
            }
        }

        // Fungsi untuk validasi jam
        function validateJam() {
            let jamMulai = $('#modal_jam_mulai').val();
            let durasi = $('#durasi_jam').val();

            if (jamMulai) {
                let startHour = parseInt(jamMulai.split(':')[0]);
                let endHour = startHour + parseInt(durasi);

                return endHour <= 20; // Maksimal jam 20:00
            }
            return true;
        }

        // Fungsi untuk update pilihan jam mulai berdasarkan durasi
        function updateJamMulaiOptions() {
            let durasi = parseInt($('#durasi_jam').val());
            let selectedJam = $('#modal_jam_mulai').val();

            // Reset semua option
            $('#modal_jam_mulai option').prop('disabled', false).show();

            // Disable option yang menyebabkan jam selesai > 20:00
            $('#modal_jam_mulai option').each(function() {
                let optionValue = $(this).val();
                if (optionValue) {
                    let optionHour = parseInt(optionValue.split(':')[0]);
                    if (optionHour + durasi > 20) {
                        $(this).prop('disabled', true);
                    }
                }
            });

            // Jika jam yang dipilih sekarang tidak valid, reset
            if (selectedJam) {
                let startHour = parseInt(selectedJam.split(':')[0]);
                if (startHour + durasi > 20) {
                    $('#modal_jam_mulai').val('');
                    $('#modal_jam_selesai').val('');
                    $('#durasi_info').text('Durasi: ' + durasi + ' Jam');
                    $('#saveButton').prop('disabled', true);
                }
            }
        }

        $(document).ready(function() {
            // Setup CSRF
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Event untuk jam mulai dan durasi
            $('#modal_jam_mulai, #durasi_jam').change(function() {
                updateJamMulaiOptions();
                calculateJamSelesai();
            });

            // 1. TOMBOL TAMBAH - Versi dengan pilihan jam
            $(document).on('click', '.add-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let ruangan = $(this).data('ruangan');
                let jamSlot = $(this).data('jam'); // Jam default dari cell

                // Reset form
                $('#jadwalForm')[0].reset();
                $('#formMethod').val('POST');
                $('#jadwalId').val('');
                $('#jadwalModalLabel').text('Tambah Jadwal');
                $('#saveButton').text('Simpan').prop('disabled', false);
                $('#jam_peringatan').hide();

                // Isi data awal
                $('#modal_ruangan').val(ruangan);
                $('#modal_tanggal').val('{{ $selectedDate }}');

                // Set jam mulai default dari cell yang diklik
                if (jamSlot) {
                    $('#modal_jam_mulai').val(jamSlot);
                }

                // Set durasi default 2 jam
                $('#durasi_jam').val('2');

                // Update pilihan jam mulai
                updateJamMulaiOptions();

                // Hitung jam selesai
                calculateJamSelesai();

                $('#jadwalModal').modal('show');
            });

            // 2. TOMBOL EDIT - Versi dengan pilihan jam
            $(document).on('click', '.edit-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let id = $(this).data('id');

                $.ajax({
                    url: '/ruangan/edit/' + id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            // Reset form
                            $('#jadwalForm')[0].reset();
                            $('#formMethod').val('PUT');
                            $('#jadwalId').val(response.data.id);
                            $('#jadwalModalLabel').text('Edit Jadwal');
                            $('#saveButton').text('Update').prop('disabled', false);
                            $('#jam_peringatan').hide();

                            let data = response.data;

                            // Isi form
                            $('#modal_tanggal').val(data.tanggal);
                            $('#modal_ruangan').val(data.ruangan);

                            // Format jam
                            let jamMulai = data.jam_mulai.substring(0, 5);
                            let jamSelesai = data.jam_selesai.substring(0, 5);

                            $('#modal_jam_mulai').val(jamMulai);
                            $('#modal_jam_selesai').val(jamSelesai);
                            $('#modal_prodi').val(data.prodi);
                            $('#modal_semester').val(data.semester);
                            $('#modal_golongan').val(data.golongan);
                            $('#modal_kode_mk').val(data.kode_mk || '');
                            $('#modal_mata_kuliah').val(data.mata_kuliah);
                            $('#modal_sks').val(data.sks);

                            // Hitung durasi
                            let startHour = parseInt(jamMulai.split(':')[0]);
                            let endHour = parseInt(jamSelesai.split(':')[0]);
                            let durasi = endHour - startHour;

                            $('#durasi_jam').val(durasi);
                            $('#durasi_info').text('Durasi: ' + durasi + ' Jam (' + jamMulai +
                                ' - ' + jamSelesai + ')');

                            // Update pilihan jam mulai
                            updateJamMulaiOptions();

                            $('#jadwalModal').modal('show');
                        } else {
                            alert('Error: ' + (response.message || 'Data tidak ditemukan'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Gagal mengambil data: ' + error);
                    }
                });
            });

            // 3. SUBMIT FORM dengan validasi jam
            $('#jadwalForm').submit(function(e) {
                e.preventDefault();

                // Validasi jam
                if (!validateJam()) {
                    alert('Error: Jam selesai melebihi batas waktu (maksimal 20:00)');
                    return false;
                }

                let formData = $(this).serialize();
                let method = $('#formMethod').val();
                let id = $('#jadwalId').val();

                let url = method === 'PUT' ? '/ruangan/update/' + id : '/ruangan/store';

                $('#saveButton').prop('disabled', true).text('Menyimpan...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData + (method === 'PUT' ? '&_method=PUT' : ''),
                    success: function(response) {
                        $('#saveButton').prop('disabled', false);

                        if (response.success) {
                            alert(response.message);
                            $('#jadwalModal').modal('hide');
                            setTimeout(() => location.reload(), 500);
                        } else {
                            alert('Gagal: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        $('#saveButton').prop('disabled', false);
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMsg = 'Validasi gagal:\n';
                            for (let key in errors) {
                                errorMsg += errors[key][0] + '\n';
                            }
                            alert(errorMsg);
                        } else {
                            alert('Terjadi kesalahan');
                        }
                    }
                });
            });

            // 4. TOMBOL HAPUS - SOLUSI PASTI BEKERJA
            $(document).on('click', '.delete-btn', function() {
                let id = $(this).data('id');
                let jadwalText = $(this).closest('.schedule-info').find('.fw-bold').text().trim();


                if (confirm('Hapus jadwal: ' + jadwalText + '?')) {
                    $.ajax({
                        url: '/ruangan/delete/' + id,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                alert('Berhasil dihapus');
                                location.reload();
                            } else {
                                alert('Gagal: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('Gagal menghapus data');
                        }
                    });
                }
            });

            // Fungsi print table
            window.printTable = function() {
                let printContents = document.getElementById('jadwalTable').outerHTML;
                let originalContents = document.body.innerHTML;

                document.body.innerHTML = `
                <html>
                    <head>
                        <title>Jadwal Ruangan - {{ $selectedDate }}</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 20px; }
                            .table { font-size: 12px; border-collapse: collapse; }
                            .table th, .table td { border: 1px solid #ddd; padding: 5px; }
                            .ruangan-cell { min-height: 80px; vertical-align: top; }
                            .occupied { background-color: #e8f5e9; }
                            .empty { background-color: #f8f9fa; }
                            @media print {
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <h3>Jadwal Ruangan</h3>
                        <p>Tanggal: {{ $selectedDate }}</p>
                        ${printContents}
                        <div class="mt-4 no-print">
                            <button class="btn btn-primary" onclick="window.close()">Tutup</button>
                        </div>
                        <script>
                            window.onload = function() {
                                window.print();
                            }
                        <\/script>
                    </body>
                </html>`;

                window.print();
                document.body.innerHTML = originalContents;
                location.reload();
            }
        });
    </script>
@endsection
