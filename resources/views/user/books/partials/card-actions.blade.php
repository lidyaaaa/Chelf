<div class="mt-2 pt-1">
    @if($book->stok > 0)
        <form action="{{ route('cart.store', $book) }}" method="POST" class="d-flex gap-1 align-items-stretch js-cart-add-form" data-book-id="{{ $book->id }}">
            @csrf
            <input type="number"
                name="jumlah"
                value="1"
                min="1"
                max="{{ $book->stok }}"
                class="form-control form-control-sm text-center fw-bold px-1"
                style="width: 58px;"
                aria-label="Jumlah {{ $book->judul }}"
                title="Ketik jumlah (Maks: {{ $book->stok }})"
                onfocus="this.select()"
                oninput="if(this.value !== '' && this.value < 1) this.value = 1; if(this.value > {{ $book->stok }}) this.value = {{ $book->stok }};">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1 py-2">
                <i class="bi bi-cart-plus-fill"></i> + Keranjang
            </button>
        </form>
    @else
        <button class="btn btn-soft-secondary btn-sm w-100 py-2 opacity-75" disabled>
            <i class="bi bi-x-circle"></i> Stok Habis
        </button>
    @endif
</div>
