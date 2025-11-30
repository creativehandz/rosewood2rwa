<?php

namespace App\Jobs;

use App\Services\GoogleSheetsPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncPaymentsWithGoogleSheets implements ShouldQueue
{
    use Queueable;

    protected string $syncDirection;
    public int $timeout = 300; // 5 minutes timeout
    public int $tries = 3; // Retry 3 times on failure

    /**
     * Create a new job instance.
     */
    public function __construct(string $syncDirection = 'bidirectional')
    {
        $this->syncDirection = $syncDirection;
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleSheetsPaymentService $syncService): void
    {
        try {
            Log::info("Starting Google Sheets payment sync job", ['direction' => $this->syncDirection]);

            $result = match ($this->syncDirection) {
                'to-sheets' => $syncService->syncDatabaseToSheets(),
                'from-sheets' => $syncService->syncSheetsToDatabase(),
                'bidirectional' => $syncService->bidirectionalSync(),
                default => throw new \InvalidArgumentException("Invalid sync direction: {$this->syncDirection}")
            };

            Log::info("Google Sheets payment sync completed successfully", [
                'direction' => $this->syncDirection,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("Google Sheets payment sync job failed", [
                'direction' => $this->syncDirection,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Google Sheets payment sync job failed permanently", [
            'direction' => $this->syncDirection,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
