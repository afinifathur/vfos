<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Investment;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Investment::where('id', 7)
            ->where('name', 'TRIM Kas 2 Kelas A')
            ->update([
                'scraping_url' => 'https://pusatdata.kontan.co.id/reksadana/produk/16585/Reksa-Dana-TRIM-Kas-2-Kelas-A'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Investment::where('id', 7)
            ->where('name', 'TRIM Kas 2 Kelas A')
            ->update([
                'scraping_url' => 'https://pusatdata.kontan.co.id/reksadana/produk/1040/TRIM-Kas-2-Kelas-A'
            ]);
    }
};
