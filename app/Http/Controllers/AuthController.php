<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman form registrasi akun pelanggan baru.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Memproses pendaftaran akun pelanggan baru.
     * - Memvalidasi nama, email unik, nomor telepon (10-13 digit), dan password kuat.
     * - Otomatis memberikan role 'user' dan nomor_anggota otomatis (MBR-YYYY-XXXX) dari Model User.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:80|unique:users,email',
            'telepon' => 'required|digits_between:10,13',
            'nik' => 'required|digits:16|unique:users,nik',
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()->symbols()],
        ], [
            'telepon.digits_between' => 'Nomor telepon harus berupa angka 10–13 digit.',
            'nik.digits' => 'NIK harus 16 digit.',
            'nik.unique' => 'NIK sudah digunakan.',
            'password.min' => 'Password minimal harus 8 karakter.',
            'password.letters' => 'Password harus mengandung minimal satu huruf.',
            'password.numbers' => 'Password harus mengandung minimal satu angka.',
            'password.symbols' => 'Password harus mengandung minimal satu simbol (contoh: @, #, !, $, _).',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'kelas_jabatan' => 'Pelanggan Umum',
            'telepon' => $data['telepon'],
            'nik' => $data['nik'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Registrasi akun berhasil. Silakan login untuk mulai berbelanja.');
    }

    /**
     * Menampilkan halaman form login.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Memproses autentikasi login (Admin maupun Pelanggan).
     * Setelah berhasil login, session ID di-regenerate untuk mencegah serangan Session Fixation.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $request->session()->forget('url.intended');

            return redirect()->route('dashboard');
        }

        return back()
            ->withErrors([
                'email' => 'Email atau password salah.',
            ])
            ->onlyInput('email');
    }

    /**
     * Memproses logout pengguna, menghapus session aktif, dan me-regenerate CSRF token.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda berhasil logout.');
    }
}
