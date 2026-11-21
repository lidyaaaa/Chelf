@php
    $cartCount = (int) ($book->carts_count ?? $book->carts()->count());
    $deletionReasons = [];

    if ($book->stok > 0) {
        $deletionReasons[] = sprintf('Stok masih tersedia (%d eksemplar).', $book->stok);
    }

    if ($cartCount > 0) {
        $deletionReasons[] = 'Buku masih ada di keranjang pelanggan.';
    }
@endphp

<div class="{{ $containerClass ?? 'd-flex gap-2 mt-2 pt-1' }}">
    <a href="{{ route('books.edit', $book) }}" class="btn btn-soft-primary btn-sm {{ $editBtnClass ?? 'flex-grow-1' }}">
        <i class="bi bi-pencil-square"></i> Edit Buku
    </a>
    @if($deletionReasons)
        <button type="button" class="btn btn-soft-secondary btn-sm px-2 book-delete-blocked-action"
            aria-label="Hapus buku dinonaktifkan"
            aria-disabled="true"
            data-delete-blocked-reason="{{ implode(' ', $deletionReasons) }}">
            <i class="bi bi-trash3"></i>
        </button>
    @else
        <form action="{{ route('books.destroy', $book) }}" method="POST" class="d-inline"
            onsubmit="return confirm('Yakin ingin menghapus buku {{ addslashes($book->judul) }}?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-soft-danger btn-sm px-2" title="Hapus Buku">
                <i class="bi bi-trash3"></i>
            </button>
        </form>
    @endif
</div>
