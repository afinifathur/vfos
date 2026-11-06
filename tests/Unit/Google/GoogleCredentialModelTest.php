<?php

namespace Tests\Unit\Google;

use App\Models\GoogleCredential;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Pure unit tests for GoogleCredential model helper methods.
 * These tests do NOT touch the database.
 */
class GoogleCredentialModelTest extends TestCase
{
    public function test_is_expired_returns_true_when_expires_at_is_null(): void
    {
        $credential = new GoogleCredential();
        $credential->setRawAttributes(['expires_at' => null]);

        $this->assertTrue($credential->isExpired());
    }

    public function test_is_expired_returns_true_when_past_expiry_buffer(): void
    {
        $credential = new GoogleCredential();
        $credential->setRawAttributes([
            'expires_at' => Carbon::now()->subMinutes(10)->format('Y-m-d H:i:s'),
        ]);

        $this->assertTrue($credential->isExpired());
    }

    public function test_is_expired_returns_false_when_token_is_fresh(): void
    {
        $credential = new GoogleCredential();
        $credential->setRawAttributes([
            'expires_at' => Carbon::now()->addHour()->format('Y-m-d H:i:s'),
        ]);

        $this->assertFalse($credential->isExpired());
    }

    public function test_has_refresh_token_returns_false_when_null(): void
    {
        $credential = new GoogleCredential();
        $credential->setRawAttributes(['refresh_token' => null]);

        $this->assertFalse($credential->hasRefreshToken());
    }

    public function test_has_refresh_token_returns_false_when_empty_string(): void
    {
        $credential = new GoogleCredential();
        $credential->setRawAttributes(['refresh_token' => '']);

        $this->assertFalse($credential->hasRefreshToken());
    }

    public function test_status_label_returns_incomplete_without_refresh_token(): void
    {
        // A credential with no refresh_token should be 'Incomplete'
        $credential = new GoogleCredential();
        $credential->setRawAttributes(['is_active' => true, 'refresh_token' => null]);

        $this->assertEquals('Incomplete', $credential->statusLabel());
    }

    public function test_status_label_returns_disconnected_when_inactive(): void
    {
        $credential = new GoogleCredential();
        $credential->setRawAttributes(['is_active' => false]);

        $this->assertEquals('Disconnected', $credential->statusLabel());
    }

    public function test_repeated_is_expired_calls_do_not_mutate_expires_at(): void
    {
        $credential = new GoogleCredential();
        $originalExpiry = Carbon::now()->addMinutes(10);
        $credential->expires_at = $originalExpiry;

        $timestampBefore = $credential->expires_at->timestamp;

        // Call isExpired() 10 times consecutively
        for ($i = 0; $i < 10; $i++) {
            $credential->isExpired();
        }

        $timestampAfter = $credential->expires_at->timestamp;

        $this->assertEquals(
            $timestampBefore,
            $timestampAfter,
            'isExpired() must never mutate the in-memory expires_at Carbon instance.'
        );
    }
}
