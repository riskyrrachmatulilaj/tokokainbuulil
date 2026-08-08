<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite tidak mendukung ALTER COLUMN pada enum.
     * Kolom payment_method sudah berupa string di SQLite,
     * jadi kita hanya perlu mengubah tipe ke string biasa
     * agar menerima value 'transfer'.
     *
     * Untuk MySQL, enum juga diganti ke string agar fleksibel.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_method', 20)->default('cash')->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_method', 20)->default('cash')->change();
        });
    }
};
