<?php

namespace App\Http\Controllers;

use App\Models\Khataman;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SantriController extends Controller
{
    public function index(Request $request)
    {
        $user = request()->user();

        if ($user?->isWali()) {
            return redirect()->route('wali.santri');
        }

        $query = Santri::query()->cari($request->q);

        // Santri/wali hanya melihat datanya sendiri (bagian 3)
        if ($user->isSantri()) {
            $query->where('id', $user->santri_id);
        }

        if ($request->filled('status')) {
            $query->where('status_aktif', $request->status);
        }
        if ($request->filled('jenis_kelamin')) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }
        if ($request->filled('kamar')) {
            $query->where('kamar', $request->kamar);
        }

        // Sorting
        $sort = $request->get('sort', 'abjad');
        switch ($sort) {
            case 'nis':
                $query->orderBy('nis');
                break;
            case 'terbaru':
                $query->orderByDesc('created_at');
                break;
            case 'terlama':
                $query->orderBy('created_at');
                break;
            case 'abjad':
            default:
                $query->orderBy('nama');
                break;
        }

        $santris = $query->paginate(12)->withQueryString();

        return view('santri.index', [
            'santris' => $santris,
            'kamar' => Santri::aktif()->whereNotNull('kamar')->distinct()->pluck('kamar')->sort()->values(),
            'sort' => $sort,
        ]);
    }

    public function create()
    {
        return view('santri.form', [
            'santri' => new Santri,
            'waliOptions' => $this->waliOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validate($request);
        $waliId = $data['wali_id'] ?? null;
        unset($data['wali_id'], $data['wali_username'], $data['wali_password']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('santri', 'public');
        }

        $santri = DB::transaction(function () use ($data, $request, $waliId) {
            $santri = Santri::create($data);
            $wali = $waliId
                ? User::where('role', User::ROLE_WALI)->findOrFail($waliId)
                : $this->buatAkunWali($request, $santri);

            $wali->tautkanSantri($santri->id, 'Orang Tua');

            return $santri;
        });

        return redirect()->route('santri.show', $santri)
            ->with('toast', ['type' => 'success', 'message' => 'Data santri berhasil disimpan.']);
    }

    public function show(Santri $santri)
    {
        $this->authorizeSantri($santri);

        $santri->load('khatamans');

        return view('santri.show', ['santri' => $santri]);
    }

    public function edit(Santri $santri)
    {
        return view('santri.form', [
            'santri' => $santri,
            'waliOptions' => $this->waliOptions(),
        ]);
    }

    public function update(Request $request, Santri $santri)
    {
        $data = $this->validate($request);
        $waliId = $data['wali_id'] ?? null;
        unset($data['wali_id'], $data['wali_username'], $data['wali_password']);

        if ($request->hasFile('foto')) {
            // Delete old photo if exists
            if ($santri->foto) {
                Storage::disk('public')->delete($santri->foto);
            }
            $data['foto'] = $request->file('foto')->store('santri', 'public');
        }

        DB::transaction(function () use ($data, $santri, $waliId) {
            $santri->update($data);

            $waliSebelumnya = $santri->wali;
            if ($waliId) {
                $waliBaru = User::where('role', User::ROLE_WALI)->findOrFail($waliId);
                $santri->wali_id = $waliBaru->id;
                $santri->save();
                $waliBaru->refreshAnakUtama();
            } else {
                $santri->wali_id = null;
                $santri->save();
            }

            if ($waliSebelumnya && (int) $waliSebelumnya->id !== (int) $waliId) {
                $waliSebelumnya->refreshAnakUtama();
            }
        });

        return redirect()->route('santri.show', $santri)
            ->with('toast', ['type' => 'success', 'message' => 'Data santri berhasil diperbarui.']);
    }

    public function destroy(Santri $santri, Request $request)
    {
        $santri->delete(); // soft delete -> Sampah (bagian 36)

        return redirect()->back()->with('toast', ['type' => 'success', 'message' => 'Data berhasil dipindahkan ke Sampah.']);
    }

    /* ---------------------------------------------------------------- */
    /* Riwayat khataman (bagian 7) - dapat ditambahkan berkali-kali */
    /* ---------------------------------------------------------------- */

    public function simpanKhataman(Request $request, Santri $santri)
    {
        $data = $request->validate([
            'jenis_khatam' => ['required', 'string', 'max:50'],
            'tahun' => ['required', 'integer', 'between:1950,2100'],
        ]);

        if (Khataman::withTrashed()
            ->where('santri_id', $santri->id)
            ->where('jenis_khatam', $data['jenis_khatam'])
            ->where('tahun', $data['tahun'])
            ->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Riwayat khatam tersebut sudah tercatat.',
            ]);
        }

        $santri->khatamans()->create($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Riwayat khataman berhasil ditambahkan.',
        ]);
    }

    public function hapusKhataman(Khataman $khataman)
    {
        $khataman->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Riwayat khataman berhasil dipindahkan ke Sampah.',
        ]);
    }

    /* ---------------------------------------------------------------- */
    /* API Isi Otomatis (bagian 8) */
    /* ---------------------------------------------------------------- */

    public function apiCari(Request $request)
    {
        $q = trim((string) $request->q);

        $query = Santri::aktif()->cari($q ?: null);

        $user = auth()->user();
        if ($user?->isWali()) {
            $query->where('wali_id', $user->id);
        } elseif ($user?->isSantri()) {
            $query->where('id', $user->santri_id);
        }

        $santris = $query
            ->orderBy('nis')
            ->limit(20)
            ->get(['id', 'nis', 'nama', 'kamar', 'jenis_kelamin', 'foto']);

        return response()->json([
            'data' => $santris->map(fn ($s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'nama' => $s->nama,
                'kamar' => $s->kamar,
                'jenis_kelamin' => $s->jenis_kelamin,
                'foto' => $s->foto_url,
                'label' => "{$s->nis} - {$s->nama}",
            ]),
        ]);
    }

    public function apiProfil(Santri $santri)
    {
        $this->authorizeSantri($santri);

        return response()->json([
            'id' => $santri->id,
            'nis' => $santri->nis,
            'nama' => $santri->nama,
            'kamar' => $santri->kamar,
            'jenis_kelamin' => $santri->jenis_kelamin,
            'status_aktif' => $santri->status_aktif,
            'foto' => $santri->foto_url,
        ]);
    }

    /* ---------------------------------------------------------------- */

    private function validate(Request $request): array
    {
        $santriId = $request->route('santri')?->id;
        $isCreate = ! $santriId;

        if ($request->has('wali_username')) {
            $request->merge(['wali_username' => trim((string) $request->input('wali_username'))]);
        }

        $waliTerpilih = $request->filled('wali_id');

        $rules = [
            'nis' => ['required', 'string', 'max:30', 'unique:santris,nis'.($santriId ? ",{$santriId}" : '')],
            'nama' => ['required', 'string', 'max:120'],
            'jenis_kelamin' => ['required', 'in:Putra,Putri'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'kamar' => ['nullable', 'string', 'max:80'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'status_aktif' => ['required', 'in:Aktif,Alumni,Pindah,Keluar'],
            'pendidikan_formal' => ['nullable', 'string', 'max:100'],
            'jenis_diniyah' => ['required', 'in:Diniyah,Asatidzah,Mutimin'],
            'level_diniyah' => ['nullable', 'required_if:jenis_diniyah,Diniyah', 'in:1 Wustho,2 Wustho,3 Wustho,1 \'Ulya,2 \'Ulya,3 \'Ulya'],
            'nama_ayah' => ['nullable', 'string', 'max:120'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:120'],
            'nama_ibu' => ['nullable', 'string', 'max:120'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:120'],
            'nama_wali' => ['nullable', 'string', 'max:120'],
            'pekerjaan_wali' => ['nullable', 'string', 'max:120'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat_wali' => ['nullable', 'string', 'max:500'],
            'wali_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_WALI)->whereNull('deleted_at'),
            ],
        ];

        if ($isCreate && ! $waliTerpilih) {
            $rules['wali_username'] = [
                'required',
                'string',
                'max:120',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username'),
            ];
            $rules['wali_password'] = ['required', 'string', 'min:8', 'max:100'];
        }

        return $request->validate($rules, [
            'level_diniyah.required_if' => 'Level Diniyah wajib dipilih untuk jenis Diniyah.',
            'wali_id.exists' => 'Akun yang dipilih tidak ditemukan atau bukan akun Wali Santri.',
            'wali_username.required' => 'Kode akun Wali wajib diisi jika tidak memilih wali yang sudah ada.',
            'wali_username.unique' => 'Kode akun Wali sudah digunakan. Pilih kode lain.',
            'wali_username.regex' => 'Kode akun Wali hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.',
            'wali_password.required' => 'Password Wali Santri wajib diisi jika membuat akun baru.',
            'wali_password.min' => 'Password minimal 8 karakter.',
        ]);
    }

    private function waliOptions()
    {
        return User::query()
            ->where('role', User::ROLE_WALI)
            ->withCount('santris')
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    private function buatAkunWali(Request $request, Santri $santri): User
    {
        $nama = trim((string) ($santri->nama_wali ?: $santri->nama_ayah ?: $santri->nama_ibu));

        return User::create([
            'name' => $nama !== '' ? $nama : 'Wali '.$santri->nama,
            'username' => $request->string('wali_username')->trim()->toString(),
            'password' => Hash::make($request->input('wali_password')),
            'role' => User::ROLE_WALI,
            'email_verified_at' => now(),
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
