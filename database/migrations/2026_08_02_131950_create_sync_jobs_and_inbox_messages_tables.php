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
        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('connector'); // e.g. gmail
            $table->string('query')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('total_found')->default(0);
            $table->integer('downloaded')->default(0);
            $table->integer('skipped')->default(0);
            $table->integer('failed')->default(0);
            $table->string('status'); // PENDING, SUCCESS, FAILED, RUNNING
            $table->integer('duration_ms')->default(0);
            $table->timestamps();
        });

        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sync_job_id')->nullable()->constrained('sync_jobs')->onDelete('set null');
            $table->string('connector')->default('gmail');
            $table->string('provider')->nullable(); // Determined during parser phase, initially null
            $table->string('google_message_id');
            $table->string('google_thread_id')->nullable();
            $table->string('history_id')->nullable();
            $table->unsignedBigInteger('internal_date')->nullable();
            $table->string('subject')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->text('snippet')->nullable();
            $table->mediumText('html_body')->nullable();
            $table->mediumText('plain_body')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('labels')->nullable();
            $table->string('checksum')->nullable(); // SHA256 of raw payload
            $table->string('status')->default('NEW'); // NEW, SYNCED, PARSED, FAILED, IGNORED, ARCHIVED
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            // Prevent duplicate records for the same user and message ID
            $table->unique(['user_id', 'google_message_id']);
            
            // Add indexes for performance
            $table->index('internal_date');
            $table->index('status');
            $table->index('connector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
        Schema::dropIfExists('sync_jobs');
    }
};
