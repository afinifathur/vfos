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
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('owner', ['afin', 'pacar', 'business'])->default('afin')->after('user_id');
        });
        Schema::table('budgets', function (Blueprint $table) {
            $table->enum('owner', ['afin', 'pacar', 'business'])->default('afin')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('owner');
        });
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn('owner');
        });
    }
};
