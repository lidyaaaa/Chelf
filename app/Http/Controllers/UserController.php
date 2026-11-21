<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Menampilkan halaman Edit Profil (untuk Pelanggan maupun Admin).
     */
    public function editProfile()
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Memperbarui data profil pengguna yang sedang login (email tidak dapat diubah).
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'telepon' => 'nullable|digits_between:10,13',
            'nik' => 'nullable|digits:16|unique:users,nik,' . $user->id,
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)->letters()->numbers()->symbols()],
        ], [
            'name.max' => 'Nama lengkap maksimal 50 karakter.',
            'telepon.digits_between' => 'Nomor telepon harus berupa angka 10–13 digit.',
            'nik.digits' => 'NIK harus 16 digit.',
            'nik.unique' => 'NIK sudah digunakan.',
            'password.min' => 'Password baru minimal harus 8 karakter.',
            'password.letters' => 'Password baru harus mengandung minimal satu huruf.',
            'password.numbers' => 'Password baru harus mengandung minimal satu angka.',
            'password.symbols' => 'Password baru harus mengandung minimal satu simbol.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $payload = [
            'name' => trim($data['name']),
            'telepon' => $data['telepon'] ?? null,
            'nik' => $data['nik'] ?? null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }

    /**
     * Menampilkan daftar seluruh pengguna/pelanggan yang terdaftar di sistem.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->search);

        $users = User::query()
            ->where('role', 'user')
            ->withCount(['transactions', 'carts'])
            ->withSum('transactions as total_belanja', 'total_harga')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nomor_anggota', 'like', "%{$search}%")
                        ->orWhere('telepon', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalMembers = User::where('role', 'user')->count();

        return view('admin.users.index', compact('users', 'totalMembers'));
    }
}
