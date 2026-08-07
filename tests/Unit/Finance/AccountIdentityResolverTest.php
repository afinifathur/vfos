<?php

namespace Tests\Unit\Finance;

use Tests\TestCase;
use App\Models\Account;
use App\Models\User;
use App\Services\Finance\Identity\AccountIdentityResolverInterface;
use App\Services\Finance\Identity\Policies\ConfidencePolicy;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountIdentityResolverTest extends TestCase
{
    use DatabaseTransactions;

    private AccountIdentityResolverInterface $resolver;
    private User $user;
    private Account $account1;
    private Account $account2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = $this->app->make(AccountIdentityResolverInterface::class);
        $this->user = User::factory()->create();

        // Create two accounts for testing
        $this->account1 = Account::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Debit',
            'type' => 'bank',
            'owner' => 'afin',
            'is_active' => true,
        ]);

        $this->account2 = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Mandiri Payroll',
            'type' => 'bank',
            'owner' => 'afin',
            'is_active' => true,
        ]);
    }

    public function test_exact_match(): void
    {
        // Register an exact account number for account1
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'account_number',
            value: '0244247535',
            provider: 'BCA',
            priority: 0,
            source: 'manual'
        );

        $result = $this->resolver->resolve('0244247535', 'BCA', $this->user->id);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->account1->id, $result->account->id);
        $this->assertEquals(1.0, $result->confidence);
        $this->assertEquals('exact_match', $result->resolutionMethod);
        $this->assertEquals('account_number', $result->identifierType);
        $this->assertEquals('0244247535', $result->matchedBy);
    }

    public function test_alias_match(): void
    {
        $this->resolver->registerIdentifier(
            accountId: $this->account2->id,
            type: 'alias',
            value: 'Payroll',
            provider: null,
            priority: 50,
            source: 'manual'
        );

        $result = $this->resolver->resolve('Payroll', null, $this->user->id);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->account2->id, $result->account->id);
        $this->assertEquals(0.80, $result->confidence);
        $this->assertEquals('exact_match', $result->resolutionMethod);
        $this->assertEquals('alias', $result->identifierType);
    }

    public function test_phone_match(): void
    {
        // Phone: 08123456789 (which normalizes to 628123456789)
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'phone',
            value: '08123456789',
            provider: null,
            priority: 0,
            source: 'manual'
        );

        // Query with +62 812-3456-789
        $result = $this->resolver->resolve('+62 812-3456-789', null, $this->user->id);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->account1->id, $result->account->id);
        $this->assertEquals(0.90, $result->confidence);
        $this->assertEquals('exact_match', $result->resolutionMethod);
        $this->assertEquals('phone', $result->identifierType);
        $this->assertEquals('628123456789', $result->matchedBy);
    }

    public function test_virtual_account_match(): void
    {
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'virtual_account',
            value: '8801244247535',
            provider: 'BCA',
            priority: 10,
            source: 'manual'
        );

        $result = $this->resolver->resolve('8801244247535', 'BCA', $this->user->id);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->account1->id, $result->account->id);
        $this->assertEquals(0.95, $result->confidence);
        $this->assertEquals('exact_match', $result->resolutionMethod);
    }

    public function test_suffix_partial_match(): void
    {
        // Stored account number
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'account_number',
            value: '0244247535',
            provider: 'BCA',
            priority: 0,
            source: 'manual'
        );

        // Case A: Query is longer (e.g. virtual account wraps base account suffix)
        // Stored: 0244247535, Query: 88010244247535
        $resultA = $this->resolver->resolve('88010244247535', 'BCA', $this->user->id);
        $this->assertTrue($resultA->isSuccess());
        $this->assertEquals($this->account1->id, $resultA->account->id);
        $this->assertEquals(0.95, $resultA->confidence);
        $this->assertEquals('virtual_account_match', $resultA->resolutionMethod);

        // Case B: Query is shorter (e.g. last 5 digits partial lookup)
        // Stored: 0244247535, Query: 47535
        $resultB = $this->resolver->resolve('47535', 'BCA', $this->user->id);
        $this->assertTrue($resultB->isSuccess());
        $this->assertEquals($this->account1->id, $resultB->account->id);
        $this->assertEquals(0.80, $resultB->confidence);
        $this->assertEquals('alias_match', $resultB->resolutionMethod);

        // Case C: Too short suffix (less than 4 digits) -> should not match
        // Query: 535 (length 3)
        $resultC = $this->resolver->resolve('535', 'BCA', $this->user->id);
        $this->assertFalse($resultC->isSuccess());
    }

    public function test_multiple_identifiers_resolves_highest_confidence(): void
    {
        // Register an alias on account1 (80% confidence)
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'alias',
            value: '123456789',
            provider: null,
            priority: 0,
            source: 'manual'
        );

        // Register a phone on account2 (90% confidence)
        $this->resolver->registerIdentifier(
            accountId: $this->account2->id,
            type: 'phone',
            value: '123456789',
            provider: null,
            priority: 0,
            source: 'manual'
        );

        // When we query '123456789', it matches both!
        // Phone match has higher confidence (90%) than alias match (80%).
        // So account2 must win!
        $result = $this->resolver->resolve('123456789', null, $this->user->id);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->account2->id, $result->account->id);
        $this->assertEquals(0.90, $result->confidence);
    }

    public function test_ambiguous_identifiers_resolve_to_null(): void
    {
        // Register same alias on account1 and account2
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'alias',
            value: 'Payroll',
            provider: null,
            priority: 10,
            source: 'manual'
        );

        $this->resolver->registerIdentifier(
            accountId: $this->account2->id,
            type: 'alias',
            value: 'Payroll',
            provider: null,
            priority: 10,
            source: 'manual'
        );

        $result = $this->resolver->resolve('Payroll', null, $this->user->id);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('ambiguous', $result->resolutionMethod);
        $this->assertEquals(0.0, $result->confidence);
    }

    public function test_low_confidence_match(): void
    {
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'display_name',
            value: 'BCA Debit Account',
            provider: null,
            priority: 0,
            source: 'manual'
        );

        // Under STRICT mode (default), display name (60% confidence) is rejected
        $resultStrict = $this->resolver->resolve('BCA Debit Account', null, $this->user->id, 'STRICT');
        $this->assertFalse($resultStrict->isSuccess());
        $this->assertEquals('low_confidence', $resultStrict->resolutionMethod);

        // Under BEST_EFFORT mode, it should resolve successfully with 60% confidence
        $resultBest = $this->resolver->resolve('BCA Debit Account', null, $this->user->id, 'BEST_EFFORT');
        $this->assertTrue($resultBest->isSuccess());
        $this->assertEquals($this->account1->id, $resultBest->account->id);
        $this->assertEquals(0.60, $resultBest->confidence);
    }

    public function test_no_match(): void
    {
        $result = $this->resolver->resolve('NonExistent', null, $this->user->id);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('no_match', $result->resolutionMethod);
        $this->assertEquals(0.0, $result->confidence);
    }

    public function test_duplicate_identifiers(): void
    {
        // Register duplicate records for the exact same account
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'account_number',
            value: '0244247535',
            provider: 'BCA',
            priority: 0,
            source: 'manual'
        );

        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'account_number',
            value: '0244247535',
            provider: 'BCA',
            priority: 0,
            source: 'manual'
        );

        $result = $this->resolver->resolve('0244247535', 'BCA', $this->user->id);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->account1->id, $result->account->id);
    }

    public function test_archived_identifiers_are_ignored(): void
    {
        $candidate = $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'account_number',
            value: '0244247535',
            provider: 'BCA',
            priority: 0,
            source: 'manual'
        );

        // Resolve initially works
        $this->assertTrue($this->resolver->resolve('0244247535', 'BCA', $this->user->id)->isSuccess());

        // Archive the identifier
        $this->resolver->archiveIdentifier($candidate->uuid);

        // Resolve now fails
        $result = $this->resolver->resolve('0244247535', 'BCA', $this->user->id);
        $this->assertFalse($result->isSuccess());
    }

    public function test_provider_isolation(): void
    {
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'account_number',
            value: '0244247535',
            provider: 'BCA',
            priority: 0,
            source: 'manual'
        );

        // Query with BCA provider -> resolves successfully
        $resultBCA = $this->resolver->resolve('0244247535', 'BCA', $this->user->id);
        $this->assertTrue($resultBCA->isSuccess());

        // Query with Mandiri provider -> fails to match BCA account
        $resultMandiri = $this->resolver->resolve('0244247535', 'Mandiri', $this->user->id);
        $this->assertFalse($resultMandiri->isSuccess());
    }

    public function test_normalization_collision(): void
    {
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'account_number',
            value: '0244-247535',
            provider: null,
            priority: 0,
            source: 'manual'
        );

        $result = $this->resolver->resolve('0244247535', null, $this->user->id);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->account1->id, $result->account->id);
    }

    public function test_case_insensitivity(): void
    {
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'alias',
            value: 'BCA',
            provider: null,
            priority: 0,
            source: 'manual'
        );

        // Query with bca
        $resultLower = $this->resolver->resolve('bca', null, $this->user->id);
        $this->assertTrue($resultLower->isSuccess());

        // Query with BcA
        $resultMixed = $this->resolver->resolve('BcA', null, $this->user->id);
        $this->assertTrue($resultMixed->isSuccess());
    }

    public function test_priority_resolution(): void
    {
        // Both match with the same confidence (alias = 80%)
        // account1 priority = 100
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'alias',
            value: 'Payroll',
            provider: null,
            priority: 100,
            source: 'manual'
        );

        // account2 priority = 20
        $this->resolver->registerIdentifier(
            accountId: $this->account2->id,
            type: 'alias',
            value: 'Payroll',
            provider: null,
            priority: 20,
            source: 'manual'
        );

        $result = $this->resolver->resolve('Payroll', null, $this->user->id);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->account1->id, $result->account->id); // account1 wins because higher priority
    }

    public function test_resolution_trace(): void
    {
        $this->resolver->registerIdentifier(
            accountId: $this->account1->id,
            type: 'account_number',
            value: '0244247535',
            provider: 'BCA',
            priority: 0,
            source: 'manual'
        );

        $result = $this->resolver->resolve('0244247535', 'BCA', $this->user->id);

        $this->assertNotEmpty($result->trace);
        $this->assertIsArray($result->trace);
        $this->assertStringContainsString('Input', $result->trace[0]);
        $this->assertStringContainsString('Normalized', $result->trace[1]);
    }
}
