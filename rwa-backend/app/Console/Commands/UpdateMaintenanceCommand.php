<?php

namespace App\Console\Commands;

use App\Models\Resident;
use Illuminate\Console\Command;

class UpdateMaintenanceCommand extends Command
{
    protected $signature = 'resident:update-maintenance {name} {amount}';
    protected $description = 'Update monthly maintenance for a resident';

    public function handle(): int
    {
        $name = $this->argument('name');
        $amount = floatval($this->argument('amount'));
        
        $resident = Resident::where('owner_name', 'LIKE', "%{$name}%")->first();
        
        if (!$resident) {
            $this->error("Resident not found");
            return self::FAILURE;
        }
        
        $oldMaintenance = $resident->monthly_maintenance;
        $resident->update(['monthly_maintenance' => $amount]);
        
        $this->info("Updated {$resident->owner_name}: ₹{$oldMaintenance} → ₹{$amount}");
        
        return self::SUCCESS;
    }
}