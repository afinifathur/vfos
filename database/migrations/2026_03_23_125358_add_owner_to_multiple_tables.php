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
        $tables = ['investments', 'goals', 'assets', 'debts', 'receivables'];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->enum('owner', ['afin', 'pacar', 'business'])->default('afin')->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['investments', 'goals', 'assets', 'debts', 'receivables'];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('owner');
            });
        }
    }
};
