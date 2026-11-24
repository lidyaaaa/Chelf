<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. ROUTE PUBLIK (Bisa diakses siapa saja: Tamu / User / Admin)
|--------------------------------------------------------------------------
*/
Route::get('/', [BookController::class, 'index'])->name('home');
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::view('/about', 'about')->name('about');

// Fallback route untuk menampilkan gambar sampul buku dari folder storage/app/public
// Berguna agar gambar tetap muncul meskipun symlink 'php artisan storage:link' belum dibuat di Windows
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/'.$path);
    abort_unless(is_file($fullPath), 404);

    return response()->file($fullPath);
})->where('path', '.*');

/*
|--------------------------------------------------------------------------
| 2. ROUTE TAMU / GUEST (Hanya untuk yang BELUM login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');
});

/*
|--------------------------------------------------------------------------
| 3. ROUTE TERAUTENTIKASI (Wajib sudah login: User maupun Admin)
|--------------------------------------------------------------------------
| Menggunakan middleware 'auth.forbidden' (RequireAuthenticatedUser) yang
| menolak akses dengan HTTP 401 jika belum login.
*/
Route::middleware('auth.forbidden')->group(function () {
    // Dashboard & Profil Akun
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/profil', [UserController::class, 'editProfile'])
        ->name('profile.edit');
    Route::put('/profil', [UserController::class, 'updateProfile'])
        ->name('profile.update');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
    Route::get('/kartu-member/{user?}', [ReportController::class, 'memberCard'])
        ->name('member.card');

    // Transaksi Pembelian, Pembatalan Pesanan, dan Cetak Struk
    Route::get('/transaksi', [TransactionController::class, 'index'])
        ->name('transactions.index');
    Route::post('/books/{book}/beli', [TransactionController::class, 'store'])
        ->name('transactions.store');
    Route::post('/transaksi/{transaction}/batal', [TransactionController::class, 'cancel'])
        ->name('transactions.cancel');
    Route::get('/transaksi/{transaction}/struk', [TransactionController::class, 'receipt'])
        ->name('transactions.receipt');

    // Fitur Live Chat antara Pelanggan dan Admin (AJAX Polling & Send)
    Route::get('/chat', [ChatController::class, 'index'])
        ->name('chat.index');
    Route::get('/chat/messages', [ChatController::class, 'messages'])
        ->name('chat.messages');
    Route::post('/chat', [ChatController::class, 'store'])
        ->name('chat.store');

    /*
    |----------------------------------------------------------------------
    | 3A. ROUTE KHUSUS PELANGGAN (role:user)
    |----------------------------------------------------------------------
    | Mengelola keranjang belanja, checkout pesanan, dan buku alamat pengiriman.
    */
    Route::middleware('role:user')->group(function () {
        Route::get('/keranjang', [CartController::class, 'index'])
            ->name('cart.index');
        Route::post('/keranjang/{book}', [CartController::class, 'store'])
            ->name('cart.store');
        Route::patch('/keranjang/{cart}', [CartController::class, 'update'])
            ->name('cart.update');
        Route::delete('/keranjang/{cart}', [CartController::class, 'destroy'])
            ->name('cart.destroy');
        Route::post('/checkout', [TransactionController::class, 'checkout'])
            ->name('transactions.checkout');
        Route::post('/alamat', [AddressController::class, 'store'])
            ->name('addresses.store');
        Route::put('/alamat/{address}', [AddressController::class, 'update'])
            ->name('addresses.update');
        Route::delete('/alamat/{address}', [AddressController::class, 'destroy'])
            ->name('addresses.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | 3B. ROUTE KHUSUS ADMIN TOKO (role:admin)
    |----------------------------------------------------------------------
    | Mengelola CRUD Buku, CRUD Kategori, Persetujuan Transaksi, Daftar Member, & Laporan CSV.
    */
    Route::middleware('role:admin')->group(function () {
        // CRUD Katalog Buku
        Route::get('/books/create', [BookController::class, 'create'])
            ->name('books.create');
        Route::post('/books', [BookController::class, 'store'])
            ->name('books.store');
        Route::get('/books/{book}/edit', [BookController::class, 'edit'])
            ->name('books.edit');
        Route::put('/books/{book}', [BookController::class, 'update'])
            ->name('books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])
            ->name('books.destroy');

        // CRUD Kategori Buku
        Route::post('/categories', [CategoryController::class, 'store'])
            ->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])
            ->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->name('categories.destroy');

        // Input Kasir & Konfirmasi / Setujui Pesanan Pelanggan
        Route::post('/transaksi/kasir', [TransactionController::class, 'storeByAdmin'])
            ->name('transactions.store-admin');
        Route::post('/transaksi/{transaction}/setujui', [TransactionController::class, 'approve'])
            ->name('transactions.approve');
        Route::post('/users/{user}/setujui-semua', [TransactionController::class, 'approveAll'])
            ->name('transactions.approve-all');

        // Manajemen Pelanggan & Laporan Penjualan (Web + Export CSV)
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');

        Route::get('/laporan', [ReportController::class, 'index'])
            ->name('reports.index');
        Route::get('/laporan/export-csv', [ReportController::class, 'exportCsv'])
            ->name('reports.export-csv');
    });
});
