@extends('layouts.app')

@section('title', 'Edit Profil Saya — BookStore')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar-chip" style="width: 44px; height: 44px; font-size: 1.1rem;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                    <div>
                        <h5 class="fw-bold text-slate-900 mb-0">Edit Profil Saya</h5>
                        <div class="small text-muted font-monospace">
                            {{ $user->nomor_anggota }} &bull; {{ $user->role === 'admin' ? 'Administrator' : 'Pelanggan' }}
                        </div>
                    </div>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>

            <div class="card-body p-4">
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-slate-700">Alamat Email (Tidak dapat diubah)</label>
                        <input type="email" class="form-control bg-light text-muted" value="{{ $user->email }}" readonly disabled>
                        <div class="form-text small">
                            <i class="bi bi-lock-fill me-1"></i>Email terdaftar bersifat permanen dan tidak dapat diedit.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-slate-700">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" maxlength="50"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-slate-700">No. WhatsApp / Telepon</label>
                        <input type="text" name="telepon"
                            class="form-control @error('telepon') is-invalid @enderror"
                            placeholder="0812xxxxxxxx"
                            inputmode="numeric" pattern="\d{10,13}" minlength="10" maxlength="13"
                            oninput="this.value = this.value.replace(/\D/g, '')"
                            value="{{ old('telepon', $user->telepon) }}">
                        @error('telepon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="border-top pt-3 mb-3">
                        <h6 class="fw-bold text-slate-900 mb-1">Ganti Password (Opsional)</h6>
                        <p class="text-muted small mb-3">Kosongkan jika tidak ingin mengubah password akun Anda.</p>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-slate-700">Password Baru</label>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Min. 8 karakter (huruf, angka & simbol)"
                                minlength="8">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small text-slate-700">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation"
                                class="form-control"
                                placeholder="Ulangi password baru">
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Profil
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-soft-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
