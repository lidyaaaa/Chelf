<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Menyimpan alamat pengiriman baru milik pelanggan.
     * Jika ini adalah alamat pertama pelanggan, otomatis dijadikan alamat utama (is_utama = true).
     */
    public function store(Request $request)
    {
        UserAddress::ensureTableExists();

        $data = $request->validate([
            'label' => 'required|string|max:20',
            'penerima' => 'required|string|max:50',
            'telepon' => 'required|string|max:15',
            'alamat_lengkap' => 'required|string|max:200',
        ]);

        $user = auth()->user();
        $hasExisting = UserAddress::where('user_id', $user->id)->exists();

        UserAddress::create([
            'user_id' => $user->id,
            'label' => trim($data['label']),
            'penerima' => trim($data['penerima']),
            'telepon' => trim($data['telepon']),
            'alamat_lengkap' => trim($data['alamat_lengkap']),
            'is_utama' => ! $hasExisting,
        ]);

        return back()->with('success', 'Alamat pengiriman baru berhasil ditambahkan.');
    }

    /**
     * Mengubah data alamat pengiriman milik pelanggan.
     */
    public function update(Request $request, UserAddress $address)
    {
        UserAddress::ensureTableExists();
        // Pastikan alamat ini benar-benar milik pelanggan yang sedang login
        abort_unless($address->user_id === auth()->id(), 403);

        $data = $request->validate([
            'label' => 'required|string|max:20',
            'penerima' => 'required|string|max:50',
            'telepon' => 'required|string|max:15',
            'alamat_lengkap' => 'required|string|max:200',
            'is_utama' => 'nullable|boolean',
        ]);

        // Jika dijadikan alamat utama, reset alamat lain milik user ini menjadi bukan utama
        if ($request->boolean('is_utama')) {
            UserAddress::where('user_id', auth()->id())->update(['is_utama' => false]);
        }

        $address->update([
            'label' => trim($data['label']),
            'penerima' => trim($data['penerima']),
            'telepon' => trim($data['telepon']),
            'alamat_lengkap' => trim($data['alamat_lengkap']),
            'is_utama' => $request->boolean('is_utama', $address->is_utama),
        ]);

        return back()->with('success', 'Alamat pengiriman berhasil diperbarui.');
    }

    /**
     * Menghapus alamat pengiriman tersimpan milik pelanggan.
     */
    public function destroy(UserAddress $address)
    {
        UserAddress::ensureTableExists();
        abort_unless($address->user_id === auth()->id(), 403);

        $address->delete();

        return back()->with('success', 'Alamat pengiriman berhasil dihapus.');
    }
}
