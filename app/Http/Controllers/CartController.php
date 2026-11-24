<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Cart;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CartController extends Controller
{
    // Jeda cooldown per buku (2 detik) untuk mencegah double-click tidak sengaja saat klik "+ Keranjang"
    public const COOLDOWN_SECONDS = 2;

    // Batas maksimal penambahan ke keranjang per menit per user (anti-spam)
    public const MAX_ADDS_PER_MINUTE = 20;

    /**
     * Menampilkan halaman keranjang belanja user beserta daftar alamat pengiriman tersimpan.
     */
    public function index()
    {
        // Pastikan tabel alamat tersedia sebelum mengambil daftar alamat user
        UserAddress::ensureTableExists();

        $cartItems = Cart::with('book.category')
            ->where('user_id', auth()->id())
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $addresses = UserAddress::where('user_id', auth()->id())
            ->orderByDesc('is_utama')
            ->latest()
            ->get();

        // Hitung total eksemplar dan total harga awal dari item di halaman ini
        $totalItem = $cartItems->getCollection()->sum('jumlah');
        $totalHarga = $cartItems->getCollection()->sum(fn (Cart $item): int => $item->subtotal);

        return view('user.cart.index', compact('cartItems', 'addresses', 'totalItem', 'totalHarga'));
    }

    /**
     * Menambahkan buku ke dalam keranjang belanja user.
     * Catatan Penting: Stok buku TIDAK dikurangi saat masuk keranjang (hanya dicek batas stoknya).
     * Stok baru akan dipotong ketika user melakukan Checkout.
     */
    public function store(Request $request, Book $book): JsonResponse|RedirectResponse
    {
        $userId = (int) auth()->id();
        $burstKey = $this->burstKey($userId);
        $cooldownKey = $this->cooldownKey($userId, $book->id);

        // 1. Cek batas anti-spam per menit (maksimal 20 kali klik per menit)
        if (RateLimiter::tooManyAttempts($burstKey, self::MAX_ADDS_PER_MINUTE)) {
            $retryAfter = max(1, RateLimiter::availableIn($burstKey));
            $message = sprintf(
                'Terlalu banyak aktivitas menambahkan ke keranjang. Silakan tunggu %d detik lagi.',
                $retryAfter
            );

            return $this->cartRateLimitedResponse($request, $message, $retryAfter);
        }

        // 2. Cek cooldown 2 detik untuk buku yang sama agar double-click tidak menambah 2x lipat
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $retryAfter = max(1, RateLimiter::availableIn($cooldownKey));
            $message = sprintf(
                'Mohon tunggu %d detik sebelum menambahkan "%s" ke keranjang lagi.',
                $retryAfter,
                $book->judul
            );

            return $this->cartRateLimitedResponse($request, $message, $retryAfter);
        }

        $data = $request->validate([
            'jumlah' => 'nullable|integer|min:1',
        ], [
            'jumlah.min' => 'Jumlah pembelian minimal 1 eksemplar.',
        ]);

        $qtyToAdd = (int) ($data['jumlah'] ?? 1);

        // 3. Pastikan stok buku masih tersedia
        if ($book->stok < 1) {
            $errorMsg = sprintf('Maaf, stok buku "%s" sedang habis.', $book->judul);

            if ($request->expectsJson()) {
                return response()->json(['message' => $errorMsg], 422);
            }

            return back()->withErrors([
                'cart' => $errorMsg,
            ]);
        }

        // 4. Cek apakah buku ini sudah ada di keranjang user sebelumnya
        $existingCart = Cart::where('user_id', $userId)
            ->where('book_id', $book->id)
            ->first();

        $newQty = ($existingCart ? $existingCart->jumlah : 0) + $qtyToAdd;

        // 5. Pastikan total jumlah di keranjang tidak melampaui sisa stok buku di gudang
        if ($newQty > $book->stok) {
            $errorMsg = sprintf(
                'Tidak dapat menambahkan ke keranjang. Stok buku "%s" hanya tersisa %d eksemplar (di keranjang Anda: %d).',
                $book->judul,
                $book->stok,
                $existingCart ? $existingCart->jumlah : 0
            );

            if ($request->expectsJson()) {
                return response()->json(['message' => $errorMsg, 'max_stok' => $book->stok], 422);
            }

            return back()->withErrors([
                'cart' => $errorMsg,
            ]);
        }

        // 6. Simpan atau tambahkan jumlahnya ke baris keranjang user
        Cart::updateOrCreate(
            [
                'user_id' => $userId,
                'book_id' => $book->id,
            ],
            [
                'jumlah' => $newQty,
            ]
        );

        // Catat hit ke RateLimiter untuk aktifkan cooldown 2 detik
        RateLimiter::hit($cooldownKey, self::COOLDOWN_SECONDS);
        RateLimiter::hit($burstKey, 60);

        $successMessage = sprintf('Buku "%s" berhasil ditambahkan ke keranjang belanja.', $book->judul);
        // Jika tombol yang diklik adalah "Beli Sekarang" (redirect_cart = 1), arahkan langsung ke halaman keranjang
        $redirectToCart = $request->boolean('redirect_cart');

        if ($request->expectsJson()) {
            $cartCount = (int) auth()->user()->carts()->sum('jumlah');

            return response()->json([
                'message' => $successMessage,
                'cart_count' => $cartCount,
                'cooldown_seconds' => self::COOLDOWN_SECONDS,
                'redirect_url' => $redirectToCart ? route('cart.index') : null,
            ]);
        }

        if ($redirectToCart) {
            return redirect()
                ->route('cart.index')
                ->with('success', $successMessage);
        }

        return back()->with('success', $successMessage);
    }

    /**
     * Mengubah jumlah (kuantitas) buku di dalam keranjang (baik saat diketik maupun klik tombol - / +).
     */
    public function update(Request $request, Cart $cart)
    {
        // Validasi kepemilikan: pastikan keranjang ini milik user yang sedang login
        abort_unless($cart->user_id === auth()->id(), 403);

        $data = $request->validate([
            'jumlah' => 'required|integer|min:1',
        ], [
            'jumlah.min' => 'Jumlah buku di keranjang minimal 1.',
        ]);

        $book = $cart->book;
        if (! $book || $book->stok < 1) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Stok buku ini sedang habis.'], 422);
            }

            return back()->withErrors([
                'cart' => 'Stok buku ini sedang habis.',
            ]);
        }

        $newQty = (int) $data['jumlah'];
        if ($newQty > $book->stok) {
            $msg = sprintf(
                'Jumlah melebihi stok tersedia. Stok buku "%s" saat ini hanya %d eksemplar.',
                $book->judul,
                $book->stok
            );

            if ($request->expectsJson()) {
                return response()->json(['message' => $msg, 'max_stok' => $book->stok], 422);
            }

            return back()->withErrors([
                'cart' => $msg,
            ]);
        }

        $cart->update([
            'jumlah' => $newQty,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'jumlah' => $cart->jumlah,
                'subtotal' => $cart->subtotal,
                'max_stok' => $book->stok,
                'cart_count' => (int) auth()->user()->carts()->sum('jumlah'),
            ]);
        }

        return back()->with('success', sprintf('Jumlah buku "%s" di keranjang diperbarui.', $book->judul));
    }

    /**
     * Menghapus satu item buku dari keranjang belanja.
     */
    public function destroy(Cart $cart)
    {
        abort_unless($cart->user_id === auth()->id(), 403);

        $judul = $cart->book->judul ?? 'Buku';
        $cart->delete();

        return back()->with('success', sprintf('"%s" telah dihapus dari keranjang belanja.', $judul));
    }

    private function cartRateLimitedResponse(Request $request, string $message, int $retryAfter): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'retry_after' => $retryAfter,
            ], 429, [
                'Retry-After' => (string) $retryAfter,
            ]);
        }

        return back()->withErrors([
            'cart' => $message,
        ]);
    }

    private function cooldownKey(int $userId, int $bookId): string
    {
        return 'cart-store-cooldown:'.$userId.':'.$bookId;
    }

    private function burstKey(int $userId): string
    {
        return 'cart-store-burst:'.$userId;
    }
}
