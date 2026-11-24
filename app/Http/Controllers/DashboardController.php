<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Cart;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Halaman Dashboard utama setelah login.
     * Memisahkan tampilan Dashboard Admin (statistik omzet, stok menipis, transaksi terbaru)
     * dan Dashboard Pelanggan (ringkasan keranjang, total belanja, kartu member digital).
     */
    public function index(Request $request)
    {
        Transaction::syncPendingTransactions();

        $user = auth()->user();

        if ($user->role === 'admin') {
            return $this->adminDashboard($request);
        }

        return $this->userDashboard($request, $user);
    }

    /**
     * Menyusun statistik dan tabel ringkasan untuk Dashboard Admin.
     */
    protected function adminDashboard(Request $request)
    {
        $month = $request->filled('month') ? (int) $request->month : null;
        $year = $request->filled('year') ? (int) $request->year : null;

        $applyDateFilter = function ($q) use ($month, $year): void {
            if ($month) {
                $q->whereMonth('tanggal_transaksi', $month);
            }
            if ($year) {
                $q->whereYear('tanggal_transaksi', $year);
            }
        };

        $stats = [
            'total_judul' => Book::count(),
            'stok_tersedia' => (int) Book::sum('stok'),
            'buku_terjual' => (int) TransactionItem::whereHas('transaction', function ($q) use ($applyDateFilter): void {
                $q->where('status', '!=', Transaction::STATUS_DIBATALKAN);
                $applyDateFilter($q);
            })->sum('jumlah'),
            'total_pendapatan' => (int) Transaction::where('status', '!=', Transaction::STATUS_DIBATALKAN)
                ->tap($applyDateFilter)
                ->sum('total_harga'),
            'menunggu_konfirmasi' => Transaction::where('status', Transaction::STATUS_MENUNGGU)
                ->tap($applyDateFilter)
                ->count(),
            'transaksi_selesai' => Transaction::where('status', Transaction::STATUS_SELESAI)
                ->tap($applyDateFilter)
                ->count(),
            'keranjang_aktif' => Cart::count(),
            'total_pelanggan' => User::where('role', 'user')->count(),
        ];

        $lowStockBooks = Book::with('category')
            ->where('stok', '<=', 5)
            ->orderBy('stok')
            ->orderBy('id')
            ->paginate(5, ['*'], 'low_stock_page')
            ->onEachSide(1)
            ->withQueryString();

        $recentTransactions = Transaction::with(['user', 'items.book'])
            ->tap($applyDateFilter)
            ->latest()
            ->latest('id')
            ->paginate(5, ['*'], 'recent_transactions_page')
            ->onEachSide(1)
            ->withQueryString();

        return view('admin.dashboard', compact('stats', 'lowStockBooks', 'recentTransactions', 'month', 'year'));
    }

    protected function userDashboard(Request $request, User $user)
    {
        $month = $request->filled('month') ? (int) $request->month : null;
        $year = $request->filled('year') ? (int) $request->year : null;

        $applyDateFilter = function ($q) use ($month, $year): void {
            if ($month) {
                $q->whereMonth('tanggal_transaksi', $month);
            }
            if ($year) {
                $q->whereYear('tanggal_transaksi', $year);
            }
        };

        $cartItemCount = (int) $user->carts()->sum('jumlah');
        $recentTransactions = $user->transactions()
            ->with('items.book')
            ->tap($applyDateFilter)
            ->latest()
            ->latest('id')
            ->paginate(5, ['*'], 'recent_transactions_page')
            ->onEachSide(1)
            ->withQueryString();
        $transactionCount = $user->transactions()
            ->tap($applyDateFilter)
            ->count();
        $totalBelanja = (int) $user->transactions()
            ->where('status', '!=', Transaction::STATUS_DIBATALKAN)
            ->tap($applyDateFilter)
            ->sum('total_harga');
        $accountInfo = $user->accountStatusInfo();

        return view('user.dashboard', compact(
            'user',
            'cartItemCount',
            'recentTransactions',
            'transactionCount',
            'totalBelanja',
            'accountInfo',
            'month',
            'year'
        ));
    }
}
