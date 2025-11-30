<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentService;

class TestDefaultersCommand extends Command
{
    protected $signature = 'test:defaulters';
    protected $description = 'Test the defaulters service method';

    public function handle()
    {
        try {
            $service = new PaymentService();
            $defaulters = $service->getDefaultersList();
            
            $this->info('Success! Found ' . $defaulters->count() . ' defaulters.');
            $this->line('Type: ' . get_class($defaulters));
            
            if ($defaulters->count() > 0) {
                $this->line('First defaulter:');
                $first = $defaulters->first();
                $this->line('- Resident: ' . $first['resident']->owner_name);
                $this->line('- Total balance: ₹' . number_format($first['total_balance'], 2));
                $this->line('- Overdue months: ' . $first['overdue_months']);
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}