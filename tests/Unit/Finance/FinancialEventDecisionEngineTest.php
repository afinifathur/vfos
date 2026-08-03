<?php

namespace Tests\Unit\Finance;

use Tests\TestCase;
use App\Domain\Finance\FinancialEventDecisionEngine;
use App\Domain\Finance\FinancialEventResult;
use App\Domain\Finance\FinancialEventType;
use App\Domain\Finance\FinancialEventReason;
use App\Domain\Finance\DecisionConfidence;

class FinancialEventDecisionEngineTest extends TestCase
{
    private FinancialEventDecisionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new FinancialEventDecisionEngine();
    }

    public function test_value_creation_decision(): void
    {
        $result = new FinancialEventResult(FinancialEventType::VALUE_CREATION, FinancialEventReason::SALARY);
        $decision = $this->engine->decide($result);

        $this->assertEquals(FinancialEventType::VALUE_CREATION, $decision->type);
        $this->assertEquals(FinancialEventReason::SALARY, $decision->reason);
        $this->assertEquals(DecisionConfidence::HIGH, $decision->confidence);
        $this->assertTrue($decision->updateAccounts);
        $this->assertTrue($decision->includeProfitLoss);
        $this->assertFalse($decision->updateDebt);
        $this->assertFalse($decision->updateReceivable);
        $this->assertFalse($decision->updateInvestment);
        $this->assertFalse($decision->createTransferPair);
        $this->assertTrue($decision->affectsCashFlow);
        $this->assertFalse($decision->affectsBalanceSheet);
        $this->assertTrue($decision->affectsNetWorth);
        $this->assertFalse($decision->requiresUserSelection);
        $this->assertNull($decision->targetLedgerType);
        $this->assertEquals('Creates new value.', $decision->explanation);
        $this->assertEmpty($decision->warnings);
    }

    public function test_value_consumption_decision(): void
    {
        $result = new FinancialEventResult(FinancialEventType::VALUE_CONSUMPTION, FinancialEventReason::MERCHANT_PAYMENT);
        $decision = $this->engine->decide($result);

        $this->assertEquals(DecisionConfidence::HIGH, $decision->confidence);
        $this->assertTrue($decision->includeProfitLoss);
        $this->assertTrue($decision->affectsNetWorth);
        $this->assertEquals('Consumes economic value.', $decision->explanation);
    }

    public function test_internal_transfer_decision(): void
    {
        $result = new FinancialEventResult(FinancialEventType::INTERNAL_TRANSFER, FinancialEventReason::OWN_ACCOUNT_TRANSFER);
        $decision = $this->engine->decide($result);

        $this->assertEquals(DecisionConfidence::HIGH, $decision->confidence);
        $this->assertTrue($decision->updateAccounts);
        $this->assertFalse($decision->includeProfitLoss);
        $this->assertTrue($decision->createTransferPair);
        $this->assertFalse($decision->affectsCashFlow);
        $this->assertFalse($decision->affectsNetWorth);
        $this->assertEquals('Moves value between owned accounts.', $decision->explanation);
    }

    public function test_liability_creation_decision(): void
    {
        $result = new FinancialEventResult(FinancialEventType::LIABILITY_CREATION, FinancialEventReason::LOAN_COLLECTION);
        $decision = $this->engine->decide($result);

        $this->assertEquals(DecisionConfidence::MEDIUM, $decision->confidence);
        $this->assertTrue($decision->updateAccounts);
        $this->assertTrue($decision->updateDebt);
        $this->assertFalse($decision->includeProfitLoss);
        $this->assertTrue($decision->affectsBalanceSheet);
        $this->assertTrue($decision->requiresUserSelection);
        $this->assertEquals('DEBT', $decision->targetLedgerType);
        $this->assertContains('Debt target required.', $decision->warnings);
    }

    public function test_liability_reduction_decision(): void
    {
        $result = new FinancialEventResult(FinancialEventType::LIABILITY_REDUCTION, FinancialEventReason::MORTGAGE_PRINCIPAL);
        $decision = $this->engine->decide($result);

        $this->assertEquals(DecisionConfidence::MEDIUM, $decision->confidence);
        $this->assertTrue($decision->updateAccounts);
        $this->assertTrue($decision->updateDebt);
        $this->assertTrue($decision->requiresUserSelection);
        $this->assertEquals('DEBT', $decision->targetLedgerType);
    }

    public function test_asset_acquisition_investment_decision(): void
    {
        $result = new FinancialEventResult(FinancialEventType::ASSET_ACQUISITION, FinancialEventReason::INVESTMENT_PURCHASE);
        $decision = $this->engine->decide($result);

        $this->assertEquals(DecisionConfidence::MEDIUM, $decision->confidence);
        $this->assertTrue($decision->updateInvestment);
        $this->assertFalse($decision->updateReceivable);
        $this->assertEquals('INVESTMENT', $decision->targetLedgerType);
        $this->assertContains('Investment target required.', $decision->warnings);
    }

    public function test_asset_acquisition_receivable_decision(): void
    {
        $result = new FinancialEventResult(FinancialEventType::ASSET_ACQUISITION, FinancialEventReason::LOAN_COLLECTION);
        $decision = $this->engine->decide($result);

        $this->assertEquals(DecisionConfidence::MEDIUM, $decision->confidence);
        $this->assertFalse($decision->updateInvestment);
        $this->assertTrue($decision->updateReceivable);
        $this->assertEquals('RECEIVABLE', $decision->targetLedgerType);
        $this->assertContains('Receivable target required.', $decision->warnings);
    }

    public function test_valuation_adjustment_decision(): void
    {
        $result = new FinancialEventResult(FinancialEventType::VALUATION_ADJUSTMENT, FinancialEventReason::INVESTMENT_PURCHASE);
        $decision = $this->engine->decide($result);

        $this->assertEquals(DecisionConfidence::MEDIUM, $decision->confidence);
        $this->assertFalse($decision->updateAccounts);
        $this->assertTrue($decision->updateInvestment);
        $this->assertTrue($decision->affectsNetWorth);
        $this->assertTrue($decision->affectsBalanceSheet);
    }

    public function test_unknown_decision(): void
    {
        $result = new FinancialEventResult(FinancialEventType::UNKNOWN, FinancialEventReason::UNKNOWN);
        $decision = $this->engine->decide($result);

        $this->assertEquals(DecisionConfidence::LOW, $decision->confidence);
        $this->assertFalse($decision->updateAccounts);
        $this->assertFalse($decision->includeProfitLoss);
        $this->assertContains('Unknown classification.', $decision->warnings);
    }
}
