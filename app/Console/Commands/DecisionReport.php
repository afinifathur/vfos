<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Domain\Finance\FinancialDecisionReportService;
use App\Domain\Finance\FinancialEventType;

class DecisionReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:decision-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a detailed read-only decision report for all historical transactions comparing legacy execution to the future decision engine';

    /**
     * Execute the console command.
     */
    public function handle(FinancialDecisionReportService $service)
    {
        $this->info("=== Starting Read-Only Financial Decision Report ===");

        // Fetch all transactions with relations loaded
        $transactions = Transaction::with(['account', 'transactionItems.category'])->get();

        if ($transactions->isEmpty()) {
            $this->warn("No transactions found in the database.");
            return 0;
        }

        // Summary counters
        $totalTransactions = $transactions->count();
        $compatibleCount = 0;
        $differentCount = 0;
        $unknownCount = 0;
        $debtCount = 0;
        $receivableCount = 0;
        $investmentCount = 0;

        $headers = [
            'ID', 'Legacy Type', 'Financial Event', 'Reason', 'Confidence', 
            'Target Ledger', 'Requires Selection', 'Profit Loss', 'Accounts', 
            'Debt', 'Receivable', 'Investment', 'Transfer Pair', 'Explanation', 'Warnings'
        ];
        $rows = [];

        foreach ($transactions as $tx) {
            $report = $service->generateReport($tx);

            // Update summary counters
            if ($report->compatible) {
                $compatibleCount++;
            } else {
                $differentCount++;
            }

            if ($report->type === FinancialEventType::UNKNOWN) {
                $unknownCount++;
            }

            if ($report->decision->updateDebt) {
                $debtCount++;
            }

            if ($report->decision->updateReceivable) {
                $receivableCount++;
            }

            if ($report->decision->updateInvestment) {
                $investmentCount++;
            }

            // Compile warnings string
            $warningsStr = count($report->warnings) > 0 ? implode(' | ', $report->warnings) : '-';

            $rows[] = [
                '#' . $report->transactionId,
                $report->legacyType,
                $report->type->value,
                $report->reason->value,
                $report->decision->confidence->value,
                $report->decision->targetLedgerType ?? '-',
                $report->decision->requiresUserSelection ? 'Yes' : 'No',
                $report->decision->includeProfitLoss ? 'Yes' : 'No',
                $report->decision->updateAccounts ? 'Yes' : 'No',
                $report->decision->updateDebt ? 'Yes' : 'No',
                $report->decision->updateReceivable ? 'Yes' : 'No',
                $report->decision->updateInvestment ? 'Yes' : 'No',
                $report->decision->createTransferPair ? 'Yes' : 'No',
                $report->decision->explanation,
                $warningsStr
            ];
        }

        // Display results table
        $this->table($headers, $rows);

        // Display summary
        $this->info("\n=== Decision Engine Summary ===");
        $this->line(sprintf("Total Transactions:           %d", $totalTransactions));
        $this->line(sprintf("Compatible:                   %d", $compatibleCount));
        $this->line(sprintf("Different Behaviour:          %d", $differentCount));
        $this->line(sprintf("Unknown:                      %d", $unknownCount));
        $this->line(sprintf("Potential Debt Events:        %d", $debtCount));
        $this->line(sprintf("Potential Receivable Events:  %d", $receivableCount));
        $this->line(sprintf("Potential Investment Events:  %d", $investmentCount));

        return 0;
    }
}
