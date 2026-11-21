@extends('layouts.app')

@section('title', 'Katalog Buku — BookStore')

@section('content')
    {{-- Header Katalog Bookstore --}}
    <div class="opac-header-surface p-4 p-lg-5 mb-4">
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2"
                    style="background: #dbeafe; color: #1e40af; font-size: 0.76rem; font-weight: 700;">
                    <i class="bi bi-shop"></i>
                    <span>KATALOG TOKO BUKU DIGITAL</span>
                </div>
                <h1 class="fw-extrabold text-slate-900 mb-1" style="font-size: clamp(1.55rem, 2.6vw, 2.2rem);">
                    Temukan &amp; Beli <span class="text-gradient-brand">Buku Pilihanmu</span>
                </h1>
                <p class="text-slate-600 mb-0" style="font-size: 0.94rem;">
                    Cari buku secara instan berdasarkan Judul, Penulis, Kategori, Nomor ISBN, atau Rak.
                </p>
            </div>

            {{-- Panel Kanan Sesuai Role --}}
            <div class="col-lg-5 d-flex flex-wrap justify-content-lg-end align-items-center gap-2">
                <div class="stat-glass-pill">
                    <i class="bi bi-journals text-primary"></i>
                    <span>Total: <strong id="book-result-total" class="text-slate-900">{{ $books->total() }}</strong> Judul</span>
                </div>

                @auth
                    @if(auth()->user()->role === 'admin')
                        <button type="button" class="btn btn-soft-primary bg-white"
                            data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="bi bi-tags-fill"></i> + Kelola Kategori
                        </button>
                        <a href="{{ route('books.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Tambah Buku
                        </a>
                    @else
                        @php
                            $cartCount = (int) auth()->user()->carts()->sum('jumlah');
                        @endphp
                        <a href="{{ route('cart.index') }}" class="btn btn-primary">
                            <i class="bi bi-cart3"></i> Keranjang Saya ({{ $cartCount }})
                        </a>
                        <a href="{{ route('transactions.index') }}" class="btn btn-soft-primary bg-white">
                            <i class="bi bi-receipt"></i> Pesanan Saya
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk untuk Membeli
                    </a>
                @endauth
            </div>
        </div>

        {{-- Search Bar --}}
        <form id="book-search-form" method="GET" action="{{ route('books.index') }}">
            @if(request()->filled('category_id'))
                <input type="hidden" name="category_id" value="{{ request('category_id') }}">
            @endif
            @if(request()->filled('availability'))
                <input type="hidden" name="availability" value="{{ request('availability') }}">
            @endif

            <div class="opac-search-bar mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-9 col-lg-10">
                        <div class="d-flex align-items-center ps-2">
                            <i class="bi bi-search text-primary fs-5 me-2"></i>
                            <input id="book-search-input" type="text" name="search" class="form-control ps-1"
                                placeholder="Ketik judul buku, nama penulis, penerbit, atau nomor ISBN..."
                                value="{{ request('search') }}" autocomplete="off" aria-label="Cari buku">
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            Cari Buku
                        </button>
                        @if(request()->hasAny(['search', 'category_id', 'availability']))
                            <a href="{{ route('books.index') }}" class="btn btn-soft-danger px-3" title="Reset Semua Filter">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
        <p id="book-search-status" class="visually-hidden" aria-live="polite"></p>
        <div id="book-search-error" class="alert alert-danger d-none" role="alert"></div>

        {{-- Interactive Filter Chips --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-1">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small fw-bold text-slate-600 me-1">
                    <i class="bi bi-funnel me-1"></i>Kategori:
                </span>
                <a href="{{ route('books.index', array_filter(['search' => request('search'), 'availability' => request('availability')])) }}"
                    class="filter-chip {{ !request()->filled('category_id') ? 'active' : '' }}">
                    Semua
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('books.index', array_filter(['search' => request('search'), 'category_id' => $cat->id, 'availability' => request('availability')])) }}"
                        class="filter-chip {{ (string) request('category_id') === (string) $cat->id ? 'active' : '' }}">
                        <span>{{ $cat->nama }}</span>
                        <span class="opacity-75">({{ $cat->books_count }})</span>
                    </a>
                @endforeach

                @auth
                    @if(auth()->user()->role === 'admin')
                        <button type="button" class="filter-chip text-primary border-primary border-opacity-50"
                            style="border-style: dashed !important; background: rgba(239, 246, 255, 0.85);"
                            data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="bi bi-plus-circle-fill"></i> Tambah / Edit Kategori
                        </button>
                    @endif
                @endauth
            </div>

            <div class="d-flex flex-wrap align-items-center gap-1">
                <a href="{{ route('books.index', array_filter(['search' => request('search'), 'category_id' => request('category_id')])) }}"
                    class="filter-chip {{ !request()->filled('availability') ? 'active' : '' }}">
                    Semua Stok
                </a>
                <a href="{{ route('books.index', array_filter(['search' => request('search'), 'category_id' => request('category_id'), 'availability' => 'available'])) }}"
                    class="filter-chip {{ request('availability') === 'available' ? 'active' : '' }}">
                    <span class="status-dot bg-success"></span> Stok Tersedia
                </a>
                <a href="{{ route('books.index', array_filter(['search' => request('search'), 'category_id' => request('category_id'), 'availability' => 'out_of_stock'])) }}"
                    class="filter-chip {{ request('availability') === 'out_of_stock' ? 'active' : '' }}">
                    <span class="status-dot bg-danger"></span> Stok Habis
                </a>
            </div>
        </div>
    </div>

    {{-- Grid Kartu Buku --}}
    <div id="book-search-results" aria-busy="false" aria-live="polite">
        @include('books.partials.results', ['books' => $books])
    </div>

    {{-- Modal Kategori khusus Admin --}}
    @auth
        @if(auth()->user()->role === 'admin')
            @include('admin.books.partials.category-modal')
        @endif
    @endauth

    <script>
        const bookSearchForm = document.getElementById('book-search-form');
        const bookSearchInput = document.getElementById('book-search-input');
        const bookSearchResults = document.getElementById('book-search-results');
        const bookSearchTotal = document.getElementById('book-result-total');
        const bookSearchStatus = document.getElementById('book-search-status');
        const bookSearchError = document.getElementById('book-search-error');
        let bookSearchTimer = null;
        let bookSearchController = null;

        const syncFilterChipsWithSearch = (searchValue) => {
            document.querySelectorAll('a.filter-chip[href]').forEach((chip) => {
                const url = new URL(chip.href, window.location.origin);
                if (searchValue) {
                    url.searchParams.set('search', searchValue);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.delete('page');
                chip.href = url.toString();
            });
        };

        const fetchCatalogResults = async (targetUrl, { pushHistory = false, scrollToResults = false } = {}) => {
            if (bookSearchController) {
                bookSearchController.abort();
            }

            const controller = new AbortController();
            bookSearchController = controller;
            bookSearchResults.setAttribute('aria-busy', 'true');
            bookSearchStatus.textContent = 'Memuat hasil pencarian.';
            bookSearchError.classList.add('d-none');

            try {
                const response = await fetch(targetUrl, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error('Pencarian buku gagal dimuat.');
                }

                const result = await response.json();

                if (bookSearchController !== controller) {
                    return;
                }

                bookSearchResults.innerHTML = result.html;
                bookSearchTotal.textContent = new Intl.NumberFormat('id-ID').format(result.total);
                bookSearchStatus.textContent = result.total === 0
                    ? 'Tidak ada buku yang cocok.'
                    : `Ditemukan ${result.total} buku.`;

                const nextRelativeUrl = targetUrl.pathname + targetUrl.search;
                if (pushHistory) {
                    window.history.pushState(null, '', nextRelativeUrl);
                } else {
                    window.history.replaceState(null, '', nextRelativeUrl);
                }

                if (scrollToResults) {
                    bookSearchResults.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                bookSearchError.textContent = 'Pencarian gagal dimuat. Silakan coba lagi.';
                bookSearchError.classList.remove('d-none');
                bookSearchStatus.textContent = 'Pencarian gagal dimuat.';
            } finally {
                if (bookSearchController === controller) {
                    bookSearchController = null;
                    bookSearchResults.setAttribute('aria-busy', 'false');
                }
            }
        };

        const searchBooks = () => {
            const searchUrl = new URL(bookSearchForm.action, window.location.origin);
            const query = new URLSearchParams(new FormData(bookSearchForm));
            const trimmedSearch = bookSearchInput.value.trim();

            if (trimmedSearch) {
                query.set('search', trimmedSearch);
            } else {
                query.delete('search');
            }

            query.delete('page');
            searchUrl.search = query.toString();
            syncFilterChipsWithSearch(trimmedSearch);

            return fetchCatalogResults(searchUrl, { pushHistory: false, scrollToResults: false });
        };

        bookSearchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            window.clearTimeout(bookSearchTimer);
            searchBooks();
        });

        bookSearchInput.addEventListener('input', () => {
            window.clearTimeout(bookSearchTimer);

            if (bookSearchController) {
                bookSearchController.abort();
            }

            bookSearchTimer = window.setTimeout(searchBooks, 300);
        });

        bookSearchResults.addEventListener('click', (event) => {
            const pageLink = event.target.closest('.pagination a.page-link');
            if (!pageLink || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();
            const targetUrl = new URL(pageLink.href, window.location.origin);
            fetchCatalogResults(targetUrl, { pushHistory: true, scrollToResults: true });
        });

        window.addEventListener('popstate', () => {
            const currentUrl = new URL(window.location.href);
            bookSearchInput.value = currentUrl.searchParams.get('search') || '';
            syncFilterChipsWithSearch(bookSearchInput.value.trim());
            fetchCatalogResults(currentUrl, { pushHistory: false, scrollToResults: false });
        });
    </script>
@endsection
