<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop old FK constraint
            $table->dropForeign(['account_id']);
            // Make nullable and re-add with SET NULL on delete
            $table->unsignedBigInteger('account_id')->nullable()->change();
            $table->foreign('account_id')
                  ->references('id')->on('accounts')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->unsignedBigInteger('account_id')->nullable(false)->change();
            $table->foreign('account_id')->references('id')->on('accounts');
        });
    }
};
