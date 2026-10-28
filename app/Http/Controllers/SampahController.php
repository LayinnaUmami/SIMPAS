<?php

namespace App\Http\Controllers;

use App\Models\Khataman;
use App\Models\Pelanggaran;
use App\Models\Prestasi;
use App\Models\Santri;
use Illuminate\Http\Request;

/**
 * SAMPAH (bagian 36) - soft delete, restore & hapus permanen.
 * Data di Sampah tidak muncul di Riwayat, Laporan, maupun Ranking.
 */
class SampahController extends Controller
{
    private function registry(): array
    {
        $map = [
            'santri' => ['label' => 'Data Santri', 'model' => Santri::class],
            'khataman' => ['label' => 'Riwayat Khataman', 'model' => Khataman::class],
            'prestasi' => ['label' => 'Prestasi', 'model' => Prestasi::class],
            'pelanggaran' => ['label' => 'Pelanggaran', 'model' => Pelanggaran::class],
        ];

        foreach (config('simpas.activities') as $key => $cfg) {
            $map[$key] = ['label' => $cfg['label'], 'model' => $cfg['model']];
        }

        return $map;
    }

    public function index(Request $request)
    {
        $registry = $this->registry();
        $tipe = $request->tipe ?: 'semua';

        $items = collect();

        if ($tipe === 'semua') {
            foreach ($registry as $key => $conf) {
                $query = $conf['model']::onlyTrashed()->latest('deleted_at')->limit(100);
                // Hanya load relasi 'santri' jika model bukan Santri (Santri adalah model utama, tidak punya relasi ke dirinya sendiri)
                if ($key !== 'santri') {
                    $query->with('santri');
                }
                $found = $query->get();
                foreach ($found as $row) {
                    $row->tipe = $key;
                    $row->tipe_label = $conf['label'];
                }
                $items = $items->concat($found);
            }
            $items = $items->sortByDesc('deleted_at')->values();
        } elseif (isset($registry[$tipe])) {
            $query = $registry[$tipe]['model']::onlyTrashed()->latest('deleted_at');
            if ($tipe !== 'santri') {
                $query->with('santri');
            }
            $items = $query->get()
                ->each(function ($row) use ($tipe, $registry) {
                    $row->tipe = $tipe;
                    $row->tipe_label = $registry[$tipe]['label'];
                });
        }

        return view('sampah.index', [
            'items' => $items,
            'registry' => $registry,
            'tipe' => $tipe,
        ]);
    }

    public function restore(string $type, int $id, Request $request)
    {
        $registry = $this->registry();
        abort_unless(isset($registry[$type]), 404);

        $model = $registry[$type]['model']::onlyTrashed()->find($id);
        abort_unless($model, 404);

        $model->restore();

        return redirect()->back()->with('toast', ['type' => 'success', 'message' => 'Data berhasil dipulihkan.']);
    }

    public function permanen(string $type, int $id, Request $request)
    {
        $registry = $this->registry();
        abort_unless(isset($registry[$type]), 404);

        $model = $registry[$type]['model']::onlyTrashed()->find($id);
        abort_unless($model, 404);

        $model->forceDelete();

        return redirect()->back()->with('toast', ['type' => 'success', 'message' => 'Data berhasil dihapus secara permanen.']);
    }

    public function hapusSemua(string $type)
    {
        $registry = $this->registry();
        abort_unless(isset($registry[$type]), 404);

        $modelClass = $registry[$type]['model'];
        $label = $registry[$type]['label'];

        \Log::info('hapusSemua started', ['type' => $type, 'model' => $modelClass]);

        // Gunakan forceDelete untuk hard delete (bypass soft delete)
        $deleted = $modelClass::query()->forceDelete();

        \Log::info('hapusSemua completed', ['deleted' => $deleted]);

        return redirect()->route('sampah.index', ['tipe' => $type])
            ->with('toast', ['type' => 'success', 'message' => "Semua data {$label} berhasil dihapus permanen ({$deleted} record)."]);
    }
}
