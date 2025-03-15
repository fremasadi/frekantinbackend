<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableNumber extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model ini.
     *
     * @var string
     */
    protected $table = 'table_numbers';

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'number',
        'status',
    ];

    /**
     * Atribut yang harus dikonversi ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Mendapatkan semua meja yang tersedia.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function available()
    {
        return self::where('status', true);
    }

    /**
     * Mendapatkan semua meja yang tidak tersedia.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function unavailable()
    {
        return self::where('status', false);
    }

    /**
     * Mengubah status meja menjadi tersedia.
     *
     * @return bool
     */
    public function setAvailable()
    {
        return $this->update(['status' => true]);
    }

    /**
     * Mengubah status meja menjadi tidak tersedia.
     *
     * @return bool
     */
    public function setUnavailable()
    {
        return $this->update(['status' => false]);
    }
}