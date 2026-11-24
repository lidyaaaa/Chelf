@extends('layouts.app')

@section('title', 'Keranjang Belanja — BookStore')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-extrabold text-slate-900 mb-1">
                <i class="bi bi-cart3 text-primary me-1"></i> Keranjang Belanja Saya
            </h2>
            <p class="text-slate-600 mb-0 small">
                Centang produk yang ingin di-checkout, ketik jumlah atau gunakan tombol <code>-</code> / <code>+</code>, dan pilih alamat pengiriman.
            </p>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-soft-primary bg-white"
                data-bs-toggle="modal" data-bs-target="#addressModal">
                <i class="bi bi-geo-alt-fill"></i> + Kelola Alamat
            </button>
            <a href="{{ route('books.index') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Buku Lainnya
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Daftar Item di Keranjang --}}
        <div class="col-lg-8">
            <div class="card overflow-hidden">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 44px;" class="text-center">
                                    <input type="checkbox" id="checkAllCart" class="form-check-input"
                                        style="width: 1.15rem; height: 1.15rem; cursor: pointer;"
                                        @checked($cartItems->isNotEmpty())
                                        @disabled($cartItems->isEmpty())
                                        title="Pilih Semua">
                                </th>
                                <th>Buku</th>
                                <th>Harga Satuan</th>
                                <th style="width: 150px;" class="text-center">Jumlah</th>
                                <th>Subtotal</th>
                                <th class="text-end">Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cartItems as $item)
                                @php
                                    $maxStok = max(1, (int) ($item->book->stok ?? 1));
                                    $hargaSatuan = (int) ($item->book->harga ?? 0);
                                @endphp
                                <tr id="cart-row-{{ $item->id }}"
                                    data-cart-id="{{ $item->id }}"
                                    data-price="{{ $hargaSatuan }}"
                                    data-qty="{{ $item->jumlah }}"
                                    data-max="{{ $maxStok }}"
                                    data-update-url="{{ route('cart.update', $item) }}">
                                    <td class="text-center">
                                        <input type="checkbox"
                                            class="form-check-input cart-item-check"
                                            name="cart_ids[]"
                                            form="checkoutForm"
                                            value="{{ $item->id }}"
                                            style="width: 1.15rem; height: 1.15rem; cursor: pointer;"
                                            checked>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-slate-900">{{ $item->book->judul ?? '-' }}</div>
                                        <div class="small text-slate-600">
                                            {{ $item->book->penulis ?? '-' }} &bull;
                                            <span class="text-success fw-semibold">Stok: {{ $item->book->stok ?? 0 }}</span>
                                        </div>
                                    </td>
                                    <td class="fw-semibold text-slate-700">
                                        Rp {{ number_format($hargaSatuan, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center border rounded-3 bg-white p-1 gap-1">
                                            <button type="button"
                                                class="btn btn-sm btn-light border-0 px-2 py-1 fw-bold btn-qty-minus"
                                                onclick="changeCartQty({{ $item->id }}, -1)"
                                                @disabled($item->jumlah <= 1)>
                                                <i class="bi bi-dash-lg"></i>
                                            </button>
                                            <input type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                class="form-control form-control-sm text-center fw-bold text-slate-900 cart-qty-display px-1"
                                                style="width: 58px; border: 1px solid #cbd5e1; background: #f8fafc;"
                                                value="{{ $item->jumlah }}"
                                                data-min="1"
                                                data-max="{{ $maxStok }}"
                                                aria-label="Jumlah {{ $item->book->judul ?? 'buku' }}"
                                                onfocus="this.select()"
                                                oninput="handleCartQtyInput({{ $item->id }}, this)"
                                                onblur="commitCartQty({{ $item->id }}, this)"
                                                onkeydown="if(event.key === 'Enter'){ event.preventDefault(); this.blur(); }">
                                            <button type="button"
                                                class="btn btn-sm btn-light border-0 px-2 py-1 fw-bold btn-qty-plus"
                                                onclick="changeCartQty({{ $item->id }}, 1)"
                                                @disabled($item->jumlah >= $maxStok)>
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size: 0.7rem;">Maks: {{ $maxStok }}</div>
                                    </td>
                                    <td class="fw-extrabold text-primary cart-subtotal-display">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('cart.destroy', $item) }}" method="POST"
                                            onsubmit="return confirm('Hapus buku ini dari keranjang?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-soft-danger btn-sm">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-slate-600">
                                        <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Keranjang belanja Anda masih kosong.
                                        <div class="mt-3">
                                            <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm px-3">
                                                Belanja Buku Sekarang
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($cartItems->hasPages())
                <div class="mt-4">
                    {{ $cartItems->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

        {{-- Panel Checkout & Ringkasan Pembayaran --}}
        <div class="col-lg-4">
            <div class="card p-4">
                <h5 class="fw-bold text-slate-900 border-bottom pb-3 mb-3">
                    Ringkasan Checkout
                </h5>

                <div class="d-flex justify-content-between small text-slate-600 mb-2">
                    <span>Produk Dipilih di Halaman Ini</span>
                    <span class="fw-bold text-slate-900"><span id="summaryTotalItem">{{ $totalItem }}</span> Eksemplar</span>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <span class="fw-semibold text-slate-700">Total Pembayaran</span>
                    <span class="fs-4 fw-extrabold text-primary" id="summaryTotalHarga">
                        Rp {{ number_format($totalHarga, 0, ',', '.') }}
                    </span>
                </div>

                <form id="checkoutForm" action="{{ route('transactions.checkout') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cart_ids_submitted" value="1">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-slate-700">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select name="metode_pembayaran" class="form-select" required>
                            @foreach(\App\Models\Transaction::PAYMENT_METHODS as $method)
                                <option value="{{ $method }}" @selected(old('metode_pembayaran') === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-slate-700 mb-0">
                                Pilih Alamat Pengiriman <span class="text-danger">*</span>
                            </label>
                            <button type="button" class="btn btn-link btn-sm p-0 fw-semibold text-decoration-none"
                                data-bs-toggle="modal" data-bs-target="#addressModal">
                                <i class="bi bi-plus-circle-fill me-1"></i>+ Tambah Alamat
                            </button>
                        </div>

                        <div class="input-group">
                            <select name="address_id" class="form-select @error('address_id') is-invalid @enderror" required>
                                @forelse($addresses as $addr)
                                    <option value="{{ $addr->id }}" @selected(old('address_id') == $addr->id || $loop->first)>
                                        [{{ $addr->label }}] {{ $addr->penerima }} — {{ \Illuminate\Support\Str::limit($addr->alamat_lengkap, 38) }}
                                    </option>
                                @empty
                                    <option value="" disabled selected>-- Belum ada alamat, klik + Alamat --</option>
                                @endforelse
                            </select>
                            <button type="button" class="btn btn-soft-primary"
                                data-bs-toggle="modal" data-bs-target="#addressModal"
                                title="Kelola Alamat Pengiriman">
                                <i class="bi bi-geo-alt-fill"></i>
                            </button>
                        </div>
                        @error('address_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-slate-700">Catatan Pesanan (Opsional)</label>
                        <input type="text" name="catatan" class="form-control" maxlength="100"
                            placeholder="Contoh: Bungkus rapi / hadiah..." value="{{ old('catatan') }}">
                    </div>

                    <button type="submit" id="btnCheckoutSubmit" class="btn btn-primary w-100 py-2 fw-bold" @disabled($cartItems->isEmpty())>
                        <i class="bi bi-bag-check-fill me-1"></i> Checkout Produk Dipilih
                    </button>
                </form>
            </div>
        </div>
    </div>

    @include('user.cart.partials.address-modal')

    <script>
        function formatRupiah(angka) {
            return 'Rp ' + Number(angka).toLocaleString('id-ID');
        }

        function recalculateCartSummary() {
            const rows = document.querySelectorAll('tr[data-cart-id]');
            let totalItem = 0;
            let totalHarga = 0;
            let checkedCount = 0;

            rows.forEach((row) => {
                const checkbox = row.querySelector('.cart-item-check');
                const qty = parseInt(row.getAttribute('data-qty'), 10) || 1;
                const price = parseInt(row.getAttribute('data-price'), 10) || 0;

                if (checkbox && checkbox.checked) {
                    checkedCount++;
                    totalItem += qty;
                    totalHarga += qty * price;
                }
            });

            const checkAll = document.getElementById('checkAllCart');
            if (checkAll && rows.length > 0) {
                checkAll.checked = checkedCount === rows.length;
                checkAll.indeterminate = checkedCount > 0 && checkedCount < rows.length;
            }

            document.getElementById('summaryTotalItem').textContent = totalItem;
            document.getElementById('summaryTotalHarga').textContent = formatRupiah(totalHarga);

            const btnCheckout = document.getElementById('btnCheckoutSubmit');
            if (btnCheckout) {
                btnCheckout.disabled = checkedCount === 0;
            }
        }

        const cartQtyTimers = {};

        function changeCartQty(cartId, delta) {
            const row = document.getElementById('cart-row-' + cartId);
            if (!row) return;

            const currentQty = parseInt(row.getAttribute('data-qty'), 10) || 1;
            setCartQty(cartId, currentQty + delta, true);
        }

        function handleCartQtyInput(cartId, inputEl) {
            const row = document.getElementById('cart-row-' + cartId);
            if (!row) return;

            const digitsOnly = inputEl.value.replace(/[^0-9]/g, '');
            if (inputEl.value !== digitsOnly) {
                inputEl.value = digitsOnly;
            }

            if (digitsOnly === '') {
                clearTimeout(cartQtyTimers[cartId]);
                return;
            }

            const maxStok = parseInt(row.getAttribute('data-max'), 10) || 1;
            const price = parseInt(row.getAttribute('data-price'), 10) || 0;

            let newQty = parseInt(digitsOnly, 10);
            if (isNaN(newQty) || newQty < 1) {
                newQty = 1;
            }
            if (newQty > maxStok) {
                newQty = maxStok;
                inputEl.value = String(newQty);
            }

            row.setAttribute('data-qty', newQty);
            row.querySelector('.cart-subtotal-display').textContent = formatRupiah(newQty * price);
            row.querySelector('.btn-qty-minus').disabled = newQty <= 1;
            row.querySelector('.btn-qty-plus').disabled = newQty >= maxStok;
            recalculateCartSummary();

            clearTimeout(cartQtyTimers[cartId]);
            cartQtyTimers[cartId] = setTimeout(() => setCartQty(cartId, newQty, false), 450);
        }

        function commitCartQty(cartId, inputEl) {
            const row = document.getElementById('cart-row-' + cartId);
            if (!row) return;

            const currentQty = parseInt(row.getAttribute('data-qty'), 10) || 1;
            const raw = inputEl.value.replace(/[^0-9]/g, '');
            setCartQty(cartId, raw === '' ? currentQty : raw, true);
        }

        async function setCartQty(cartId, rawQty, syncInput = true) {
            clearTimeout(cartQtyTimers[cartId]);
            const row = document.getElementById('cart-row-' + cartId);
            if (!row) return;

            const maxStok = parseInt(row.getAttribute('data-max'), 10) || 1;
            const price = parseInt(row.getAttribute('data-price'), 10) || 0;
            const updateUrl = row.getAttribute('data-update-url');

            let newQty = parseInt(rawQty, 10);
            if (isNaN(newQty) || newQty < 1) newQty = 1;
            if (newQty > maxStok) newQty = maxStok;

            row.setAttribute('data-qty', newQty);
            const inputEl = row.querySelector('.cart-qty-display');
            if (inputEl && (syncInput || document.activeElement !== inputEl)) {
                inputEl.value = newQty;
            }
            row.querySelector('.cart-subtotal-display').textContent = formatRupiah(newQty * price);
            row.querySelector('.btn-qty-minus').disabled = newQty <= 1;
            row.querySelector('.btn-qty-plus').disabled = newQty >= maxStok;
            recalculateCartSummary();

            try {
                const response = await fetch(updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ jumlah: newQty })
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (data.message) alert(data.message);
                    window.location.reload();
                    return;
                }

                if (typeof data.cart_count === 'number') {
                    document.querySelectorAll('[data-cart-count-badge]').forEach((badge) => {
                        badge.textContent = data.cart_count > 0 ? String(data.cart_count) : '(0)';
                    });
                    document.querySelectorAll('[data-cart-count-text]').forEach((el) => {
                        el.textContent = String(data.cart_count);
                    });
                }
            } catch (e) {
                window.location.reload();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const checkAll = document.getElementById('checkAllCart');
            const itemChecks = document.querySelectorAll('.cart-item-check');

            if (checkAll) {
                checkAll.addEventListener('change', () => {
                    itemChecks.forEach((chk) => {
                        chk.checked = checkAll.checked;
                    });
                    recalculateCartSummary();
                });
            }

            itemChecks.forEach((chk) => {
                chk.addEventListener('change', recalculateCartSummary);
            });
        });
    </script>
@endsection
