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
        Schema::table('inbox_messages', function (Blueprint $table) {
            $table->foreignId('transaction_id')->nullable()->after('sync_job_id')->constrained('transactions')->onDelete('set null');
            $table->timestamp('imported_at')->nullable()->after('parsed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbox_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaction_id');
            $table->dropColumn('imported_at');
        });
    }
};
