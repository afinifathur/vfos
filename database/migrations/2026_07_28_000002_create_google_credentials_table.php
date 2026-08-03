<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores OAuth tokens for connected Google accounts.
     *
     * NOTE: client_id and client_secret are intentionally NOT stored here.
     * OAuth application credentials live in app_settings (application-level).
     * This table stores per-user, per-provider OAuth tokens only.
     *
     * One active credential per (user_id, provider) combination.
     * Prepared for future providers: gmail, drive, calendar.
     */
    public function up(): void
    {
        Schema::create('google_credentials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('The vFOS user who authorized this Google account');

            $table->string('provider', 50)
                ->comment('Google API provider scope: gmail | drive | calendar');

            $table->string('google_email', 191)
                ->nullable()
                ->comment('The Google account email address (from userinfo)');

            // Sensitive OAuth tokens — stored encrypted via Laravel Crypt
            $table->text('refresh_token')
                ->nullable()
                ->comment('Long-lived refresh token (encrypted). Null if not yet obtained.');

            $table->text('access_token')
                ->nullable()
                ->comment('Short-lived access token (encrypted). Refreshed automatically.');

            $table->timestamp('expires_at')
                ->nullable()
                ->comment('When the current access_token expires');

            $table->json('scopes')
                ->nullable()
                ->comment('JSON array of granted OAuth scopes');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Soft-disable without deleting. False = disconnected.');

            $table->timestamp('last_connected_at')
                ->nullable()
                ->comment('Timestamp of the last successful token exchange or refresh');

            $table->timestamps();

            // Enforce one active credential per provider per user
            $table->unique(['user_id', 'provider'], 'uq_google_cred_user_provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_credentials');
    }
};
