<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Transaction;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    /**
     * Halaman Katalog Bookstore (Publik, Pelanggan, dan Admin).
     * Mendukung:
     * - Pencarian cepat (Judul, Penulis, Penerbit, ISBN, Nama Kategori, Kode Rak/DDC)
     * - Filter kategori & ketersediaan stok (Tersedia / Habis)
     * - Pengurutan (Terbaru, Harga Termurah, Harga Termahal, A-Z)
     * - Live Search AJAX (mengembalikan JSON partial HTML jika diminta via fetch)
     */
    public function index(Request $request)
    {
        // Eager loading relasi category, jumlah di keranjang, dan total eksemplar terjual
        $query = Book::query()
            ->with('category')
            ->withCount('carts')
            ->withSum(['transactionItems as terjual_count' => function ($q): void {
                // Hanya hitung transaksi yang tidak dibatalkan
                $q->whereHas('transaction', fn ($tq) => $tq->where('status', '!=', Transaction::STATUS_DIBATALKAN));
            }], 'jumlah')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->search);
                $query->where(function ($q) use ($search): void {
                    $q->where('judul', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('penerbit', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($catQuery) use ($search): void {
                            $catQuery->where('nama', 'like', "%{$search}%")
                                ->orWhere('kode_ddc', 'like', "%{$search}%");
                        });
                });
            })

            ->when($request->filled('category_id'), function ($query) use ($request): void {
                $query->where('category_id', $request->category_id);
            })
            ->when($request->availability === 'available', function ($query): void {
                $query->where('stok', '>', 0);
            })
            ->when($request->availability === 'out_of_stock', function ($query): void {
                $query->where('stok', '<=', 0);
            });

        // Pengurutan katalog berdasarkan pilihan dropdown sort
        if ($request->sort === 'termurah') {
            $query->orderBy('harga', 'asc');
        } elseif ($request->sort === 'termahal') {
            $query->orderBy('harga', 'desc');
        } elseif ($request->sort === 'az') {
            $query->orderBy('judul', 'asc');
        } else {
            $query->latest()->latest('id');
        }

        // Pengunjung tamu (belum login) dibatasi preview 8 buku halaman pertama,
        // sedangkan user yang sudah login mendapatkan pagination penuh.
        if (! auth()->check()) {
            $guestBooks = $query->limit(8)->get();
            $books = new LengthAwarePaginator(
                $guestBooks,
                $guestBooks->count(),
                8,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $books = $query
                ->paginate(8)
                ->onEachSide(1)
                ->withQueryString();
        }

        // Jika request berasal dari Live Search AJAX (fetch dengan header Accept: application/json),
        // kembalikan potongan HTML kartu buku beserta total judul.
        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('books.partials.results', compact('books'))->render(),
                'total' => $books->total(),
            ]);
        }

        $categories = Category::withCount('books')->orderBy('nama')->get();

        return view('books.index', compact('books', 'categories'));
    }

    /**
     * Menampilkan form tambah buku baru (Khusus Admin).
     */
    public function create()
    {
        $categories = Category::withCount('books')->orderBy('nama')->get();

        return view('admin.books.create', compact('categories'));
    }

    /**
     * Menyimpan buku baru ke database beserta upload gambar sampul (Khusus Admin).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'kategori_baru' => 'nullable|string|max:25',
            'judul' => 'required|string|max:100|unique:books,judul',
            'isbn' => [
                'bail',
                'required',
                'string',
                'max:17',
                'regex:/^[0-9]+(?:-[0-9]+)*$/',
                // Validasi custom: normalisasi ISBN dengan menghapus tanda strip (-)
                // agar ISBN yang angkanya sama tetap terdeteksi duplikat meskipun beda posisi strip
                function (string $attribute, mixed $value, Closure $fail): void {
                    $normalizedIsbn = (string) preg_replace('/\D/', '', (string) $value);

                    $isDuplicate = Book::query()
                        ->whereRaw("REPLACE(REPLACE(isbn, '-', ''), ' ', '') = ?", [$normalizedIsbn])
                        ->exists();

                    if ($isDuplicate) {
                        $fail('Buku dengan nomor ISBN ini sudah terdaftar di database.');
                    }
                },
            ],
            'penulis' => 'required|string|max:60',
            'penerbit' => 'required|string|max:60',
            'tahun_terbit' => [
                'required',
                'integer',
                'min:1000',
                'max:'.date('Y'),
            ],
            'harga' => 'required|integer|min:0',
            'stok' => 'required|integer|min:0',
            'sinopsis' => 'required|string|max:1000',
            'cover' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'judul.unique' => 'Buku dengan judul ini sudah ada di dalam katalog/database.',
            'isbn.regex' => 'Nomor ISBN hanya boleh berisi angka dan tanda hubung.',
            'stok.min' => 'Stok buku tidak boleh bernilai minus (minimal 0).',
            'harga.min' => 'Harga buku tidak boleh bernilai minus.',
        ]);

        // Jika admin mengisi input 'kategori_baru', otomatis buat kategori tersebut
        if ($request->filled('kategori_baru')) {
            $newCat = Category::firstOrCreate([
                'nama' => trim((string) $request->kategori_baru),
            ]);
            $data['category_id'] = $newCat->id;
        }
        unset($data['kategori_baru']);

        // Pastikan stok minimal 0 (tidak boleh negatif)
        $data['stok'] = max(0, (int) $data['stok']);
        $data['total_koleksi'] = $data['stok'];
        $data['penerbit'] = $data['penerbit'] ?? '-';
        $data['tahun_terbit'] = $data['tahun_terbit'] ?? date('Y');

        // Simpan file gambar cover ke storage/app/public/covers
        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('covers', 'public');
        }

        Book::create($data);

        return redirect()
            ->route('books.index')
            ->with('success', 'Buku baru berhasil ditambahkan ke katalog toko.');
    }

    /**
     * Menampilkan form edit data buku (Khusus Admin).
     */
    public function edit(Book $book)
    {
        $categories = Category::withCount('books')->orderBy('nama')->get();

        return view('admin.books.edit', compact('book', 'categories'));
    }

    /**
     * Memperbarui data buku yang sudah ada (Khusus Admin).
     */
    public function update(Request $request, Book $book)
    {
        // Pada validasi unique judul & isbn, sertakan ID buku saat ini (.$book->id)
        // agar tidak dianggap duplikat dengan dirinya sendiri saat disimpan.
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'kategori_baru' => 'nullable|string|max:25',
            'judul' => 'required|string|max:100|unique:books,judul,'.$book->id,
            'isbn' => 'nullable|string|max:17|unique:books,isbn,'.$book->id,
            'penulis' => 'required|string|max:60',
            'penerbit' => 'nullable|string|max:60',
            'tahun_terbit' => [
                'nullable',
                'integer',
                'min:1000',
                'max:'.date('Y'),
            ],
            'harga' => 'required|integer|min:0',
            'stok' => 'required|integer|min:0',
            'sinopsis' => 'nullable|string|max:1000',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'judul.unique' => 'Buku dengan judul ini sudah ada di dalam katalog/database.',
            'isbn.unique' => 'Buku dengan nomor ISBN ini sudah terdaftar di database.',
            'stok.min' => 'Stok buku tidak boleh bernilai minus (minimal 0).',
            'harga.min' => 'Harga buku tidak boleh bernilai minus.',
        ]);

        if ($request->filled('kategori_baru')) {
            $newCat = Category::firstOrCreate([
                'nama' => trim((string) $request->kategori_baru),
            ]);
            $data['category_id'] = $newCat->id;
        }
        unset($data['kategori_baru']);

        $data['stok'] = max(0, (int) $data['stok']);
        // Hitung ulang total_koleksi = stok tersedia saat ini + jumlah buku yang sudah terjual
        $soldCount = (int) $book->transactionItems()
            ->whereHas('transaction', fn ($q) => $q->where('status', '!=', Transaction::STATUS_DIBATALKAN))
            ->sum('jumlah');
        $data['total_koleksi'] = $data['stok'] + $soldCount;
        $data['penerbit'] = $data['penerbit'] ?? '-';
        $data['tahun_terbit'] = $data['tahun_terbit'] ?? date('Y');

        // Jika admin mengupload cover baru, hapus file cover lama dari storage agar tidak menumpuk
        if ($request->hasFile('cover')) {
            if ($book->cover) {
                Storage::disk('public')->delete($book->cover);
            }

            $data['cover'] = $request->file('cover')->store('covers', 'public');
        }

        $book->update($data);

        return redirect()
            ->route('books.index')
            ->with('success', 'Data buku berhasil diperbarui.');
    }

    /**
     * Menghapus buku dari katalog (Khusus Admin).
     * Aturan Referential Integrity:
     * 1. Buku TIDAK BISA dihapus jika stoknya masih ada (stok > 0).
     * 2. Buku TIDAK BISA dihapus jika sedang berada di dalam keranjang belanja pelanggan.
     */
    public function destroy(Book $book)
    {
        if ($book->stok > 0) {
            return back()->withErrors([
                'book' => sprintf(
                    'Buku "%s" tidak dapat dihapus karena masih memiliki stok sebanyak %d eksemplar.',
                    $book->judul,
                    $book->stok
                ),
            ]);
        }

        if ($book->isInAnyCart()) {
            return back()->withErrors([
                'book' => sprintf(
                    'Buku "%s" tidak dapat dihapus karena sedang berada di dalam keranjang belanja pengguna.',
                    $book->judul
                ),
            ]);
        }

        $judul = $book->judul;

        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }

        $book->transactionItems()->delete();
        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', "Buku \"{$judul}\" berhasil dihapus dari katalog.");
    }
}
