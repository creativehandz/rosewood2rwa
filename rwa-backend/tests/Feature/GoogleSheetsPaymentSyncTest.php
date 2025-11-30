<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Resident;
use App\Services\GoogleSheetsPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class GoogleSheetsPaymentSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $googleSheetsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Google Sheets service to avoid actual API calls in tests
        $this->googleSheetsService = Mockery::mock(GoogleSheetsPaymentService::class);
        $this->app->instance(GoogleSheetsPaymentService::class, $this->googleSheetsService);
    }

    public function test_can_get_sync_status()
    {
        // Create test data
        $resident = Resident::factory()->create();
        Payment::factory()->count(3)->create(['resident_id' => $resident->id]);

        // Mock the sync status response
        $this->googleSheetsService
            ->shouldReceive('getSyncStatus')
            ->once()
            ->andReturn([
                'total_payments' => 3,
                'synced_payments' => 2,
                'unsynced_payments' => 1,
                'last_sync_at' => now(),
                'sync_percentage' => 66.67
            ]);

        $response = $this->actingAs($this->createAuthenticatedUser())
            ->getJson('/api/v1/payments/google-sheets/sync-status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_payments',
                    'synced_payments',
                    'unsynced_payments',
                    'sync_percentage'
                ]
            ]);
    }

    public function test_can_test_sheets_connection()
    {
        // Mock successful connection test
        $this->googleSheetsService
            ->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Connection successful',
                'spreadsheet_title' => 'Rosewood RWA'
            ]);

        $response = $this->actingAs($this->createAuthenticatedUser())
            ->getJson('/api/v1/payments/google-sheets/test-connection');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Connection successful'
            ]);
    }

    public function test_can_sync_to_sheets()
    {
        // Mock successful sync to sheets
        $this->googleSheetsService
            ->shouldReceive('syncDatabaseToSheets')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Successfully synced to Google Sheets',
                'synced_count' => 5
            ]);

        $response = $this->actingAs($this->createAuthenticatedUser())
            ->postJson('/api/v1/payments/google-sheets/sync-to-sheets');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'synced_count' => 5
                ]
            ]);
    }

    public function test_can_sync_from_sheets()
    {
        // Mock successful sync from sheets
        $this->googleSheetsService
            ->shouldReceive('syncSheetsToDatabase')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Sync from Google Sheets completed',
                'processed_count' => 3,
                'errors' => []
            ]);

        $response = $this->actingAs($this->createAuthenticatedUser())
            ->postJson('/api/v1/payments/google-sheets/sync-from-sheets');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'processed_count' => 3,
                    'errors' => []
                ]
            ]);
    }

    public function test_can_perform_bidirectional_sync()
    {
        // Mock successful bidirectional sync
        $this->googleSheetsService
            ->shouldReceive('bidirectionalSync')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Bidirectional sync completed',
                'sheets_to_db' => ['processed_count' => 2],
                'db_to_sheets' => ['synced_count' => 5]
            ]);

        $response = $this->actingAs($this->createAuthenticatedUser())
            ->postJson('/api/v1/payments/google-sheets/bidirectional-sync');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Bidirectional sync completed'
            ]);
    }

    public function test_handles_sync_errors_gracefully()
    {
        // Mock sync failure
        $this->googleSheetsService
            ->shouldReceive('syncDatabaseToSheets')
            ->once()
            ->andThrow(new \Exception('Google Sheets API error'));

        $response = $this->actingAs($this->createAuthenticatedUser())
            ->postJson('/api/v1/payments/google-sheets/sync-to-sheets');

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Failed to sync to Google Sheets'
            ]);
    }

    private function createAuthenticatedUser()
    {
        return \App\Models\User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}