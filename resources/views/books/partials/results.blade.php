@php
    $isAdminCatalog = auth()->check() && auth()->user()->role === 'admin';
@endphp

@if($isAdminCatalog)
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Buku</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok &amp; Penjualan</th>
                        <th class="pe-4 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $book)
                        @php
                            $gradientClass = 'cover-gradient-' . (($book->category_id ?? $book->id) % 4);
                            $soldCount = (int) ($book->terjual_count ?? 0);
                        @endphp
                        <tr>
                            <td class="ps-4" style="min-width: 260px;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 overflow-hidden flex-shrink-0 border"
                                        style="width: 48px; height: 64px; cursor: pointer;"
                                        data-bs-toggle="modal" data-bs-target="#bookDetailModal-{{ $book->id }}">
                                        @if($book->cover)
                                            <img src="{{ asset('storage/' . $book->cover) }}" class="w-100 h-100"
                                                style="object-fit: cover;" alt="Cover {{ $book->judul }}">
                                        @else
                                            <div class="w-100 h-100 d-flex align-items-center justify-content-center {{ $gradientClass }}">
                                                <i class="bi bi-book-half text-dark opacity-75"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-slate-900" style="cursor: pointer;"
                                            data-bs-toggle="modal" data-bs-target="#bookDetailModal-{{ $book->id }}">
                                            {{ $book->judul }}
                                        </div>
                                        <div class="small text-slate-600">{{ $book->penulis }}</div>
                                        <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                                            ISBN: {{ $book->isbn ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="rack-badge">
                                    <i class="bi bi-tag-fill text-primary"></i>
                                    {{ $book->category->nama ?? 'Umum' }}
                                </span>
                            </td>
                            <td class="fw-extrabold text-primary text-nowrap">
                                {{ $book->formatted_harga }}
                            </td>
                            <td>
                                <div>
                                    @if($book->stok > 0)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            Stok: {{ $book->stok }} eks
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                            Stok Habis
                                        </span>
                                    @endif
                                </div>
                                <div class="small text-slate-600 mt-1">
                                    {{ $soldCount }} terjual
                                </div>
                            </td>
                            <td class="pe-4 text-end text-nowrap">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-soft-secondary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#bookDetailModal-{{ $book->id }}">
                                        Detail
                                    </button>
                                    @include('admin.books.partials.card-actions', [
                                        'book' => $book,
                                        'containerClass' => 'd-inline-flex align-items-center gap-1',
                                        'editBtnClass' => '',
                                    ])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-light" style="width: 64px; height: 64px;">
                                    <i class="bi bi-search fs-3 text-slate-600"></i>
                                </div>
                                <h5 class="fw-bold text-slate-900">Buku Tidak Ditemukan</h5>
                                <p class="text-slate-600 small mb-3">Tidak ada buku yang cocok dengan kata kunci atau filter yang Anda pilih.</p>
                                <a href="{{ route('books.index') }}" class="btn btn-soft-primary btn-sm px-3">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filter Pencarian
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($books as $book)
        @include('books.partials.detail-modal', [
            'book' => $book,
            'gradientClass' => 'cover-gradient-' . (($book->category_id ?? $book->id) % 4),
            'soldCount' => (int) ($book->terjual_count ?? 0),
        ])
    @endforeach
@else
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
        @forelse($books as $book)
            @include('books.partials.book-card', ['book' => $book])
        @empty
            <div class="col-12">
                <div class="card border-0 p-5 text-center">
                    <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-light" style="width: 64px; height: 64px;">
                        <i class="bi bi-search fs-3 text-slate-600"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900">Buku Tidak Ditemukan</h5>
                    <p class="text-slate-600 small mb-3">Tidak ada buku yang cocok dengan kata kunci atau filter yang Anda pilih.</p>
                    <div>
                        <a href="{{ route('books.index') }}" class="btn btn-soft-primary btn-sm px-3">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Filter Pencarian
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endif

@if($books->hasPages())
    <div class="card border-0 px-4 py-3 mt-4">
        {{ $books->links('pagination.bookstore') }}
    </div>
@endif
