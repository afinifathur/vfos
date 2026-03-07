<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
            $table->decimal('average_cost', 15, 2)->change();
            $table->decimal('current_price', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->decimal('quantity', 15, 6)->change();
            $table->decimal('average_cost', 15, 2)->change();
            $table->decimal('current_price', 15, 6)->change();
        });
    }
};
