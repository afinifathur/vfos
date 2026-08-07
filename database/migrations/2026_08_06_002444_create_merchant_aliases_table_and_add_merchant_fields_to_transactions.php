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
        Schema::create('merchant_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_key')->unique();
            $table->string('raw_merchant');
            $table->string('display_name');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subcategory_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('usage_count')->default(1);
            $table->integer('auto_categorized_count')->default(0);
            $table->decimal('confidence', 5, 2)->default(1.0);
            $table->boolean('is_locked')->default(false);
            $table->foreignId('last_transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('merchant_key')->nullable()->index()->after('notes');
            $table->string('raw_merchant')->nullable()->after('merchant_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['merchant_key', 'raw_merchant']);
        });

        Schema::dropIfExists('merchant_aliases');
    }
};
