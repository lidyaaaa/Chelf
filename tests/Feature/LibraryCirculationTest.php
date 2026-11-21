<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryCirculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_book_form_leaves_price_blank_until_admin_enters_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('books.create'))
            ->assertOk()
            ->assertSee('name="harga" class="form-control"', false)
            ->assertSee('value="" placeholder="Contoh: 75000" min="0"', false)
            ->assertDontSee('value="75000"', false)
            ->assertDontSee('name="call_number"', false);
    }

    public function test_book_edit_form_does_not_show_call_number_field(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $book = Book::create([
            'judul' => 'Buku Tanpa Field Rak',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2025,
            'harga' => 50000,
            'stok' => 1,
            'total_koleksi' => 1,
            'call_number' => 'OLD-01',
        ]);

        $this->actingAs($admin)
            ->get(route('books.edit', $book))
            ->assertOk()
            ->assertDontSee('name="call_number"', false);
    }

    public function test_public_catalog_search_and_availability_filter(): void
    {
        $category = Category::create(['nama' => 'Pemrograman', 'kode_ddc' => '005']);
        Book::create([
            'category_id' => $category->id,
            'judul' => 'Pemrograman Laravel Modern',
            'isbn' => '978-602-111',
            'call_number' => '005.133 LAR',
            'penulis' => 'Budi Raharjo',
            'penerbit' => 'Informatika',
            'tahun_terbit' => 2025,
            'harga' => 110000,
            'stok' => 3,
            'total_koleksi' => 3,
        ]);

        $response = $this->get('/?search=978-602-111&availability=available');
        $response->assertOk();
        $response->assertSee('Pemrograman Laravel Modern');
        $response->assertSee('Rp 110.000');
        $response->assertDontSee('Navigasi Halaman');
    }

    public function test_json_catalog_search_returns_matching_books_and_result_count(): void
    {
        Book::create([
            'judul' => 'Laravel untuk Pemula',
            'penulis' => 'Dewi Anggraini',
            'penerbit' => 'Informatika',
            'tahun_terbit' => 2025,
            'harga' => 90000,
            'stok' => 2,
            'total_koleksi' => 2,
        ]);
        Book::create([
            'judul' => 'Belajar Basis Data',
            'penulis' => 'Budi Santoso',
            'penerbit' => 'Media Kita',
            'tahun_terbit' => 2025,
            'harga' => 85000,
            'stok' => 1,
            'total_koleksi' => 1,
        ]);

        $response = $this->getJson(route('books.index', ['search' => 'Laravel']));

        $response->assertOk()
            ->assertJsonStructure(['html', 'total'])
            ->assertJsonPath('total', 1);
        $this->assertStringContainsString('Laravel untuk Pemula', (string) $response->json('html'));
        $this->assertStringNotContainsString('Belajar Basis Data', (string) $response->json('html'));
    }

    public function test_catalog_books_are_paginated(): void
    {
        $customer = User::factory()->create(['role' => 'user']);

        foreach (range(1, 11) as $bookNumber) {
            Book::create([
                'judul' => 'Buku Katalog '.$bookNumber,
                'penulis' => 'Penulis',
                'penerbit' => 'Penerbit',
                'tahun_terbit' => 2025,
                'harga' => 50000,
                'stok' => 1,
                'total_koleksi' => 1,
            ]);
        }

        // Guest hanya melihat preview 8 buku terbaru tanpa pagination halaman ke-2
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Buku Katalog 11')
            ->assertSee('Buku Katalog 4')
            ->assertDontSee('Buku Katalog 3')
            ->assertDontSeeText('Menampilkan 1–8 dari 11 data');

        // User yang sudah login dapat melihat seluruh katalog dengan pagination
        $this->actingAs($customer)
            ->get(route('books.index'))
            ->assertOk()
            ->assertSeeText('Menampilkan 1–8 dari 11 data');

        $this->actingAs($customer)
            ->get(route('books.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Buku Katalog 1')
            ->assertDontSee('Buku Katalog 4');
    }

    public function test_all_pages_render_without_blade_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);
        $book = Book::create([
            'judul' => 'Buku Render Uji',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2025,
            'harga' => 80000,
            'stok' => 3,
            'total_koleksi' => 3,
        ]);

        $trx = Transaction::create([
            'user_id' => $customer->id,
            'tanggal_transaksi' => today(),
            'total_item' => 1,
            'total_harga' => 80000,
            'metode_pembayaran' => 'QRIS',
            'status' => Transaction::STATUS_MENUNGGU,
        ]);

        TransactionItem::create([
            'transaction_id' => $trx->id,
            'book_id' => $book->id,
            'jumlah' => 1,
            'harga_satuan' => 80000,
            'subtotal' => 80000,
        ]);

        // Test seluruh halaman Admin
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Navigasi Halaman');
        $this->actingAs($admin)->get(route('books.index'))->assertOk();
        $this->actingAs($admin)->get(route('books.create'))->assertOk();
        $this->actingAs($admin)->get(route('books.edit', $book))->assertOk();
        $this->actingAs($admin)->get(route('transactions.index'))->assertOk();
        $this->actingAs($admin)->get(route('transactions.receipt', $trx))->assertOk();
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();

        // Test seluruh halaman Pelanggan (termasuk Keranjang / Cart)
        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Navigasi Halaman');
        $this->actingAs($customer)->get(route('books.index'))->assertOk();
        $this->actingAs($customer)->get(route('cart.index'))->assertOk();
        $this->actingAs($customer)->get(route('transactions.index'))->assertOk();
        $this->actingAs($customer)->get(route('transactions.receipt', $trx))->assertOk();
        $this->actingAs($customer)->get(route('member.card'))->assertOk();
    }

    public function test_admin_can_create_and_update_book_and_stock_cannot_be_negative(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['nama' => 'Fiksi']);
        Storage::fake('public');

        // Stok minus ditolak
        $bookInput = [
            'category_id' => $category->id,
            'judul' => 'Buku Minus',
            'isbn' => '9786021234568',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2025,
            'harga' => 50000,
            'stok' => 10,
            'sinopsis' => 'Sinopsis buku.',
        ];
        $this->actingAs($admin)->post(route('books.store'), array_merge($bookInput, [
            'stok' => -5,
            'cover' => UploadedFile::fake()->image('cover-minus.jpg'),
        ]))->assertSessionHasErrors('stok');

        // Stok >= 0 berhasil
        $this->actingAs($admin)->post(route('books.store'), array_merge($bookInput, [
            'judul' => 'Buku Baru Toko',
            'isbn' => '978-623-95545-6-9',
            'tahun_terbit' => 2025,
            'harga' => 90000,
            'stok' => 10,
            'cover' => UploadedFile::fake()->image('cover-valid.jpg'),
        ]))->assertRedirect(route('books.index'));

        $book = Book::where('judul', 'Buku Baru Toko')->firstOrFail();
        $this->assertSame(10, $book->stok);
        $this->assertSame(90000, $book->harga);
        $this->assertSame('978-623-95545-6-9', $book->isbn);
        $this->assertNull($book->call_number);
    }

    public function test_admin_book_creation_requires_every_field_and_a_thirteen_digit_isbn(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('books.store'), [])
            ->assertSessionHasErrors([
                'category_id',
                'judul',
                'isbn',
                'penulis',
                'penerbit',
                'tahun_terbit',
                'harga',
                'stok',
                'sinopsis',
                'cover',
            ]);

        $category = Category::create(['nama' => 'Referensi']);
        Storage::fake('public');
        $bookInput = [
            'category_id' => $category->id,
            'judul' => 'Buku ISBN Uji',
            'isbn' => '978602123456',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2025,
            'harga' => 50000,
            'stok' => 1,
            'sinopsis' => 'Sinopsis buku.',
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ];

        $this->actingAs($admin)
            ->post(route('books.store'), $bookInput)
            ->assertSessionHasErrors('isbn');

        $this->assertDatabaseMissing('books', ['judul' => 'Buku ISBN Uji']);

        $bookInput['isbn'] = '97860212345678';
        $this->actingAs($admin)
            ->post(route('books.store'), $bookInput)
            ->assertSessionHasErrors('isbn');
    }

    public function test_admin_can_delete_a_book_only_when_stock_is_zero(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['nama' => 'Sains']);
        $book = Book::create([
            'category_id' => $category->id,
            'judul' => 'Buku Bersedia Dihapus',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2025,
            'harga' => 50000,
            'stok' => 1,
            'total_koleksi' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('<i class="bi bi-trash3"></i>', false)
            ->assertSee('aria-label="Hapus buku dinonaktifkan"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('data-delete-blocked-reason="Stok masih tersedia (1 eksemplar)."', false)
            ->assertSee('aria-label="Hapus kategori dinonaktifkan"', false)
            ->assertDontSee('<i class="bi bi-lock-fill', false);

        $this->actingAs($admin)
            ->delete(route('books.destroy', $book))
            ->assertSessionHasErrors('book');

        $this->assertModelExists($book);

        $book->update(['stok' => 0]);

        $this->actingAs($admin)
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'));

        $this->assertModelMissing($book);
    }

    public function test_blocked_book_delete_action_has_a_toast_reason_for_stock_and_cart(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);
        $category = Category::create(['nama' => 'Novel']);
        Book::create([
            'category_id' => $category->id,
            'judul' => 'Buku dengan Stok',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2025,
            'harga' => 50000,
            'stok' => 2,
            'total_koleksi' => 2,
        ]);
        $bookInCart = Book::create([
            'category_id' => $category->id,
            'judul' => 'Buku di Keranjang',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2025,
            'harga' => 50000,
            'stok' => 0,
            'total_koleksi' => 0,
        ]);
        Cart::create([
            'user_id' => $customer->id,
            'book_id' => $bookInCart->id,
            'jumlah' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('id="book-delete-blocked-toast"', false)
            ->assertSee('data-delete-blocked-reason="Stok masih tersedia (2 eksemplar)."', false)
            ->assertSee('data-delete-blocked-reason="Buku masih ada di keranjang pelanggan."', false)
            ->assertDontSeeText('Stok masih tersedia (2 eksemplar).')
            ->assertDontSeeText('Buku masih ada di keranjang pelanggan.');
    }

    public function test_cart_items_are_paginated(): void
    {
        $customer = User::factory()->create(['role' => 'user']);

        foreach (range(1, 11) as $bookNumber) {
            $book = Book::create([
                'judul' => 'Buku Keranjang '.$bookNumber,
                'penulis' => 'Penulis',
                'penerbit' => 'Penerbit',
                'tahun_terbit' => 2025,
                'harga' => 50000,
                'stok' => 2,
                'total_koleksi' => 2,
            ]);
            Cart::create([
                'user_id' => $customer->id,
                'book_id' => $book->id,
                'jumlah' => 1,
            ]);
        }

        $this->actingAs($customer)
            ->get(route('cart.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Buku Keranjang 1', false)
            ->assertDontSee('Buku Keranjang 11', false);
    }

    public function test_admin_transaction_and_report_lists_are_paginated_independently(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 16) as $rowNumber) {
            $customer = User::factory()->create(['role' => 'user']);
            Transaction::create([
                'kode_transaksi' => sprintf('TRX-PAGE-%02d', $rowNumber),
                'user_id' => $customer->id,
                'tanggal_transaksi' => now()->startOfMonth()->addDays($rowNumber - 1)->toDateString(),
                'total_item' => 1,
                'total_harga' => 50000,
                'metode_pembayaran' => 'QRIS',
                'status' => Transaction::STATUS_MENUNGGU,
            ]);

            Book::create([
                'judul' => 'Inventaris '.$rowNumber,
                'penulis' => 'Penulis',
                'penerbit' => 'Penerbit',
                'tahun_terbit' => 2025,
                'harga' => 50000,
                'stok' => $rowNumber,
                'total_koleksi' => $rowNumber,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('transactions.index', ['pending_page' => 2]))
            ->assertOk()
            ->assertSee('TRX-PAGE-06')
            ->assertDontSee('TRX-PAGE-16');

        $this->actingAs($admin)
            ->get(route('reports.index', ['transactions_page' => 2, 'books_page' => 2]))
            ->assertOk()
            ->assertSee('TRX-PAGE-01')
            ->assertSee('Inventaris 16')
            ->assertSee('Rp 800.000');
    }

    public function test_admin_dashboard_stock_and_transaction_lists_are_paginated_independently(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);

        foreach (range(1, 6) as $rowNumber) {
            Book::create([
                'judul' => 'Buku Dashboard '.$rowNumber,
                'penulis' => 'Penulis',
                'penerbit' => 'Penerbit',
                'tahun_terbit' => 2025,
                'harga' => 50000,
                'stok' => min($rowNumber - 1, 5),
                'total_koleksi' => min($rowNumber - 1, 5),
            ]);

            $transaction = Transaction::create([
                'kode_transaksi' => sprintf('DASH-ADMIN-%02d', $rowNumber),
                'user_id' => $customer->id,
                'tanggal_transaksi' => today()->subDays(7 - $rowNumber),
                'total_item' => 1,
                'total_harga' => 50000,
                'metode_pembayaran' => 'QRIS',
                'status' => Transaction::STATUS_SELESAI,
            ]);
            $transaction->forceFill(['created_at' => now()->subDays($rowNumber)])->save();
        }

        $this->actingAs($admin)
            ->get(route('dashboard', [
                'low_stock_page' => 2,
                'recent_transactions_page' => 2,
            ]))
            ->assertOk()
            ->assertSeeText('Menampilkan 6–6 dari 6 data')
            ->assertSee('Buku Dashboard 6')
            ->assertDontSee('Buku Dashboard 5')
            ->assertSee('DASH-ADMIN-06')
            ->assertDontSee('DASH-ADMIN-05');
    }

    public function test_customer_dashboard_transactions_are_paginated(): void
    {
        $customer = User::factory()->create(['role' => 'user']);

        foreach (range(1, 6) as $rowNumber) {
            $transaction = Transaction::create([
                'kode_transaksi' => sprintf('DASH-USER-%02d', $rowNumber),
                'user_id' => $customer->id,
                'tanggal_transaksi' => today()->subDays($rowNumber),
                'total_item' => 1,
                'total_harga' => 50000,
                'metode_pembayaran' => 'QRIS',
                'status' => Transaction::STATUS_SELESAI,
            ]);
            $transaction->forceFill(['created_at' => now()->subDays($rowNumber)])->save();
        }

        $this->actingAs($customer)
            ->get(route('dashboard', ['recent_transactions_page' => 2]))
            ->assertOk()
            ->assertSeeText('Menampilkan 6–6 dari 6 data')
            ->assertSee('DASH-USER-06')
            ->assertDontSee('DASH-USER-05');
    }

    public function test_logout_confirmation_is_shown_for_admin_and_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('logoutConfirmationModal')
            ->assertSee('Yakin ingin logout dari akun ini?')
            ->assertSee('Ya, Logout');

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('logoutConfirmationModal')
            ->assertSee('Yakin ingin logout dari akun ini?')
            ->assertSee('Ya, Logout');
    }

    public function test_cancelling_pending_transaction_restores_book_stock(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $book = Book::create([
            'judul' => 'Buku Batal Beli',
            'penulis' => 'Penulis',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2025,
            'harga' => 60000,
            'stok' => 5,
            'total_koleksi' => 5,
        ]);

        // Beli 2 eksemplar -> stok menjadi 3
        $this->actingAs($customer)->post(route('transactions.store', $book), [
            'jumlah' => 2,
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jl. Sudirman No. 1',
        ])->assertRedirect(route('transactions.index'));

        $this->assertSame(3, $book->fresh()->stok);

        // Batalkan pesanan -> stok kembali menjadi 5
        $trx = Transaction::latest('id')->firstOrFail();
        $this->actingAs($customer)->post(route('transactions.cancel', $trx))
            ->assertRedirect(route('transactions.index'));

        $this->assertSame(5, $book->fresh()->stok);
        $this->assertSame(Transaction::STATUS_DIBATALKAN, $trx->fresh()->status);
    }
}
