<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Application-level key-value settings store.
     * Stores configuration that belongs to the application installation,
     * not to a specific user — e.g. Google OAuth Client ID & Secret.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Setting key, e.g. google_client_id');
            $table->text('value')->nullable()->comment('Setting value; encrypted keys are stored as ciphertext');
            $table->string('group', 50)->default('general')->comment('Logical group: google, mail, app, etc.');
            $table->boolean('is_encrypted')->default(false)->comment('Whether the value column is encrypted via Laravel Crypt');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
