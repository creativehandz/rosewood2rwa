<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetsPaymentService;
use Illuminate\Console\Command;

class SyncPaymentsWithSheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:sync-sheets
                            {direction=bidirectional : Sync direction: bidirectional, to-sheets, from-sheets}
                            {--test : Test connection only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync payments between database and Google Sheets';

    protected $syncService;

    public function __construct(GoogleSheetsPaymentService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $direction = $this->argument('direction');
        $testOnly = $this->option('test');

        $this->info("🔄 Payment Sync Service");
        $this->line("Direction: {$direction}");
        
        if ($testOnly) {
            return $this->testConnection();
        }

        try {
            $this->line("Starting sync...");
            
            $result = match ($direction) {
                'to-sheets' => $this->syncToSheets(),
                'from-sheets' => $this->syncFromSheets(),
                'bidirectional' => $this->bidirectionalSync(),
                default => $this->error("Invalid direction. Use: bidirectional, to-sheets, or from-sheets")
            };

            if ($result) {
                $this->showSyncStatus();
            }

        } catch (\Exception $e) {
            $this->error("❌ Sync failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function testConnection()
    {
        $this->info("🔍 Testing Google Sheets connection...");
        
        try {
            $result = $this->syncService->testConnection();
            
            if ($result['success']) {
                $this->info("✅ Connection successful!");
                $this->line("Spreadsheet: " . $result['spreadsheet_title']);
            } else {
                $this->error("❌ Connection failed: " . $result['message']);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Connection test failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function syncToSheets()
    {
        $this->info("📤 Syncing database to Google Sheets...");
        
        $result = $this->syncService->syncDatabaseToSheets();
        
        $this->info("✅ " . $result['message']);
        $this->line("Synced payments: " . $result['synced_count']);
        
        return true;
    }

    protected function syncFromSheets()
    {
        $this->info("📥 Syncing Google Sheets to database...");
        
        $result = $this->syncService->syncSheetsToDatabase();
        
        $this->info("✅ " . $result['message']);
        $this->line("Processed rows: " . $result['processed_count']);
        
        if (!empty($result['errors'])) {
            $this->warn("⚠️ Errors encountered:");
            foreach ($result['errors'] as $error) {
                $this->line("  • " . $error);
            }
        }
        
        return true;
    }

    protected function bidirectionalSync()
    {
        $this->info("🔄 Starting bidirectional sync...");
        
        $result = $this->syncService->bidirectionalSync();
        
        $this->info("✅ " . $result['message']);
        $this->line("Sheets to DB: " . $result['sheets_to_db']['processed_count'] . " processed");
        $this->line("DB to Sheets: " . $result['db_to_sheets']['synced_count'] . " synced");
        
        return true;
    }

    protected function showSyncStatus()
    {
        $this->line("");
        $this->info("📊 Sync Status:");
        
        $status = $this->syncService->getSyncStatus();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Payments', $status['total_payments']],
                ['Synced Payments', $status['synced_payments']],
                ['Unsynced Payments', $status['unsynced_payments']],
                ['Sync Percentage', $status['sync_percentage'] . '%'],
                ['Last Sync', $status['last_sync_at'] ? 
                    (is_string($status['last_sync_at']) ? $status['last_sync_at'] : $status['last_sync_at']->format('Y-m-d H:i:s')) 
                    : 'Never']
            ]
        );
    }
}
