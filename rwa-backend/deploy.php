<?php
// Laravel Deployment Script for PHP
// Run this on the server after FTP upload: php deploy.php

echo "Starting Laravel deployment...\n";

// Change to project directory
$projectPath = '/home/u151629516/domains/creativehandz.in/public_html/rosewoodestate2rwa';
chdir($projectPath);

echo "Current directory: " . getcwd() . "\n";

// Function to run command and show output
function runCommand($command) {
    echo "Running: $command\n";
    $output = [];
    $returnCode = 0;
    exec($command . ' 2>&1', $output, $returnCode);
    
    foreach($output as $line) {
        echo "$line\n";
    }
    
    if($returnCode !== 0) {
        echo "ERROR: Command failed with code $returnCode\n";
        return false;
    }
    
    return true;
}

// Install Composer dependencies
echo "\n=== Installing Composer dependencies ===\n";
if(!runCommand('composer install --optimize-autoloader --no-dev --no-interaction')) {
    echo "Composer install failed!\n";
    exit(1);
}

// Generate application key
echo "\n=== Generating application key ===\n";
runCommand('php artisan key:generate --force');

// Clear caches
echo "\n=== Clearing caches ===\n";
runCommand('php artisan config:clear');
runCommand('php artisan route:clear');
runCommand('php artisan view:clear');

// Cache configuration for production
echo "\n=== Caching configuration ===\n";
runCommand('php artisan config:cache');
runCommand('php artisan route:cache');
runCommand('php artisan view:cache');

echo "\n=== Deployment completed successfully! ===\n";
?>