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
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'ruangan' => 'required|string|max:50',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'prodi' => 'required|string|max:10',
            'semester' => 'required|integer|min:1|max:8',
            'golongan' => 'required|string|max:1',
            'kode_mk' => 'nullable|string|max:20',
            'mata_kuliah' => 'required|string|max:100',
            'sks' => 'required|integer|min:1|max:8',
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
                $query->whereBetween('jam_mulai', [$request->jam_mulai, $request->jam_selesai])
                    ->orWhereBetween('jam_selesai', [$request->jam_mulai, $request->jam_selesai])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('jam_mulai', '<=', $request->jam_mulai)
                            ->where('jam_selesai', '>=', $request->jam_selesai);
                    });
            })
            ->first();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Slot waktu pada tanggal ' . $request->tanggal . ' sudah terisi oleh ' . $conflict->prodi . ' ' . $conflict->semester . $conflict->golongan
            ], 409);
        }

        // Tentukan hari dari tanggal (hanya untuk referensi, tidak untuk filtering)
        $hari = $this->getHariIndonesia($request->tanggal);

        $jadwal = Jadwal::create([
            'tanggal' => $request->tanggal,
            'hari' => $hari, // Simpan hari hanya sebagai informasi
            'ruangan' => $request->ruangan,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'prodi' => $request->prodi,
            'semester' => $request->semester,
            'golongan' => $request->golongan,
            'kode_mk' => $request->kode_mk,
            'mata_kuliah' => $request->mata_kuliah,
            'sks' => $request->sks,
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

    // Di RuanganController.php
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

        // Format waktu ke HH:MM jika perlu
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

        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'ruangan' => 'required|string|max:50',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'prodi' => 'required|string|max:10',
            'semester' => 'required|integer|min:1|max:8',
            'golongan' => 'required|string|max:1',
            'kode_mk' => 'nullable|string|max:20',
            'mata_kuliah' => 'required|string|max:100',
            'sks' => 'required|integer|min:1|max:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
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
            'kode_mk' => $request->kode_mk,
            'mata_kuliah' => $request->mata_kuliah,
            'sks' => $request->sks,
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
}
