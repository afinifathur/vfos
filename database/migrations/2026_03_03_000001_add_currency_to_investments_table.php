<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->string('currency', 10)->default('IDR')->after('scraping_url');
            $table->string('price_unit', 20)->default('unit')->after('currency');
            // price_unit: 'unit' (default), 'gram', 'troy_oz'
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn(['currency', 'price_unit']);
        });
    }
};
