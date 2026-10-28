<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index', [
            // Relasi dimuat sebagai 'santris' (daftar anak). Nama 'santri' kini
        // hanya accessor yang mengembalikan anak utama, dan accessor bukan
        // relasi Eloquent sehingga tidak bisa di-load.
        'user' => auth()->user()->load('santris'),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('users', 'public');
        }

        $user->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Profil berhasil diperbarui.',
        ]);
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'current_password' => 'Password lama',
            'password' => 'Password baru',
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Password berhasil diperbarui.',
        ]);
    }
}
