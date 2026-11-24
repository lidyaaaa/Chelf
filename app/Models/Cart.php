<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
        ];
    }

    /**
     * Relasi item keranjang dimiliki oleh satu User (Pelanggan).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi item keranjang merujuk ke satu Buku di katalog.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Accessor ($cart->subtotal) untuk menghitung harga total per baris keranjang
     * secara otomatis: jumlah buku x harga satuan buku.
     */
    public function getSubtotalAttribute(): int
    {
        return (int) $this->jumlah * (int) ($this->book->harga ?? 0);
    }
}
