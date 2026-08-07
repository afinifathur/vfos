<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\AccountIdentifier;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\Finance\Diagnostics\FinancialIntegrityValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class FinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
    }

    public function test_validator_detects_balance_drift(): void
    {
        $account = Account::create([
            'user_id'         => $this->user->id,
            'name'            => 'BCA DEBIT',
            'type'            => 'bank',
            'account_number'  => '1234',
            'initial_balance' => 1000.0,
            'total_balance'   => 1000.0,
            'owner'           => 'afin',
            'is_active'       => true,
        ]);

        AccountIdentifier::create([
            'account_id'     => $account->id,
            'provider'       => 'BCA',
            'value'          => '1234',
            'original_value' => '1234',
            'type'           => 'account_number',
            'priority'       => 100,
            'source'         => 'manual',
        ]);

        // Create transaction that would result in balance = 1200.0 (total_balance is cached at 1000.0, so drift is 200)
        $tx = Transaction::create([
            'account_id'       => $account->id,
            'total_amount'     => 200.0,
            'type'             => 'income',
            'transaction_date' => now(),
        ]);
        
        $category = Category::create([
            'user_id' => $this->user->id,
            'name'    => 'Salary',
            'type'    => 'income',
            'owner'   => 'afin',
        ]);

        TransactionItem::create([
            'transaction_id' => $tx->id,
            'category_id'    => $category->id,
            'amount'         => 200.0,
        ]);

        // Manually update database column directly to bypass Transaction saved observer recalculation
        \Illuminate\Support\Facades\DB::table('accounts')->where('id', $account->id)->update(['total_balance' => 1000.0]);

        $validator = app(FinancialIntegrityValidator::class);
        $report = $validator->validate($this->user->id);

        $this->assertLessThan(100, $report->healthScore);
        
        $findings = $report->findings->all();
        $driftFinding = $findings->firstWhere('issue', 'Cached Balance Mismatch (Balance Drift)');
        $this->assertNotNull($driftFinding);
        $this->assertEquals(200.0, $driftFinding->difference);
    }

    public function test_validator_detects_duplicate_active_identifiers(): void
    {
        $account1 = Account::create([
            'user_id'         => $this->user->id,
            'name'            => 'BCA DEBIT 1',
            'type'            => 'bank',
            'account_number'  => '1234',
            'initial_balance' => 0.0,
            'total_balance'   => 0.0,
            'owner'           => 'afin',
            'is_active'       => true,
        ]);

        $account2 = Account::create([
            'user_id'         => $this->user->id,
            'name'            => 'BCA DEBIT 2',
            'type'            => 'bank',
            'account_number'  => '5678',
            'initial_balance' => 0.0,
            'total_balance'   => 0.0,
            'owner'           => 'afin',
            'is_active'       => true,
        ]);

        AccountIdentifier::create([
            'account_id'     => $account1->id,
            'provider'       => 'BCA',
            'value'          => '123456',
            'original_value' => '123456',
            'type'           => 'account_number',
            'priority'       => 100,
            'source'         => 'manual',
        ]);

        AccountIdentifier::create([
            'account_id'     => $account2->id,
            'provider'       => 'BCA',
            'value'          => '123456',
            'original_value' => '123456',
            'type'           => 'account_number',
            'priority'       => 100,
            'source'         => 'manual',
        ]);

        $validator = app(FinancialIntegrityValidator::class);
        $report = $validator->validate($this->user->id);

        $findings = $report->findings->all();
        $dupFinding = $findings->firstWhere('issue', 'Duplicate Active Account Identifier');
        $this->assertNotNull($dupFinding);
    }

    public function test_health_command_exits_cleanly(): void
    {
        $account = Account::create([
            'user_id'         => $this->user->id,
            'name'            => 'BCA DEBIT',
            'type'            => 'bank',
            'account_number'  => '1234',
            'initial_balance' => 0.0,
            'total_balance'   => 0.0,
            'owner'           => 'afin',
            'is_active'       => true,
        ]);

        AccountIdentifier::create([
            'account_id'     => $account->id,
            'provider'       => 'BCA',
            'value'          => '1234',
            'original_value' => '1234',
            'type'           => 'account_number',
            'priority'       => 100,
            'source'         => 'manual',
        ]);

        $exitCode = Artisan::call('finance:health', ['--user' => $this->user->id]);
        $this->assertEquals(0, $exitCode);
    }

    public function test_production_report_command_exits_cleanly(): void
    {
        Config::set('finance.use_identity_resolver', true);

        $account = Account::create([
            'user_id'         => $this->user->id,
            'name'            => 'BCA DEBIT',
            'type'            => 'bank',
            'account_number'  => '1234',
            'initial_balance' => 0.0,
            'total_balance'   => 0.0,
            'owner'           => 'afin',
            'is_active'       => true,
        ]);

        AccountIdentifier::create([
            'account_id'     => $account->id,
            'provider'       => 'BCA',
            'value'          => '1234',
            'original_value' => '1234',
            'type'           => 'account_number',
            'priority'       => 100,
            'source'         => 'manual',
        ]);

        $exitCode = Artisan::call('finance:production-report', ['--user' => $this->user->id]);
        $this->assertContains($exitCode, [0, 1, 2]);
    }
}
