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

        .action-buttons .detail-btn {
            background-color: #4a90e2;
            color: white;
            border: none;
        }

        .action-buttons .detail-btn:hover {
            background-color: #3a7bc8;
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

        /* Modal detail info */
        .detail-info {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-info:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            min-width: 120px;
            display: inline-block;
        }

        .detail-value {
            color: #333;
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
                                    <label for="dosen_pengampu" class="form-label">
                                        <i class="bi bi-person"></i> Filter Dosen Pengampu
                                    </label>
                                    <select class="form-select" id="dosen_pengampu" name="dosen_pengampu">
                                        <option value="">Semua Dosen</option>
                                        @foreach ($allDosen ?? [] as $dosen)
                                            <option value="{{ $dosen }}"
                                                {{ request('dosen_pengampu') == $dosen ? 'selected' : '' }}>
                                                {{ $dosen }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="teknisi" class="form-label">
                                        <i class="bi bi-tools"></i> Filter Teknisi
                                    </label>
                                    <select class="form-select" id="teknisi" name="teknisi">
                                        <option value="">Semua Teknisi</option>
                                        @foreach ($allTeknisi ?? [] as $tech)
                                            <option value="{{ $tech }}"
                                                {{ request('teknisi') == $tech ? 'selected' : '' }}>
                                                {{ $tech }}
                                            </option>
                                        @endforeach
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
                            @if (request('kelas'))
                                | Kelas: <strong>{{ request('kelas') }}</strong>
                            @endif
                            @if (request('dosen_pengampu'))
                                | Dosen: <strong>{{ request('dosen_pengampu') }}</strong>
                            @endif
                            @if (request('teknisi'))
                                | Teknisi: <strong>{{ request('teknisi') }}</strong>
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
                                                    title="{{ $jadwal
                                                        ? $jadwal->prodi .
                                                            ' ' .
                                                            $jadwal->semester .
                                                            $jadwal->golongan .
                                                            ' - ' .
                                                            $jadwal->mata_kuliah .
                                                            (isset($jadwal->dosen_pengampu) ? '\nDosen: ' . $jadwal->dosen_pengampu : '') .
                                                            (isset($jadwal->teknisi) ? '\nTeknisi: ' . $jadwal->teknisi : '')
                                                        : 'Kosong' }}">
                                                    @if ($jadwal)
                                                        <div class="schedule-info detail-btn" data-id="{{ $jadwal->id }}"
                                                            style="line-height: 1.1;">
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

                                                            {{-- TAMPILKAN DOSEN PENGAMPU --}}
                                                            @if ($jadwal->dosen_pengampu)
                                                                <small class="d-block text-secondary"
                                                                    style="font-size: 0.6rem;">
                                                                    <i class="bi bi-person"></i>
                                                                    {{ Str::limit($jadwal->dosen_pengampu, 25) }}
                                                                </small>
                                                            @endif

                                                            {{-- TAMPILKAN TEKNISI --}}
                                                            @if ($jadwal->teknisi)
                                                                <small class="d-block text-info" style="font-size: 0.6rem;">
                                                                    <i class="bi bi-tools"></i>
                                                                    {{ Str::limit($jadwal->teknisi, 20) }}
                                                                </small>
                                                            @endif

                                                            {{-- ACTION BUTTONS --}}
                                                            <div class="mt-1 action-buttons">

                                                                @auth
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
                                                                @endauth
                                                            </div>
                                                        </div>
                                                    @else
                                                        {{-- CELL KOSONG --}}
                                                        <div class="empty-cell">
                                                            <span class="text-muted">-</span>
                                                            @auth
                                                                <div class="mt-1">
                                                                    <button class="btn btn-sm btn-outline-success add-btn"
                                                                        data-ruangan="{{ $ruangan }}"
                                                                        data-jam="{{ $timeSlot }}"
                                                                        data-bs-toggle="tooltip" title="Tambah jadwal">
                                                                        <i class="bi bi-plus"></i> Tambah
                                                                    </button>
                                                                </div>
                                                            @endauth
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

    {{-- MODAL TAMBAH/EDIT JADWAL (VERSI BARU) --}}
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

                        {{-- MATA KULIAH DROPDOWN --}}
                        <div class="mb-3">
                            <label class="form-label">Mata Kuliah <span class="text-danger">*</span></label>
                            <select class="form-select" name="mata_kuliah" id="modal_mata_kuliah" required>
                                <option value="">Pilih Prodi, Semester, dan Golongan terlebih dahulu</option>
                            </select>
                            <div class="form-text">Pilih mata kuliah berdasarkan prodi, semester, dan golongan</div>
                        </div>

                        {{-- DOSEN PENGAMPU DROPDOWN --}}
                        <div class="mb-3">
                            <label class="form-label">Dosen Pengampu <span class="text-danger">*</span></label>
                            <select class="form-select" name="dosen_pengampu" id="modal_dosen_pengampu" required>
                                <option value="">Pilih Mata Kuliah terlebih dahulu</option>
                            </select>
                            <div class="form-text">Dosen pengampu (koordinator + team teaching)</div>
                        </div>

                        {{-- TEKNISI DROPDOWN --}}
                        <div class="mb-3">
                            <label class="form-label">Teknisi</label>
                            <select class="form-select" name="teknisi" id="modal_teknisi">
                                <option value="">Pilih Mata Kuliah terlebih dahulu</option>
                            </select>
                            <div class="form-text">Teknisi untuk mata kuliah yang dipilih</div>
                        </div>

                        {{-- HIDDEN FIELDS UNTUK DATA LAMA --}}
                        <input type="hidden" name="kode_mk" id="modal_kode_mk">
                        <input type="hidden" name="sks" id="modal_sks">
                        <input type="hidden" name="dosen_koordinator" id="modal_dosen_koordinator">
                        <input type="hidden" name="team_teaching" id="modal_team_teaching">
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

    {{-- MODAL DETAIL JADWAL --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="detailModalLabel">
                        <i class="bi bi-info-circle"></i> Detail Jadwal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="detail-info">
                        <span class="detail-label">Tanggal:</span>
                        <span class="detail-value" id="detail_tanggal"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Hari:</span>
                        <span class="detail-value" id="detail_hari"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Ruangan:</span>
                        <span class="detail-value" id="detail_ruangan"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Jam:</span>
                        <span class="detail-value" id="detail_jam"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Kelas:</span>
                        <span class="detail-value" id="detail_kelas"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Mata Kuliah:</span>
                        <span class="detail-value" id="detail_mata_kuliah"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Kode MK:</span>
                        <span class="detail-value" id="detail_kode_mk"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">SKS:</span>
                        <span class="detail-value" id="detail_sks"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Dosen Koordinator:</span>
                        <span class="detail-value" id="detail_dosen_koordinator"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Dosen Pengampu:</span>
                        <span class="detail-value" id="detail_dosen_pengampu"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Team Teaching:</span>
                        <span class="detail-value" id="detail_team_teaching"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Teknisi:</span>
                        <span class="detail-value" id="detail_teknisi"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Semester Akademik:</span>
                        <span class="detail-value" id="detail_semester_akademik"></span>
                    </div>
                    <div class="detail-info">
                        <span class="detail-label">Tahun Akademik:</span>
                        <span class="detail-value" id="detail_tahun_akademik"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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

        function loadMataKuliah() {
            let prodi = $('#modal_prodi').val();
            let semester = $('#modal_semester').val();
            let golongan = $('#modal_golongan').val();

            if (!prodi || !semester || !golongan) {
                $('#modal_mata_kuliah').html(
                    '<option value="">Pilih Prodi, Semester, dan Golongan terlebih dahulu</option>');
                $('#modal_dosen_pengampu').html('<option value="">Pilih Mata Kuliah terlebih dahulu</option>');
                $('#modal_teknisi').html('<option value="">Pilih Mata Kuliah terlebih dahulu</option>');
                return;
            }

            $.ajax({
                url: '/ruangan/get-mata-kuliah',
                type: 'GET',
                data: {
                    prodi: prodi,
                    semester: semester,
                    golongan: golongan
                },
                success: function(response) {
                    let options = '<option value="">Pilih Mata Kuliah</option>';

                    if (response.length > 0) {
                        response.forEach(function(item) {
                            options +=
                                `<option value="${item.mata_kuliah}" 
                                 data-kode="${item.kode_mk || ''}"
                                 data-sks="${item.sks || 2}"
                                 data-dosen-koordinator="${item.dosen_koordinator || ''}"
                                 data-team-teaching="${item.team_teaching || '[]'}">${item.mata_kuliah}</option>`;
                        });
                    } else {
                        options += '<option value="">Tidak ada mata kuliah untuk kelas ini</option>';
                    }

                    $('#modal_mata_kuliah').html(options);
                    $('#modal_dosen_pengampu').html(
                        '<option value="">Pilih Mata Kuliah terlebih dahulu</option>');
                    $('#modal_teknisi').html('<option value="">Pilih Mata Kuliah terlebih dahulu</option>');
                },
                error: function(xhr, status, error) {
                    console.error('Gagal memuat mata kuliah:', error);
                    $('#modal_mata_kuliah').html('<option value="">Error loading data</option>');
                }
            });
        }

        // Fungsi untuk load dosen pengampu berdasarkan mata kuliah
        function loadDosenPengampu() {
            let mataKuliah = $('#modal_mata_kuliah').val();
            let prodi = $('#modal_prodi').val();
            let semester = $('#modal_semester').val();
            let golongan = $('#modal_golongan').val();

            if (!mataKuliah || !prodi || !semester || !golongan) {
                $('#modal_dosen_pengampu').html('<option value="">Pilih Mata Kuliah terlebih dahulu</option>');
                return;
            }

            $.ajax({
                url: '/ruangan/get-dosen-pengampu',
                type: 'GET',
                data: {
                    mata_kuliah: mataKuliah,
                    prodi: prodi,
                    semester: semester,
                    golongan: golongan
                },
                success: function(response) {
                    let options = '<option value="">Pilih Dosen Pengampu</option>';

                    if (response.length > 0) {
                        response.forEach(function(dosen) {
                            options += `<option value="${dosen}">${dosen}</option>`;
                        });
                    } else {
                        options += '<option value="">Tidak ada dosen pengampu</option>';
                    }

                    $('#modal_dosen_pengampu').html(options);
                },
                error: function(xhr, status, error) {
                    console.error('Gagal memuat dosen pengampu:', error);
                    $('#modal_dosen_pengampu').html('<option value="">Error loading data</option>');
                }
            });
        }

        // Fungsi untuk load teknisi berdasarkan mata kuliah
        function loadTeknisi() {
            let mataKuliah = $('#modal_mata_kuliah').val();
            let prodi = $('#modal_prodi').val();
            let semester = $('#modal_semester').val();
            let golongan = $('#modal_golongan').val();

            if (!mataKuliah || !prodi || !semester || !golongan) {
                $('#modal_teknisi').html('<option value="">Pilih Mata Kuliah terlebih dahulu</option>');
                return;
            }

            $.ajax({
                url: '/ruangan/get-teknisi',
                type: 'GET',
                data: {
                    mata_kuliah: mataKuliah,
                    prodi: prodi,
                    semester: semester,
                    golongan: golongan
                },
                success: function(response) {
                    let options = '<option value="">Pilih Teknisi</option>';

                    if (response.teknisi) {
                        options += `<option value="${response.teknisi}" selected>${response.teknisi}</option>`;
                    } else {
                        options += '<option value="">Tidak ada teknisi</option>';
                    }

                    $('#modal_teknisi').html(options);
                },
                error: function(xhr, status, error) {
                    console.error('Gagal memuat teknisi:', error);
                    $('#modal_teknisi').html('<option value="">Error loading data</option>');
                }
            });
        }

        // 5. TOMBOL DETAIL - Lihat informasi lengkap
        $(document).on('click', '.detail-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            let id = $(this).data('id');

            $.ajax({
                url: '/ruangan/detail/' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        let data = response.data;

                        // Format jam
                        let jamMulai = data.jam_mulai;
                        let jamSelesai = data.jam_selesai;
                        if (jamMulai && jamMulai.length > 5) {
                            jamMulai = jamMulai.substring(0, 5);
                        }
                        if (jamSelesai && jamSelesai.length > 5) {
                            jamSelesai = jamSelesai.substring(0, 5);
                        }

                        // Isi data ke modal detail
                        $('#detail_tanggal').text(data.tanggal || '-');
                        $('#detail_hari').text(data.hari || '-');
                        $('#detail_ruangan').text(data.ruangan || '-');
                        $('#detail_jam').text((jamMulai || '-') + ' - ' + (jamSelesai || '-'));
                        $('#detail_kelas').text((data.prodi || '') + ' ' + (data.semester || '') + (data
                            .golongan || ''));
                        $('#detail_mata_kuliah').text(data.mata_kuliah || '-');
                        $('#detail_kode_mk').text(data.kode_mk || '-');
                        $('#detail_sks').text(data.sks || '-');
                        $('#detail_dosen_koordinator').text(data.dosen_koordinator || '-');
                        $('#detail_dosen_pengampu').text(data.dosen_pengampu || '-');

                        // Handle team teaching (bisa array atau string)
                        let teamTeaching = data.team_teaching || '-';
                        if (teamTeaching && typeof teamTeaching === 'string') {
                            try {
                                let parsed = JSON.parse(teamTeaching);
                                if (Array.isArray(parsed) && parsed.length > 0) {
                                    teamTeaching = parsed.join(', ');
                                }
                            } catch (e) {
                                // Biarkan sebagai string
                            }
                        }
                        $('#detail_team_teaching').text(teamTeaching);

                        $('#detail_teknisi').text(data.teknisi || '-');
                        $('#detail_semester_akademik').text(data.semester_akademik || '-');
                        $('#detail_tahun_akademik').text(data.tahun_akademik || '-');

                        $('#detailModal').modal('show');
                    } else {
                        alert('Error: ' + (response.message || 'Data tidak ditemukan'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Gagal mengambil data detail jadwal');
                }
            });
        });

        $(document).ready(function() {
            // Pastikan CSRF token tersedia
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Inisialisasi tooltip
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Event untuk jam mulai dan durasi
            $('#modal_jam_mulai').change(function() {
                calculateJamSelesai();
            });

            $('#durasi_jam').change(function() {
                updateJamMulaiOptions();
                calculateJamSelesai();
            });

            // Event untuk prodi, semester, golongan (load mata kuliah)
            $('#modal_prodi, #modal_semester, #modal_golongan').change(function() {
                loadMataKuliah();
            });

            // Event untuk mata kuliah (load dosen pengampu & teknisi)
            $('#modal_mata_kuliah').change(function() {
                let selectedOption = $(this).find('option:selected');

                // Set hidden fields
                $('#modal_kode_mk').val(selectedOption.data('kode') || '');
                $('#modal_sks').val(selectedOption.data('sks') || 2);
                $('#modal_dosen_koordinator').val(selectedOption.data('dosen-koordinator') || '');
                $('#modal_team_teaching').val(selectedOption.data('team-teaching') || '[]');

                // Load data terkait
                loadDosenPengampu();
                loadTeknisi();
            });

            // 1. TOMBOL TAMBAH
            $(document).on('click', '.add-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let ruangan = $(this).data('ruangan');
                let jamSlot = $(this).data('jam');

                // Reset form
                $('#jadwalForm')[0].reset();
                $('#formMethod').val('POST');
                $('#jadwalId').val('');
                $('#jadwalModalLabel').text('Tambah Jadwal');
                $('#saveButton').text('Simpan').prop('disabled', false);
                $('#jam_peringatan').hide();

                // Reset hidden fields
                $('#modal_kode_mk').val('');
                $('#modal_sks').val('');
                $('#modal_dosen_koordinator').val('');
                $('#modal_team_teaching').val('');

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

                // Reset dropdowns
                $('#modal_mata_kuliah').html(
                    '<option value="">Pilih Prodi, Semester, dan Golongan terlebih dahulu</option>');
                $('#modal_dosen_pengampu').html(
                    '<option value="">Pilih Mata Kuliah terlebih dahulu</option>');
                $('#modal_teknisi').html('<option value="">Pilih Mata Kuliah terlebih dahulu</option>');

                $('#jadwalModal').modal('show');
            });

            // 2. TOMBOL EDIT
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
                            let data = response.data;

                            // Reset form
                            $('#jadwalForm')[0].reset();
                            $('#formMethod').val('PUT');
                            $('#jadwalId').val(data.id);
                            $('#jadwalModalLabel').text('Edit Jadwal');
                            $('#saveButton').text('Update').prop('disabled', false);
                            $('#jam_peringatan').hide();

                            // FORMAT TANGGAL UNTUK INPUT TYPE="DATE"
                            // Pastikan tanggal dalam format YYYY-MM-DD
                            let tanggal = data.tanggal;
                            if (tanggal) {
                                // Jika tanggal dalam format lain, konversi ke YYYY-MM-DD
                                if (tanggal.includes('/')) {
                                    // Format: DD/MM/YYYY -> YYYY-MM-DD
                                    let parts = tanggal.split('/');
                                    if (parts.length === 3) {
                                        tanggal = parts[2] + '-' + parts[1].padStart(2, '0') +
                                            '-' + parts[0].padStart(2, '0');
                                    }
                                } else if (tanggal.includes('-') && tanggal.split('-')[0]
                                    .length === 2) {
                                    // Format: DD-MM-YYYY -> YYYY-MM-DD
                                    let parts = tanggal.split('-');
                                    if (parts.length === 3) {
                                        tanggal = parts[2] + '-' + parts[1].padStart(2, '0') +
                                            '-' + parts[0].padStart(2, '0');
                                    }
                                }
                                // Jika sudah YYYY-MM-DD, langsung pakai
                                $('#modal_tanggal').val(tanggal);
                            } else {
                                // Default ke tanggal sekarang
                                $('#modal_tanggal').val('{{ date('Y-m-d') }}');
                            }

                            // Isi form lainnya
                            $('#modal_ruangan').val(data.ruangan);

                            // Format jam
                            let jamMulai = data.jam_mulai;
                            let jamSelesai = data.jam_selesai;

                            // Pastikan format jam HH:MM
                            if (jamMulai && jamMulai.length > 5) {
                                jamMulai = jamMulai.substring(0, 5);
                            }
                            if (jamSelesai && jamSelesai.length > 5) {
                                jamSelesai = jamSelesai.substring(0, 5);
                            }

                            $('#modal_jam_mulai').val(jamMulai);
                            $('#modal_jam_selesai').val(jamSelesai);
                            $('#modal_prodi').val(data.prodi);
                            $('#modal_semester').val(data.semester);
                            $('#modal_golongan').val(data.golongan);

                            // Hitung durasi
                            if (jamMulai && jamSelesai) {
                                let startHour = parseInt(jamMulai.split(':')[0]);
                                let endHour = parseInt(jamSelesai.split(':')[0]);
                                let durasi = endHour - startHour;

                                if (durasi > 0) {
                                    $('#durasi_jam').val(durasi);
                                    $('#durasi_info').text('Durasi: ' + durasi + ' Jam (' +
                                        jamMulai + ' - ' + jamSelesai + ')');
                                }
                            }

                            // Set hidden fields
                            $('#modal_kode_mk').val(data.kode_mk || '');
                            $('#modal_sks').val(data.sks || 2);
                            $('#modal_dosen_koordinator').val(data.dosen_koordinator || '');
                            $('#modal_team_teaching').val(data.team_teaching || '[]');

                            // Update pilihan jam mulai berdasarkan durasi
                            updateJamMulaiOptions();

                            // Load mata kuliah dan set nilai yang dipilih
                            loadMataKuliah();

                            // Tunggu sebentar lalu set mata kuliah dan data lainnya
                            setTimeout(function() {
                                if (data.mata_kuliah) {
                                    $('#modal_mata_kuliah').val(data.mata_kuliah);

                                    // Trigger change untuk load dosen dan teknisi
                                    $('#modal_mata_kuliah').trigger('change');

                                    // Set dosen pengampu dan teknisi setelah load
                                    setTimeout(function() {
                                        if (data.dosen_pengampu) {
                                            $('#modal_dosen_pengampu').val(data
                                                .dosen_pengampu);
                                        }
                                        if (data.teknisi) {
                                            $('#modal_teknisi').val(data
                                                .teknisi);
                                        }
                                    }, 500);
                                }
                            }, 500);

                            $('#jadwalModal').modal('show');
                        } else {
                            alert('Error: ' + (response.message || 'Data tidak ditemukan'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('Gagal mengambil data jadwal');
                    }
                });
            });

            // 3. SUBMIT FORM
            $('#jadwalForm').submit(function(e) {
                e.preventDefault();

                // Validasi jam
                if (!validateJam()) {
                    alert('Error: Jam selesai melebihi batas waktu (maksimal 20:00)');
                    return false;
                }

                // Validasi form
                if (!$('#modal_mata_kuliah').val()) {
                    alert('Error: Pilih mata kuliah terlebih dahulu');
                    return false;
                }

                if (!$('#modal_dosen_pengampu').val()) {
                    alert('Error: Pilih dosen pengampu terlebih dahulu');
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
                    data: formData,
                    success: function(response) {
                        $('#saveButton').prop('disabled', false).text(method === 'PUT' ?
                            'Update' : 'Simpan');

                        if (response.success) {
                            alert(response.message);
                            $('#jadwalModal').modal('hide');
                            setTimeout(() => location.reload(), 500);
                        } else {
                            alert('Gagal: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        $('#saveButton').prop('disabled', false).text(method === 'PUT' ?
                            'Update' : 'Simpan');

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMsg = 'Validasi gagal:\n';
                            for (let key in errors) {
                                errorMsg += errors[key][0] + '\n';
                            }
                            alert(errorMsg);
                        } else if (xhr.status === 409) {
                            alert(xhr.responseJSON.message);
                        } else {
                            alert('Terjadi kesalahan server');
                        }
                    }
                });
            });

            // 4. TOMBOL HAPUS
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let id = $(this).data('id');
                let jadwalText = $(this).closest('.schedule-info').find('.fw-bold').text().trim();

                if (confirm('Yakin menghapus jadwal: ' + jadwalText + '?')) {
                    $.ajax({
                        url: '/ruangan/delete/' + id,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                alert('Jadwal berhasil dihapus');
                                location.reload();
                            } else {
                                alert('Gagal: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
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
