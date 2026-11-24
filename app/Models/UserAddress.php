<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'penerima',
        'telepon',
        'alamat_lengkap',
        'is_utama',
    ];

    protected function casts(): array
    {
        return [
            'is_utama' => 'boolean',
        ];
    }

    /**
     * Memastikan tabel 'user_addresses' sudah tersedia di database.
     * Jika belum ada, dibuatkan secara otomatis agar fitur alamat pengiriman tidak error.
     */
    public static function ensureTableExists(): void
    {
        if (! Schema::hasTable('user_addresses')) {
            Schema::create('user_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('label', 50)->default('Rumah');
                $table->string('penerima', 100);
                $table->string('telepon', 30);
                $table->text('alamat_lengkap');
                $table->boolean('is_utama')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Relasi alamat pengiriman dimiliki oleh satu User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor ($address->formatted_address) untuk menyatukan label, nama penerima,
     * nomor telepon, dan alamat lengkap menjadi satu teks siap cetak di struk transaksi.
     */
    public function getFormattedAddressAttribute(): string
    {
        return sprintf(
            '[%s] %s (%s) — %s',
            $this->label,
            $this->penerima,
            $this->telepon,
            $this->alamat_lengkap
        );
    }
}
