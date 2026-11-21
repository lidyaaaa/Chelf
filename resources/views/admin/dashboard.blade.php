@extends('layouts.app')

@section('title', 'Dashboard Admin — BookStore')

@section('content')
    @php
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    @endphp

    <div class="opac-header-surface p-4 p-lg-5 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2"
                    style="background: #dbeafe; color: #1e40af; font-size: 0.76rem; font-weight: 700;">
                    <i class="bi bi-shield-check"></i>
                    <span>PANEL KENDALI ADMIN BOOKSTORE</span>
                </div>
                <h2 class="fw-extrabold text-slate-900 mb-1">
                    Monitoring <span class="text-gradient-brand">Penjualan &amp; Stok Buku</span>
                </h2>
                <p class="text-slate-600 mb-0 small">
                    Pantau omzet penjualan, stok buku tersedia, keranjang aktif pelanggan, dan konfirmasi transaksi.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('books.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Buku
                </a>
                <a href="{{ route('transactions.index') }}" class="btn btn-soft-primary bg-white">
                    <i class="bi bi-receipt-cutoff"></i> Transaksi Penjualan
                </a>
                <a href="{{ route('reports.index') }}" class="btn btn-soft-secondary bg-white">
                    <i class="bi bi-printer"></i> Laporan Penjualan
                </a>
                <a href="{{ route('users.index') }}" class="btn btn-soft-secondary bg-white">
                    <i class="bi bi-people"></i> Daftar Pengguna
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-center mt-3 pt-3 border-top border-primary border-opacity-10">
            <div class="col-auto">
                <span class="small fw-bold text-slate-700">
                    <i class="bi bi-calendar3 text-primary me-1"></i> Filter Pemasukan &amp; Transaksi per Bulan:
                </span>
            </div>
            <div class="col-sm-4 col-md-3">
                <select name="month" class="form-select form-select-sm bg-white">
                    <option value="">-- Semua Bulan --</option>
                    @foreach($namaBulan as $num => $label)
                        <option value="{{ $num }}" @selected((int) ($month ?? 0) === $num)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <select name="year" class="form-select form-select-sm bg-white">
                    <option value="">-- Semua Tahun --</option>
                    @foreach(range((int) date('Y'), 2023) as $yr)
                        <option value="{{ $yr }}" @selected((int) ($year ?? 0) === $yr)>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
                @if(!empty($month) || !empty($year))
                    <a href="{{ route('dashboard') }}" class="btn btn-soft-danger btn-sm">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Kartu Statistik Utama --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-slate-600 small fw-bold">KATALOG &amp; STOK</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                            <i class="bi bi-bookshelf fs-5"></i>
                        </span>
                    </div>
                    <h3 class="fw-extrabold text-slate-900 mb-1">{{ $stats['total_judul'] }} Judul</h3>
                    <div class="small text-slate-600">
                        Total Stok Tersedia: <strong class="text-success">{{ $stats['stok_tersedia'] }}</strong> eks
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-slate-600 small fw-bold">TOTAL PENDAPATAN</span>
                        <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-3">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </span>
                    </div>
                    <h3 class="fw-extrabold text-primary mb-1">Rp {{ number_format($stats['total_pendapatan'], 0, ',', '.') }}</h3>
                    <div class="small text-slate-600">
                        Buku Terjual: <strong class="text-slate-900">{{ $stats['buku_terjual'] }}</strong> eks
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-slate-600 small fw-bold">STATUS TRANSAKSI</span>
                        <span class="badge bg-warning bg-opacity-10 text-dark p-2 rounded-3">
                            <i class="bi bi-receipt fs-5"></i>
                        </span>
                    </div>
                    <h3 class="fw-extrabold text-slate-900 mb-1">{{ $stats['transaksi_selesai'] }} Selesai</h3>
                    <div class="small text-slate-600">
                        Menunggu Konfirmasi: <strong class="text-warning">{{ $stats['menunggu_konfirmasi'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-slate-600 small fw-bold">KERANJANG &amp; PELANGGAN</span>
                        <span class="badge bg-info bg-opacity-10 text-primary p-2 rounded-3">
                            <i class="bi bi-cart-check fs-5"></i>
                        </span>
                    </div>
                    <h3 class="fw-extrabold text-slate-900 mb-1">{{ $stats['keranjang_aktif'] }} Item Cart</h3>
                    <div class="small text-slate-600">
                        Pelanggan Terdaftar: <a href="{{ route('users.index') }}" class="fw-bold text-primary text-decoration-none">{{ $stats['total_pelanggan'] }} akun &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Monitoring Stok Buku Menipis --}}
        <div class="col-lg-7">
            <div class="card h-100 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-bold text-slate-900 mb-0">
                        <i class="bi bi-box-seam text-primary me-1"></i> Monitoring Stok Buku (&le; 5 Eksemplar)
                    </h6>
                    <small class="text-slate-600">Segera tambah stok agar pelanggan tetap dapat membeli buku</small>
                </div>
                <div class="table-responsive flex-grow-1">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Judul Buku</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Sisa Stok</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockBooks as $book)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-slate-900">{{ $book->judul }}</div>
                                        <div class="small text-slate-600">{{ $book->penulis }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-slate-700">
                                            {{ $book->category->nama ?? 'Umum' }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold">{{ $book->formatted_harga }}</td>
                                    <td>
                                        <span class="badge {{ $book->stok === 0 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                            {{ $book->stok }} eks
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('books.edit', $book) }}" class="btn btn-soft-primary btn-sm">
                                            Update Stok
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-slate-600 py-5">
                                        <i class="bi bi-check2-circle text-success fs-3 d-block mb-1"></i>
                                        Seluruh stok buku masih aman (&gt; 5 eksemplar).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($lowStockBooks->hasPages())
                    <div class="card-footer bg-white border-top py-3 px-4 mt-auto">
                        {{ $lowStockBooks->links('pagination.bookstore') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Transaksi Penjualan Terbaru --}}
        <div class="col-lg-5">
            <div class="card h-100 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-slate-900 mb-0">Transaksi Terbaru</h6>
                    <a href="{{ route('transactions.index') }}" class="btn btn-soft-primary btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body px-4 py-2 flex-grow-1">
                    <ul class="list-group list-group-flush">
                        @forelse($recentTransactions as $trx)
                            <li class="list-group-item px-0 py-3 d-flex justify-content-between align-items-start {{ $loop->first ? 'border-0' : 'border-bottom-0 border-top' }}">
                                <div>
                                    <div class="fw-bold text-slate-900">
                                        {{ $trx->user->name ?? '-' }} &bull; <code class="text-primary">{{ $trx->kode_transaksi }}</code>
                                    </div>
                                    <div class="small text-slate-700 mt-1">
                                        {{ $trx->items->map(fn($it) => ($it->book->judul ?? 'Buku') . ' (' . $it->jumlah . 'x)')->implode(', ') }}
                                    </div>
                                    <div class="small text-slate-600 mt-1">
                                        {{ $trx->total_item }} buku &bull; <strong>Rp {{ number_format($trx->total_harga, 0, ',', '.') }}</strong>
                                        &bull; <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">{{ $trx->metode_pembayaran }}</span>
                                    </div>
                                </div>
                                <span class="badge bg-secondary bg-opacity-10 text-slate-700 border">
                                    {{ strtoupper($trx->status) }}
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-center text-slate-600 py-4 border-0">
                                Belum ada transaksi penjualan.
                            </li>
                        @endforelse
                    </ul>
                </div>
                @if($recentTransactions->hasPages())
                    <div class="card-footer bg-white border-top py-3 px-4 mt-auto">
                        {{ $recentTransactions->links('pagination.bookstore') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
