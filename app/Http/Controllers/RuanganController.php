<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jadwal;
use Illuminate\Support\Facades\Validator;

class RuanganController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('tanggal', date('Y-m-d'));
        $kelas = $request->input('kelas');

        // 1. GET DATA BERDASARKAN TANGGAL
        $query = Jadwal::where('tanggal', $selectedDate)
            ->where('is_template', false) // Hanya jadwal riil
            ->orderBy('ruangan')
            ->orderBy('jam_mulai');

        // FILTER KELAS JIKA ADA
        if ($kelas) {
            if (preg_match('/^([A-Z]+)\s*(\d+)([A-Z])$/i', $kelas, $matches)) {
                $prodi = strtoupper($matches[1]);
                $semester = intval($matches[2]);
                $golongan = strtoupper($matches[3]);

                $query->where('prodi', $prodi)
                    ->where('semester', $semester)
                    ->where('golongan', $golongan);
            }
        }

        $jadwals = $query->get()->unique(function ($item) {
            return $item->ruangan . '|' . $item->jam_mulai . '|' . $item->jam_selesai;
        });

        // 2. GET ROOMS
        $ruangansFromData = $jadwals->pluck('ruangan')->unique()->sort()->values()->toArray();
        $defaultRuangan = ['3.1', '3.2', '3.3', '3.4', '3.5', '3.6', '3.7', '3.8', '3.9', '3.10', '3.11'];
        $ruangans = array_unique(array_merge($ruangansFromData, $defaultRuangan));
        sort($ruangans);

        // 3. TIME SLOTS
        $timeSlots = [];
        for ($hour = 7; $hour <= 17; $hour++) {
            $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
        }

        // 4. INISIALISASI GRID
        $scheduleGrid = [];

        foreach ($ruangans as $ruangan) {
            foreach ($timeSlots as $timeSlot) {
                $scheduleGrid[$ruangan][$timeSlot] = null;
            }
        }

        // 5. FILL GRID
        foreach ($jadwals as $jadwal) {
            $ruangan = $jadwal->ruangan;

            if (!array_key_exists($ruangan, $scheduleGrid)) {
                continue;
            }

            $jamMulai = str_replace('.', ':', $jadwal->jam_mulai);
            $jamSelesai = str_replace('.', ':', $jadwal->jam_selesai);

            $startHour = (int) substr($jamMulai, 0, 2);
            $endHour = (int) substr($jamSelesai, 0, 2);

            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $timeSlot = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';

                if (array_key_exists($timeSlot, $scheduleGrid[$ruangan])) {
                    $scheduleGrid[$ruangan][$timeSlot] = $jadwal;
                }
            }
        }

        // 6. AMBIL DAFTAR KELAS UNTUK FILTER (SEMUA DATA)
        $allKelas = Jadwal::select('prodi', 'semester', 'golongan')
            ->distinct()
            ->orderBy('prodi')
            ->orderBy('semester')
            ->orderBy('golongan')
            ->get()
            ->map(function ($item) {
                return $item->prodi . ' ' . $item->semester . $item->golongan;
            })
            ->toArray();

        // 7. RETURN VIEW
        return view('ruangan.index', [
            'selectedDate' => $selectedDate,
            'kelas' => $kelas,
            'allKelas' => $allKelas,
            'gedung' => '',
            'jadwals' => $jadwals,
            'ruangans' => $ruangans,
            'timeSlots' => $timeSlots,
            'scheduleGrid' => $scheduleGrid
        ]);
    }

    public function store(Request $request)
    {
        // VALIDASI BARU TANPA kode_mk dan sks
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'ruangan' => 'required|string|max:50',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'prodi' => 'required|string|max:10',
            'semester' => 'required|integer|min:1|max:8',
            'golongan' => 'required|string|max:1',
            'mata_kuliah' => 'required|string|max:100',
            'dosen_pengampu' => 'required|string|max:255',
            'teknisi' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // CEK KONFLIK JADWAL PADA TANGGAL YANG SAMA
        $conflict = Jadwal::where('tanggal', $request->tanggal)
            ->where('ruangan', $request->ruangan)
            ->where(function ($query) use ($request) {
                // Kondisi 1: Jadwal baru dimulai DURING jadwal yang ada
                $query->where('jam_mulai', '<', $request->jam_selesai)
                    ->where('jam_selesai', '>', $request->jam_mulai);
            })
            ->first();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Slot waktu pada tanggal ' . $request->tanggal . ' sudah terisi oleh ' . $conflict->prodi . ' ' . $conflict->semester . $conflict->golongan
            ], 409);
        }

        // AMBIL DATA SKS DAN KODE MK DARI TEMPLATE BERDASARKAN MATA KULIAH
        $templateData = Jadwal::where('mata_kuliah', $request->mata_kuliah)
            ->where('prodi', $request->prodi)
            ->where('semester', $request->semester)
            ->where('golongan', $request->golongan)
            ->where('is_template', true)
            ->first();

        // Tentukan hari dari tanggal
        $hari = $this->getHariIndonesia($request->tanggal);

        $jadwal = Jadwal::create([
            'tanggal' => $request->tanggal,
            'hari' => $hari,
            'ruangan' => $request->ruangan,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'prodi' => $request->prodi,
            'semester' => $request->semester,
            'golongan' => $request->golongan,
            'kode_mk' => $templateData->kode_mk ?? null,
            'mata_kuliah' => $request->mata_kuliah,
            'sks' => $templateData->sks ?? 2, // Default 2 jika tidak ditemukan
            'dosen_koordinator' => $templateData->dosen_koordinator ?? null,
            'team_teaching' => $templateData->team_teaching ?? null,
            'dosen_pengampu' => $request->dosen_pengampu,
            'teknisi' => $request->teknisi,
            'is_template' => false,
            'tahun_akademik' => date('Y') . '/' . (date('Y') + 1),
            'semester_akademik' => 'Genap',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil ditambahkan untuk tanggal ' . $request->tanggal,
            'data' => $jadwal
        ]);
    }

    public function forceDelete($id)
    {
        $jadwal = Jadwal::find($id);

        if (!$jadwal) {
            return redirect()->route('ruangan.index')
                ->with('error', 'Data tidak ditemukan');
        }

        $jadwal->delete();

        return redirect()->route('ruangan.index')
            ->with('success', 'Jadwal berhasil dihapus');
    }

    public function edit($id)
    {
        $jadwal = Jadwal::find($id);

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        // Format tanggal ke YYYY-MM-DD untuk input type="date"
        $tanggal = $jadwal->tanggal;
        if ($tanggal) {
            // Jika tanggal dalam format lain, konversi
            if (strpos($tanggal, '/') !== false) {
                // Format: DD/MM/YYYY
                $parts = explode('/', $tanggal);
                if (count($parts) === 3) {
                    $tanggal = $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                }
            } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $tanggal)) {
                // Format: DD-MM-YYYY
                $parts = explode('-', $tanggal);
                $tanggal = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }

        $jadwal->tanggal = $tanggal;

        // Format waktu ke HH:MM
        $jadwal->jam_mulai = substr($jadwal->jam_mulai, 0, 5);
        $jadwal->jam_selesai = substr($jadwal->jam_selesai, 0, 5);

        return response()->json([
            'success' => true,
            'data' => $jadwal
        ]);
    }

    public function update(Request $request, $id)
    {
        $jadwal = Jadwal::find($id);

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        // VALIDASI BARU TANPA kode_mk dan sks
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'ruangan' => 'required|string|max:50',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'prodi' => 'required|string|max:10',
            'semester' => 'required|integer|min:1|max:8',
            'golongan' => 'required|string|max:1',
            'mata_kuliah' => 'required|string|max:100',
            'dosen_pengampu' => 'required|string|max:255',
            'teknisi' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // AMBIL DATA SKS DAN KODE MK DARI TEMPLATE JIKA MATA KULIAH BERUBAH
        $kode_mk = $jadwal->kode_mk;
        $sks = $jadwal->sks;

        if (
            $jadwal->mata_kuliah != $request->mata_kuliah ||
            $jadwal->prodi != $request->prodi ||
            $jadwal->semester != $request->semester ||
            $jadwal->golongan != $request->golongan
        ) {

            $templateData = Jadwal::where('mata_kuliah', $request->mata_kuliah)
                ->where('prodi', $request->prodi)
                ->where('semester', $request->semester)
                ->where('golongan', $request->golongan)
                ->where('is_template', true)
                ->first();

            if ($templateData) {
                $kode_mk = $templateData->kode_mk;
                $sks = $templateData->sks;
            }
        }

        // Update hari jika tanggal berubah
        if ($jadwal->tanggal != $request->tanggal) {
            $hari = $this->getHariIndonesia($request->tanggal);
            $jadwal->hari = $hari;
        }

        $jadwal->update([
            'tanggal' => $request->tanggal,
            'ruangan' => $request->ruangan,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'prodi' => $request->prodi,
            'semester' => $request->semester,
            'golongan' => $request->golongan,
            'kode_mk' => $kode_mk,
            'mata_kuliah' => $request->mata_kuliah,
            'sks' => $sks,
            'dosen_pengampu' => $request->dosen_pengampu,
            'teknisi' => $request->teknisi,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil diperbarui',
            'data' => $jadwal
        ]);
    }

    public function destroy($id)
    {
        try {
            $jadwal = Jadwal::find($id);

            if (!$jadwal) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            // Debug log
            \Log::info('Menghapus jadwal:', [
                'id' => $jadwal->id,
                'tanggal' => $jadwal->tanggal,
                'prodi' => $jadwal->prodi,
                'mata_kuliah' => $jadwal->mata_kuliah,
                'is_template' => $jadwal->is_template ?? 'null'
            ]);

            // Hapus langsung tanpa kondisi
            $jadwal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error hapus jadwal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        return view('ruangan.create');
    }

    private function getHariIndonesia($date)
    {
        $hariInggris = date('l', strtotime($date));

        $hariMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];

        return $hariMap[$hariInggris] ?? 'Senin';
    }

    public function getMataKuliah(Request $request)
    {
        $prodi = $request->input('prodi');
        $semester = $request->input('semester');
        $golongan = $request->input('golongan');

        if (!$prodi || !$semester || !$golongan) {
            return response()->json([]);
        }

        $mataKuliah = Jadwal::where('prodi', $prodi)
            ->where('semester', $semester)
            ->where('golongan', $golongan)
            ->where('is_template', true)
            ->select('mata_kuliah', 'kode_mk', 'sks', 'dosen_koordinator', 'team_teaching', 'teknisi')
            ->distinct()
            ->orderBy('mata_kuliah')
            ->get();

        return response()->json($mataKuliah);
    }

    public function getDosenPengampu(Request $request)
    {
        $mataKuliah = $request->input('mata_kuliah');
        $prodi = $request->input('prodi');
        $semester = $request->input('semester');
        $golongan = $request->input('golongan');

        if (!$mataKuliah || !$prodi || !$semester || !$golongan) {
            return response()->json([]);
        }

        $jadwal = Jadwal::where('mata_kuliah', $mataKuliah)
            ->where('prodi', $prodi)
            ->where('semester', $semester)
            ->where('golongan', $golongan)
            ->where('is_template', true)
            ->first();

        if (!$jadwal) {
            return response()->json([]);
        }

        $dosenPengampu = [];

        // Tambahkan dosen koordinator
        if ($jadwal->dosen_koordinator) {
            $dosenPengampu[] = $jadwal->dosen_koordinator;
        }

        // Tambahkan team teaching
        if ($jadwal->team_teaching) {
            $team = json_decode($jadwal->team_teaching, true);
            if (is_array($team)) {
                $dosenPengampu = array_merge($dosenPengampu, $team);
            }
        }

        return response()->json(array_unique($dosenPengampu));
    }

    public function getTeknisi(Request $request)
    {
        $mataKuliah = $request->input('mata_kuliah');
        $prodi = $request->input('prodi');
        $semester = $request->input('semester');
        $golongan = $request->input('golongan');

        if (!$mataKuliah || !$prodi || !$semester || !$golongan) {
            return response()->json(['teknisi' => null]);
        }

        $jadwal = Jadwal::where('mata_kuliah', $mataKuliah)
            ->where('prodi', $prodi)
            ->where('semester', $semester)
            ->where('golongan', $golongan)
            ->where('is_template', true)
            ->first();

        return response()->json(['teknisi' => $jadwal ? $jadwal->teknisi : null]);
    }
}
