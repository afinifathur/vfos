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
        Schema::table('accounts', function (Blueprint $table) {
            // Update account_number to be VARCHAR(20) and nullable.
            // Using change() requires Laravel 10+ for native support.
            $table->string('account_number', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // As per user requirement: Do NOT rollback VARCHAR(20) back to 255.
            // We keep it as is.
        });
    }
};
