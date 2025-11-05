<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Drive;
use App\Models\Resident;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleSheetsService
{
    private $client;
    private $sheetsService;
    private $driveService;
    private $spreadsheetId;

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Initialize Google API client
     */
    private function initializeClient()
    {
        try {
            $this->client = new Client();
            $this->client->setApplicationName(config('google.sheets.application_name'));
            $this->client->setScopes(config('google.scopes'));
            
            $serviceAccountPath = config('google.service_account_json_path');
            
            // Debug: Log the path being used
            Log::info('Google Sheets Service - Attempting to load JSON from: ' . $serviceAccountPath);
            Log::info('Google Sheets Service - File exists: ' . (file_exists($serviceAccountPath) ? 'YES' : 'NO'));
            
            if (!file_exists($serviceAccountPath)) {
                throw new Exception("Google service account JSON file not found at: {$serviceAccountPath}");
            }
            
            $this->client->setAuthConfig($serviceAccountPath);
            
            $this->sheetsService = new Sheets($this->client);
            $this->driveService = new Drive($this->client);
            $this->spreadsheetId = config('google.sheets.spreadsheet_id');
            
            Log::info('Google Sheets Service - Successfully initialized');
            
        } catch (Exception $e) {
            Log::error('Failed to initialize Google Sheets client: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new Google Sheet for residents
     */
    public function createResidentsSheet($title = 'RWA Residents')
    {
        try {
            // Create spreadsheet
            $spreadsheet = new \Google\Service\Sheets\Spreadsheet([
                'properties' => [
                    'title' => $title
                ],
                'sheets' => [
                    [
                        'properties' => [
                            'title' => 'Residents',
                            'gridProperties' => [
                                'rowCount' => 1000,
                                'columnCount' => 20
                            ]
                        ]
                    ]
                ]
            ]);

            $createdSheet = $this->sheetsService->spreadsheets->create($spreadsheet);
            $this->spreadsheetId = $createdSheet->getSpreadsheetId();

            // Set up headers
            $this->setupHeaders();

            return [
                'spreadsheet_id' => $this->spreadsheetId,
                'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/edit",
                'message' => 'Spreadsheet created successfully'
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Google Sheet: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Set up headers in the spreadsheet
     */
    private function setupHeaders()
    {
        $headers = [
            [
                // Basic Information
                'Resident ID', 
                'House Number', 
                'Floor', 
                'Property Type',
                
                // Owner Details
                'Owner Name', 
                'Contact Number', 
                'Email Address', 
                'Address',
                
                // Status & Management
                'Status', 
                'Current State', 
                'Monthly Maintenance (₹)',
                
                // Additional Details
                'Move-in Date',
                'Emergency Contact',
                'Emergency Phone',
                'Remarks',
                
                // Timestamps
                'Created Date', 
                'Last Updated'
            ]
        ];

        $range = 'Residents!A1:Q1'; // Updated to include Remarks column (Q = 17 columns)
        $body = new ValueRange([
            'values' => $headers
        ]);

        $params = [
            'valueInputOption' => 'RAW'
        ];

        return $this->sheetsService->spreadsheets_values->update(
            $this->spreadsheetId, 
            $range, 
            $body, 
            $params
        );
    }

    /**
     * Push all residents to Google Sheets
     */
    public function pushAllResidents()
    {
        try {
            // Increase execution time for large datasets
            set_time_limit(300); // 5 minutes
            
            if (empty($this->spreadsheetId)) {
                throw new Exception('Google Sheets spreadsheet ID not configured');
            }

            // Get all residents from database
            $residents = Resident::all();
            
            Log::info('Starting push to Google Sheets', [
                'resident_count' => $residents->count(),
                'spreadsheet_id' => $this->spreadsheetId
            ]);
            
            if ($residents->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No residents found to push',
                    'count' => 0
                ];
            }

            Log::info('Clearing existing data...');

            // Clear existing data (except headers)
            $this->clearExistingData();

            Log::info('Updating headers...');
            // Update headers to ensure they're correct (includes remarks column)
            $this->setupHeaders();

            Log::info('Preparing data for ' . $residents->count() . ' residents...');

            // Prepare data for Google Sheets
            $values = [];
            foreach ($residents as $resident) {
                $values[] = [
                    // Basic Information
                    $resident->id,
                    $resident->house_number ?? '',
                    $this->formatFloor($resident->floor),
                    $this->formatPropertyType($resident->property_type),
                    
                    // Owner Details
                    $resident->owner_name,
                    $resident->contact_number ?? '',
                    $resident->email ?? '',
                    $resident->address ?? '',
                    
                    // Status & Management
                    ucfirst($resident->status),
                    ucfirst($resident->current_state ?? ''),
                    '₹' . number_format($resident->monthly_maintenance, 2),
                    
                    // Additional Details
                    $resident->move_in_date ? $resident->move_in_date : '',
                    $resident->emergency_contact ? $resident->emergency_contact : '',
                    $resident->emergency_phone ? $resident->emergency_phone : '',
                    $this->formatRemarks($resident->remarks),
                    
                    // Timestamps
                    $resident->created_at ? $resident->created_at->format('Y-m-d H:i:s') : '',
                    $resident->updated_at ? $resident->updated_at->format('Y-m-d H:i:s') : ''
                ];
            }

            // Update the spreadsheet (updated range to accommodate remarks column - 17 columns)
            $range = 'Residents!A2:Q' . (count($values) + 1);
            $body = new ValueRange([
                'values' => $values
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];

            Log::info('Pushing data to Google Sheets...');
            
            $result = $this->sheetsService->spreadsheets_values->update(
                $this->spreadsheetId, 
                $range, 
                $body, 
                $params
            );

            Log::info('Successfully pushed data to Google Sheets', [
                'rows_updated' => count($values),
                'range' => $range
            ]);

            return [
                'success' => true,
                'message' => 'Residents data pushed to Google Sheets successfully',
                'count' => count($values),
                'updated_cells' => $result->getUpdatedCells(),
                'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/edit"
            ];

        } catch (Exception $e) {
            Log::error('Failed to push residents to Google Sheets: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clear existing data (keep headers)
     */
    private function clearExistingData()
    {
        try {
            $range = 'Residents!A2:Q1000'; // Updated to include remarks column (17 columns)
            
            // Create a clear values request
            $clearRequest = new ClearValuesRequest();

            $this->sheetsService->spreadsheets_values->clear(
                $this->spreadsheetId, 
                $range,
                $clearRequest
            );
        } catch (Exception $e) {
            Log::warning('Failed to clear existing data: ' . $e->getMessage());
        }
    }

    /**
     * Format property type for display
     */
    private function formatPropertyType($type)
    {
        $types = [
            'house' => 'House',
            '3bhk_flat' => '3 BHK Flat',
            'villa' => 'Villa',
            '2bhk_flat' => '2 BHK Flat',
            '1bhk_flat' => '1 BHK Flat',
            'estonia_1' => 'Estonia 1',
            'estonia_2' => 'Estonia 2',
            'plot' => 'Plot'
        ];

        return $types[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Format remarks for Google Sheets display
     */
    private function formatRemarks($remarks)
    {
        if (!$remarks || empty($remarks)) {
            return 'No remarks';
        }

        // If remarks is a string, return it directly
        if (is_string($remarks)) {
            return $remarks;
        }

        // If remarks is an array of objects, format them (limit to first 3 for performance)
        if (is_array($remarks)) {
            $formattedRemarks = [];
            $maxRemarks = 3; // Limit for performance
            $count = 0;
            
            foreach ($remarks as $remark) {
                if ($count >= $maxRemarks) break;
                
                if (is_array($remark) || is_object($remark)) {
                    $text = $remark['text'] ?? $remark->text ?? '';
                    $addedBy = $remark['added_by'] ?? $remark->added_by ?? 'Admin';
                    
                    if ($text) {
                        $formattedRemarks[] = "• {$text} (by {$addedBy})";
                        $count++;
                    }
                } elseif (is_string($remark)) {
                    $formattedRemarks[] = "• {$remark}";
                    $count++;
                }
            }
            
            $result = implode("\n", $formattedRemarks);
            if (count($remarks) > $maxRemarks) {
                $result .= "\n• +" . (count($remarks) - $maxRemarks) . " more remarks...";
            }
            
            return $result;
        }

        return 'No remarks';
    }

    /**
     * Format floor for display
     */
    private function formatFloor($floor)
    {
        if (empty($floor)) {
            return '';
        }

        $floors = [
            'ground_floor' => 'Ground Floor',
            '1st_floor' => '1st Floor',
            '2nd_floor' => '2nd Floor'
        ];

        return $floors[$floor] ?? ucfirst(str_replace('_', ' ', $floor));
    }

    /**
     * Test connection to Google Sheets
     */
    public function testConnection()
    {
        try {
            // Test basic authentication by trying to access the Sheets service
            // This doesn't require a specific spreadsheet ID
            $drive = $this->driveService;
            
            // Try to list some files (limited to just check if auth works)
            $response = $drive->files->listFiles([
                'pageSize' => 1,
                'fields' => 'files(id, name)'
            ]);
            
            $authSuccess = true;
            $message = 'Google Sheets authentication successful';
            $data = [
                'authentication' => 'Success',
                'service_account_email' => $this->getServiceAccountEmail(),
            ];
            
            // Check if spreadsheet ID is configured
            if (empty($this->spreadsheetId)) {
                $data['spreadsheet_status'] = 'Not configured - use "Create New Sheet" to set up';
                $data['next_step'] = 'Create a new sheet or configure existing spreadsheet ID';
            } else {
                try {
                    // Test access to the configured spreadsheet
                    $spreadsheet = $this->sheetsService->spreadsheets->get($this->spreadsheetId);
                    $data['spreadsheet_status'] = 'Configured and accessible';
                    $data['spreadsheet_title'] = $spreadsheet->getProperties()->getTitle();
                    $data['spreadsheet_url'] = "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/edit";
                } catch (Exception $e) {
                    $data['spreadsheet_status'] = 'Configured but not accessible: ' . $e->getMessage();
                    $data['spreadsheet_error'] = $e->getMessage();
                }
            }
            
            return [
                'success' => $authSuccess,
                'message' => $message,
                'data' => $data
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync residents from Google Sheets to database
     */
    public function syncResidentsFromSheet()
    {
        try {
            // Increase execution time for large datasets
            set_time_limit(300); // 5 minutes
            
            // Use the already initialized client and service
            $spreadsheetId = config('google.sheets.spreadsheet_id');
            $sheetName = config('google.sheets.sheet_name', 'Residents');
            
            // Read data from Google Sheets starting from row 2 (skip headers)
            $range = $sheetName . '!A2:Q1000'; // Read up to 1000 rows, columns A to Q (17 columns)
            
            $response = $this->sheetsService->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();
            
            if (empty($values)) {
                return [
                    'success' => false,
                    'message' => 'No data found in Google Sheets',
                    'synced_count' => 0,
                    'skipped_count' => 0,
                    'deleted_count' => 0,
                    'errors' => []
                ];
            }
            
            // Get all existing house numbers from database for deletion tracking
            $existingHouseNumbers = \App\Models\Resident::pluck('house_number')->toArray();
            $foundHouseNumbers = []; // Track house numbers found in Google Sheets
            $foundResidentKeys = []; // Track house_number + floor combinations found in Google Sheets
            
            $syncedCount = 0;
            $updatedCount = 0;
            $createdCount = 0;
            $skippedCount = 0;
            $duplicateCount = 0;
            $errors = [];
            $processedResidentKeys = []; // Track house_number + floor combinations we've already processed in this sync
            
            foreach ($values as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Map the row data to resident fields (based on our column structure)
                $residentData = $this->mapSheetRowToResident($row, $rowIndex + 2);
                
                if ($residentData === null) {
                    $skippedCount++;
                    continue;
                }
                
                // Track this house number as found in Google Sheets
                if (!empty($residentData['house_number'])) {
                    $foundHouseNumbers[] = $residentData['house_number'];
                    
                    // Create a unique key combining house_number and floor
                    $residentKey = $residentData['house_number'] . '|' . $residentData['floor'];
                    $foundResidentKeys[] = $residentKey;
                    
                    // Check for duplicates within this sync batch (same house_number + floor combination)
                    if (in_array($residentKey, $processedResidentKeys)) {
                        Log::warning("Duplicate house number + floor combination in Google Sheets", [
                            'house_number' => $residentData['house_number'],
                            'floor' => $residentData['floor'],
                            'resident_key' => $residentKey,
                            'row' => $rowIndex + 2
                        ]);
                        $duplicateCount++;
                        $skippedCount++;
                        continue;
                    }
                    $processedResidentKeys[] = $residentKey;
                }
                
                try {
                    // Check if resident already exists by house number AND floor
                    $existingResident = \App\Models\Resident::where('house_number', $residentData['house_number'])
                                                          ->where('floor', $residentData['floor'])
                                                          ->first();
                    
                    Log::info("Syncing resident data for house {$residentData['house_number']}, floor {$residentData['floor']}", [
                        'house_number' => $residentData['house_number'],
                        'floor' => $residentData['floor'],
                        'owner_name' => $residentData['owner_name'],
                        'exists' => $existingResident ? 'yes' : 'no',
                        'has_remarks' => !empty($residentData['remarks'])
                    ]);
                    
                    // Test JSON encoding before saving
                    if (!empty($residentData['remarks'])) {
                        $testJson = json_encode($residentData['remarks']);
                        if ($testJson === false) {
                            Log::error("JSON encoding failed for remarks", [
                                'house_number' => $residentData['house_number'],
                                'remarks_data' => $residentData['remarks'],
                                'json_error' => json_last_error_msg()
                            ]);
                            // Remove remarks if they can't be encoded
                            $residentData['remarks'] = null;
                        }
                    }
                    
                    if ($existingResident) {
                        // Update existing resident
                        $existingResident->update($residentData);
                        $updatedCount++;
                        $syncedCount++;
                        Log::info("Successfully updated resident {$residentData['house_number']}");
                    } else {
                        // Create new resident
                        \App\Models\Resident::create($residentData);
                        $createdCount++;
                        $syncedCount++;
                        Log::info("Successfully created new resident {$residentData['house_number']}");
                    }
                    
                } catch (\Exception $e) {
                    Log::error("Error syncing resident row " . ($rowIndex + 2), [
                        'error' => $e->getMessage(),
                        'house_number' => $residentData['house_number'] ?? 'unknown',
                        'remarks_data' => $residentData['remarks'] ?? null,
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errors[] = "Row " . ($rowIndex + 2) . " (House: " . ($residentData['house_number'] ?? 'unknown') . "): " . $e->getMessage();
                    $skippedCount++;
                }
            }
            
            // Handle deletions: Remove residents that exist in database but not in Google Sheets
            $deletedCount = 0;
            $houseNumbersToDelete = array_diff($existingHouseNumbers, $foundHouseNumbers);
            
            foreach ($houseNumbersToDelete as $houseNumber) {
                try {
                    $deleted = \App\Models\Resident::where('house_number', $houseNumber)->delete();
                    if ($deleted > 0) {
                        $deletedCount++;
                        Log::info("Deleted resident from house {$houseNumber} (not found in Google Sheets)");
                    }
                } catch (\Exception $e) {
                    Log::error("Error deleting resident from house {$houseNumber}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errors[] = "Failed to delete resident from house {$houseNumber}: " . $e->getMessage();
                }
            }
            
            // Build success message
            $message = "Successfully synced {$syncedCount} residents from Google Sheets";
            if ($createdCount > 0 && $updatedCount > 0) {
                $message .= " ({$createdCount} new, {$updatedCount} updated)";
            } elseif ($createdCount > 0) {
                $message .= " ({$createdCount} new)";
            } elseif ($updatedCount > 0) {
                $message .= " ({$updatedCount} updated)";
            }
            if ($duplicateCount > 0) {
                $message .= " ({$duplicateCount} duplicates skipped)";
            }
            if ($deletedCount > 0) {
                $message .= " and deleted {$deletedCount} residents that were removed from the sheet";
            }
            
            // Add detailed logging for debugging
            $totalRowsProcessed = count($values);
            $currentDbCount = \App\Models\Resident::count();
            
            Log::info("Sync Summary", [
                'total_rows_in_sheet' => $totalRowsProcessed,
                'synced_count' => $syncedCount,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'duplicate_count' => $duplicateCount,
                'deleted_count' => $deletedCount,
                'error_count' => count($errors),
                'current_db_count' => $currentDbCount,
                'found_house_numbers' => count($foundHouseNumbers),
                'existing_house_numbers' => count($existingHouseNumbers)
            ]);

            return [
                'success' => true,
                'message' => $message,
                'synced_count' => $syncedCount,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'duplicate_count' => $duplicateCount,
                'deleted_count' => $deletedCount,
                'total_rows_processed' => $totalRowsProcessed,
                'current_db_count' => $currentDbCount,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to sync from Google Sheets: ' . $e->getMessage(),
                'synced_count' => 0,
                'skipped_count' => 0,
                'deleted_count' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Preview what would be synced from Google Sheets (for debugging)
     */
    public function previewSyncFromSheet()
    {
        try {
            // Use the already initialized client and service
            $spreadsheetId = config('google.sheets.spreadsheet_id');
            $sheetName = config('google.sheets.sheet_name', 'Residents');
            
            // Read data from Google Sheets starting from row 2 (skip headers)
            $range = $sheetName . '!A2:Q1000'; // Read up to 1000 rows, columns A to Q (17 columns)
            
            $response = $this->sheetsService->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();
            
            if (empty($values)) {
                return [
                    'raw_data' => [],
                    'mapped_data' => [],
                    'row_count' => 0
                ];
            }
            
            $mappedData = [];
            $errors = [];
            
            foreach ($values as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                try {
                    // Map the row data to resident fields
                    $residentData = $this->mapSheetRowToResident($row, $rowIndex + 2);
                    
                    if ($residentData !== null) {
                        $mappedData[] = [
                            'row_number' => $rowIndex + 2,
                            'house_number' => $residentData['house_number'],
                            'owner_name' => $residentData['owner_name'],
                            'mapped_data' => $residentData,
                            'raw_row' => $row
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'row_number' => $rowIndex + 2,
                        'error' => $e->getMessage(),
                        'raw_row' => $row
                    ];
                }
            }
            
            return [
                'row_count' => count($values),
                'mapped_count' => count($mappedData),
                'error_count' => count($errors),
                'mapped_data' => $mappedData,
                'errors' => $errors,
                'sample_raw_data' => array_slice($values, 0, 5) // Show first 5 rows for reference
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'raw_data' => [],
                'mapped_data' => []
            ];
        }
    }
    
    /**
     * Map a Google Sheets row to resident data array
     */
    private function mapSheetRowToResident($row, $rowNumber)
    {
        try {
            // Log raw row data for debugging
            Log::info("Processing row {$rowNumber}", [
                'row_data_types' => array_map('gettype', $row),
                'row_count' => count($row),
                'house_number_raw' => $row[1] ?? 'missing'
            ]);
            
            // Skip if house number is empty (required field) - Column B after removing Flat Number
            if (empty(trim($row[1] ?? ''))) {
                Log::info("Skipping row {$rowNumber} - empty house number");
                return null;
            }
            
            // Map columns based on our setupHeaders structure (after removing Flat Number)
            try {
                $residentData = [
                    'house_number' => $this->safeCleanUtf8($row[1] ?? '', 'house_number', $rowNumber), // Column B: House Number
                    'flat_number' => $this->safeCleanUtf8($row[1] ?? '', 'flat_number', $rowNumber), // Use same as house_number for backward compatibility
                    'property_type' => $this->mapPropertyType(trim($row[3] ?? '')), // Column D: Property Type
                    'floor' => $this->mapFloor(trim($row[2] ?? '')), // Column C: Floor
                    'owner_name' => $this->safeCleanUtf8($row[4] ?? '', 'owner_name', $rowNumber), // Column E: Owner Name
                    'contact_number' => $this->cleanContactNumber(trim($row[5] ?? '')), // Column F: Contact Number
                    'email' => $this->cleanEmail(trim($row[6] ?? '')), // Column G: Email Address
                    'monthly_maintenance' => $this->parseFloat($row[10] ?? '0'), // Column K: Monthly Maintenance
                    'status' => $this->mapStatus(trim($row[8] ?? '')), // Column I: Status
                    'current_state' => $this->mapCurrentState(trim($row[9] ?? '')), // Column J: Current State
                    'move_in_date' => $this->parseDate($row[11] ?? ''), // Column L: Move-in Date
                    'emergency_contact' => $this->safeCleanUtf8($row[12] ?? '', 'emergency_contact', $rowNumber) ?: null, // Column M: Emergency Contact
                    'emergency_phone' => $this->cleanContactNumber(trim($row[13] ?? '')) ?: null, // Column N: Emergency Phone
                    'remarks' => $this->safeParseRemarks($row[14] ?? '', $rowNumber), // Column O: Remarks
                ];
            } catch (\Exception $e) {
                throw new \Exception("Error mapping fields: " . $e->getMessage());
            }
            
            Log::info("Mapped resident data for row {$rowNumber}", [
                'house_number' => $residentData['house_number'],
                'owner_name' => $residentData['owner_name'],
                'has_remarks' => !empty($residentData['remarks'])
            ]);
            
            // Validate required fields
            if (empty($residentData['house_number'])) {
                throw new \Exception("House number is required but empty");
            }
            
            if (empty($residentData['owner_name'])) {
                throw new \Exception("Owner name is required but empty for house {$residentData['house_number']}");
            }
            
            // Validate contact number if provided - be more permissive
            if (!empty($residentData['contact_number'])) {
                // Check if it contains at least some digits
                if (!preg_match('/\d/', $residentData['contact_number'])) {
                    throw new \Exception("Contact number must contain at least one digit for house {$residentData['house_number']}. Value: '{$residentData['contact_number']}'");
                }
                
                // Check for obviously invalid characters (letters, etc.)
                if (preg_match('/[a-zA-Z]/', $residentData['contact_number'])) {
                    throw new \Exception("Contact number contains letters for house {$residentData['house_number']}. Value: '{$residentData['contact_number']}'");
                }
            }
            
            // Validate email if provided (after cleaning)
            if (!empty($residentData['email'])) {
                if (!filter_var($residentData['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Invalid email format for house {$residentData['house_number']}. Email: '{$residentData['email']}'");
                }
            }
            
            return $residentData;
            
        } catch (\Exception $e) {
            throw new \Exception("Error mapping row {$rowNumber}: " . $e->getMessage());
        }
    }
    
    /**
     * Map property type from sheet to valid database value
     */
    private function mapPropertyType($value)
    {
        $value = strtolower(trim($value));
        $mapping = [
            'house' => 'house',
            '3 bhk flat' => '3bhk_flat',
            '3bhk flat' => '3bhk_flat',
            'villa' => 'villa',
            '2 bhk flat' => '2bhk_flat',
            '2bhk flat' => '2bhk_flat',
            '1 bhk flat' => '1bhk_flat',
            '1bhk flat' => '1bhk_flat',
            'estonia 1' => 'estonia_1',
            'estonia 2' => 'estonia_2',
            'plot' => 'plot'
        ];
        
        return $mapping[$value] ?? 'house'; // Default to house
    }
    
    /**
     * Map floor from sheet to valid database value
     */
    private function mapFloor($value)
    {
        $value = strtolower(trim($value));
        $mapping = [
            'ground floor' => 'ground_floor',
            'ground' => 'ground_floor',
            '1st floor' => '1st_floor',
            'first floor' => '1st_floor',
            '2nd floor' => '2nd_floor',
            'second floor' => '2nd_floor'
        ];
        
        return $mapping[$value] ?? null;
    }
    
    /**
     * Map status from sheet to valid database value
     */
    private function mapStatus($value)
    {
        $value = strtolower(trim($value));
        return in_array($value, ['active', 'inactive']) ? $value : 'active';
    }
    
    /**
     * Map current state from sheet to valid database value
     */
    private function mapCurrentState($value)
    {
        $value = strtolower(trim($value));
        $mapping = [
            'occupied' => 'occupied',
            'vacant' => 'vacant',
            'filled' => 'occupied' // Legacy mapping
        ];
        
        return $mapping[$value] ?? 'occupied';
    }
    
    /**
     * Parse float value from sheet
     */
    private function parseFloat($value)
    {
        $value = trim($value);
        $value = preg_replace('/[^\d.]/', '', $value); // Remove non-numeric characters except decimal
        return is_numeric($value) ? (float) $value : 0.0;
    }
    
    /**
     * Clean and validate email
     */
    private function cleanEmail($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $value = strtolower(trim($value));
        
        // Handle common "no email" indicators
        $noEmailIndicators = ['n/a', 'na', '-', 'nil', 'none', 'null', '0', 'no', 'not available'];
        if (in_array($value, $noEmailIndicators)) {
            return null;
        }
        
        // Remove any surrounding quotes or extra whitespace
        $value = trim($value, "\"' \t\n\r\0\x0B");
        
        // Check if it looks like a name (has spaces and no @ symbol)
        // Names typically have spaces and no @ symbol
        if (strpos($value, ' ') !== false && strpos($value, '@') === false) {
            // Check if it looks like a person's name (multiple words with letters)
            $words = explode(' ', $value);
            $isLikelyName = true;
            foreach ($words as $word) {
                // If any word has numbers or special characters (except common name chars), it might not be a name
                if (!preg_match('/^[a-z\'-]+$/i', $word)) {
                    $isLikelyName = false;
                    break;
                }
            }
            
            if ($isLikelyName && count($words) >= 2) {
                // This looks like a name, not an email
                return null;
            }
        }
        
        // If it doesn't contain @, it's probably not an email
        if (strpos($value, '@') === false) {
            return null;
        }
        
        return $value ?: null;
    }

    /**
     * Clean and validate contact number
     */
    private function cleanContactNumber($value)
    {
        if (empty($value)) {
            return '';
        }
        
        $value = trim($value);
        
        // Remove common unwanted characters but preserve phone-related ones
        $cleaned = preg_replace('/[^\d\+\-\s\(\)\.]/u', '', $value);
        
        // Remove extra spaces
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);
        
        return $cleaned;
    }

    /**
     * Parse date value from sheet
     */
    private function parseDate($value)
    {
        $value = trim($value);
        if (empty($value)) {
            return null;
        }
        
        try {
            $date = \Carbon\Carbon::parse($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse remarks from Google Sheets format back to JSON array
     */
    private function parseRemarks($value)
    {
        $value = trim($value);
        
        // Return null for empty or "No remarks"
        if (empty($value) || $value === 'No remarks') {
            return null;
        }
        
        // Clean up any malformed UTF-8 characters
        $value = $this->cleanUtf8($value);
        
        // If it's already JSON (unlikely but possible), try to decode it
        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            try {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (\Exception $e) {
                // Fall through to text parsing
            }
        }
        
        // Parse the bullet-point format we created
        $remarks = [];
        $lines = explode("\n", $value);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Clean the line of malformed UTF-8
            $line = $this->cleanUtf8($line);
            
            // Remove bullet point
            $line = preg_replace('/^[•·\-\*]\s*/', '', $line);
            
            // Try to extract text and author from format: "text (by author)"
            if (preg_match('/^(.+?)\s*\(by\s+(.+?)\)$/', $line, $matches)) {
                $remarks[] = [
                    'text' => $this->cleanUtf8(trim($matches[1])),
                    'added_by' => $this->cleanUtf8(trim($matches[2])),
                    'added_at' => now()->toISOString()
                ];
            } else {
                // Simple text without author info
                $remarks[] = [
                    'text' => $this->cleanUtf8($line),
                    'added_by' => 'Admin',
                    'added_at' => now()->toISOString()
                ];
            }
        }
        
        return empty($remarks) ? null : $remarks;
    }

    /**
     * Clean malformed UTF-8 characters from string
     */
    private function cleanUtf8($string)
    {
        if (empty($string)) {
            return $string;
        }
        
        // Convert to string if not already
        $string = (string) $string;
        
        try {
            // First, try to detect if it's already valid UTF-8
            if (mb_check_encoding($string, 'UTF-8')) {
                // Remove any remaining non-printable characters except newlines and tabs
                $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
                return trim($string);
            }
            
            // Try different encoding conversions
            $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'];
            
            foreach ($encodings as $encoding) {
                try {
                    if (mb_check_encoding($string, $encoding)) {
                        $converted = mb_convert_encoding($string, 'UTF-8', $encoding);
                        if (mb_check_encoding($converted, 'UTF-8')) {
                            // Success! Clean up non-printable characters
                            $converted = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $converted);
                            return trim($converted);
                        }
                    }
                } catch (\Exception $e) {
                    // Continue to next encoding
                    continue;
                }
            }
            
            // If all encoding attempts fail, use a more aggressive cleaning approach
            Log::warning("Unable to detect encoding for string, using aggressive cleaning", [
                'original_length' => strlen($string),
                'first_50_chars' => substr($string, 0, 50)
            ]);
            
            // Remove all non-ASCII characters and keep only printable ones
            $cleaned = preg_replace('/[^\x20-\x7E\x0A\x0D]/', '', $string);
            
            // If that results in empty string, try replacing with spaces instead
            if (empty(trim($cleaned))) {
                $cleaned = preg_replace('/[^\x20-\x7E\x0A\x0D]/', ' ', $string);
            }
            
            return trim($cleaned);
            
        } catch (\Exception $e) {
            Log::error("Critical error in cleanUtf8", [
                'error' => $e->getMessage(),
                'string_length' => strlen($string),
                'first_20_chars' => substr($string, 0, 20)
            ]);
            
            // Last resort: return only ASCII characters
            return trim(preg_replace('/[^\x20-\x7E]/', '', $string));
        }
    }

    /**
     * Safely clean UTF-8 with field-specific error handling
     */
    private function safeCleanUtf8($value, $fieldName, $rowNumber)
    {
        try {
            return $this->cleanUtf8(trim($value));
        } catch (\Exception $e) {
            Log::error("UTF-8 cleaning failed for field {$fieldName} in row {$rowNumber}", [
                'field' => $fieldName,
                'row' => $rowNumber,
                'error' => $e->getMessage(),
                'value_length' => strlen($value),
                'value_preview' => substr($value, 0, 50)
            ]);
            
            // Return a safe fallback
            return trim(preg_replace('/[^\x20-\x7E]/', '', $value));
        }
    }

    /**
     * Safely parse remarks with error handling
     */
    private function safeParseRemarks($value, $rowNumber)
    {
        try {
            return $this->parseRemarks($value);
        } catch (\Exception $e) {
            Log::error("Remarks parsing failed for row {$rowNumber}", [
                'row' => $rowNumber,
                'error' => $e->getMessage(),
                'value_length' => strlen($value),
                'value_preview' => substr($value, 0, 100)
            ]);
            
            // Return null if remarks can't be processed
            return null;
        }
    }

    /**
     * Get the service account email from the credentials
     */
    private function getServiceAccountEmail()
    {
        try {
            $serviceAccountPath = config('google.service_account_json_path');
            $credentials = json_decode(file_get_contents($serviceAccountPath), true);
            return $credentials['client_email'] ?? 'Unknown';
        } catch (Exception $e) {
            return 'Unable to retrieve';
        }
    }
}