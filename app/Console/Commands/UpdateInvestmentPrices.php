<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvestmentUpdateService;
use Illuminate\Support\Facades\Storage;

class UpdateInvestmentPrices extends Command
{
    /**
     * php artisan investments:refresh --user=1
     */
    protected $signature = 'investments:refresh {--user= : User ID to update (null = all users)}';

    protected $description = 'Update current prices for all tracked investments';

    public function handle(): int
    {
        $userId   = $this->option('user') ? (int) $this->option('user') : null;
        $statusKey = 'refresh_status' . ($userId ? "_{$userId}" : '') . '.json';

        // Mark as running
        Storage::put($statusKey, json_encode([
            'status'  => 'running',
            'started' => now()->toIso8601String(),
        ]));

        try {
            $service = new InvestmentUpdateService();
            $result  = $service->updateAll($userId);

            Storage::put($statusKey, json_encode([
                'status'  => 'done',
                'updated' => $result['updated'],
                'failed'  => $result['failed'],
                'skipped' => $result['skipped'],
                'finished' => now()->toIso8601String(),
            ]));

            $this->info("Done. Updated: {$result['updated']}, Failed: {$result['failed']}, Skipped: {$result['skipped']}");
        } catch (\Exception $e) {
            Storage::put($statusKey, json_encode([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]));

            $this->error("Failed: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
