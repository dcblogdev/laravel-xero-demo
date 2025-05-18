<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\Xero\ContactDTO;
use Dcblogdev\Xero\Facades\Xero;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportXeroContactsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600; // 1 hour

    /**
     * A unique identifier for the import job
     */
    protected string $jobId;

    /**
     * Cache of contacts to reduce API calls
     *
     * @var array<string, array<string, mixed>>
     */
    private static $contactsCache = [];

    /**
     * @param  array<int, array<string, string|null>>  $csvData
     * @param  array<string, string>  $columnMapping
     * @param  string|null  $jobId  A unique identifier for this import job
     */
    public function __construct(
        protected array $csvData,
        protected array $columnMapping,
        ?string $jobId = null
    ) {
        $this->jobId = $jobId ?? uniqid('import_', true);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Update status to indicate job has started
        $this->updateStatus('processing', 'Import in progress', 0, 0, 0);

        $successCount = 0;
        $updateCount = 0;
        $errorCount = 0;
        $errors = [];
        $totalRows = count($this->csvData);
        $processedRows = 0;

        // Process contacts in batches to reduce API rate limit issues
        $batchSize = 10; // Process 10 contacts at a time
        $batches = array_chunk($this->csvData, $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            // Add a delay between batches to avoid hitting rate limits
            if ($batchIndex > 0) {
                sleep(2); // 2 second delay between batches
            }

            foreach ($batch as $rowIndex => $row) {
                try {
                    $contactData = $this->mapRowToContactDTO($row);

                    // Skip if no name is provided
                    if (empty($contactData->name)) {
                        $errors[] = 'Row '.($rowIndex + 2).': Name is required.';
                        $errorCount++;
                        $processedRows++;
                        $this->updateStatus('processing', 'Import in progress', $successCount, $updateCount, $errorCount, $processedRows, $totalRows);

                        continue;
                    }

                    // Check if contact already exists
                    try {
                        $existingContact = $this->findExistingContact(
                            $contactData->name,
                            $contactData->emailAddress,
                            $contactData->accountNumber
                        );
                    } catch (Exception $e) {
                        $errorMessage = $e->getMessage() ?: 'Unknown error finding existing contact';
                        $errors[] = 'Row '.($rowIndex + 2).': '.$errorMessage;
                        $errorCount++;
                        Log::error('Error finding existing contact', [
                            'row' => $rowIndex + 2,
                            'name' => $contactData->name,
                            'email' => $contactData->emailAddress,
                            'accountNumber' => $contactData->accountNumber,
                            'exception' => $e,
                        ]);
                        $processedRows++;
                        $this->updateStatus('processing', 'Import in progress', $successCount, $updateCount, $errorCount, $processedRows, $totalRows);

                        continue;
                    }

                    if ($existingContact) {
                        // Update existing contact
                        try {
                            $contactId = $existingContact['ContactID'];
                            $response = Xero::contacts()->update($contactId, $contactData->toArray());

                            if (isset($response['Id']) || isset($response['ContactID'])) {
                                $updateCount++;
                            } else {
                                $errors[] = 'Row '.($rowIndex + 2).': Failed to update contact - no ID returned.';
                                $errorCount++;
                            }
                        } catch (Exception $e) {
                            $errorMessage = $e->getMessage() ?: 'Unknown error updating contact';
                            $errors[] = 'Row '.($rowIndex + 2).': '.$errorMessage;
                            $errorCount++;
                            Log::error('Error updating contact', [
                                'row' => $rowIndex + 2,
                                'contact_id' => $existingContact['ContactID'] ?? 'unknown',
                                'name' => $contactData->name,
                                'data' => $contactData->toArray(),
                                'exception' => $e,
                            ]);
                        }
                    } else {
                        // Create new contact
                        try {
                            $response = Xero::contacts()->store($contactData->toArray());

                            if (isset($response['Id']) || isset($response['ContactID'])) {
                                $successCount++;
                            } else {
                                $errors[] = 'Row '.($rowIndex + 2).': Failed to create contact - no ID returned.';
                                $errorCount++;
                            }
                        } catch (Exception $e) {
                            $errorMessage = $e->getMessage() ?: 'Unknown error creating contact';
                            $errors[] = 'Row '.($rowIndex + 2).': '.$errorMessage;
                            $errorCount++;
                            Log::error('Error importing contact', [
                                'row' => $rowIndex + 2,
                                'name' => $contactData->name,
                                'data' => $contactData->toArray(),
                                'exception' => $e,
                            ]);
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = 'Row '.($rowIndex + 2).': '.$e->getMessage();
                    $errorCount++;
                    Log::error('Error importing contact: '.$e->getMessage(), [
                        'row' => $rowIndex + 2,
                        'exception' => $e,
                    ]);
                }

                $processedRows++;

                // Update status every 10 rows or at the end
                if ($processedRows % 10 === 0 || $processedRows === $totalRows) {
                    $this->updateStatus('processing', 'Import in progress', $successCount, $updateCount, $errorCount, $processedRows, $totalRows);
                }
            }
        }

        // Update final status
        $message = '';
        if ($successCount > 0) {
            $message .= $successCount.' new contacts created. ';
        }
        if ($updateCount > 0) {
            $message .= $updateCount.' existing contacts updated. ';
        }
        if ($errorCount > 0) {
            $message .= $errorCount.' contacts failed to import. ';
        }

        $status = $errorCount > 0 ? 'completed_with_errors' : 'completed';
        $this->updateStatus($status, mb_trim($message), $successCount, $updateCount, $errorCount, $totalRows, $totalRows, $errors);

        // Log the results
        Log::info('Xero contacts import completed', [
            'job_id' => $this->jobId,
            'new_contacts' => $successCount,
            'updated_contacts' => $updateCount,
            'errors' => $errorCount,
            'error_details' => $errors,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Xero contacts import job failed', [
            'job_id' => $this->jobId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->updateStatus(
            'failed',
            'Import failed: '.$exception->getMessage(),
            0,
            0,
            count($this->csvData),
            0,
            count($this->csvData),
            [$exception->getMessage()]
        );
    }

    /**
     * Map a CSV row to a ContactDTO object
     *
     * @param  array<string, string|null>  $row
     */
    private function mapRowToContactDTO(array $row): ContactDTO
    {
        $mappedData = [];

        foreach ($this->columnMapping as $header => $field) {
            $mappedData[$field] = $row[$header] ?? '';
        }

        // Process boolean fields
        $isSupplier = false;
        if (isset($mappedData['isSupplier'])) {
            $isSupplier = $this->parseBoolean($mappedData['isSupplier']);
        }

        $isCustomer = true; // Default to true
        if (isset($mappedData['isCustomer'])) {
            $isCustomer = $this->parseBoolean($mappedData['isCustomer']);
        }

        // Create an addresses array if address fields are present
        $addresses = [];
        if (
            ! empty($mappedData['addressLine1']) ||
            ! empty($mappedData['city']) ||
            ! empty($mappedData['region']) ||
            ! empty($mappedData['postalCode']) ||
            ! empty($mappedData['country'])
        ) {
            $addresses[] = ContactDTO::createAddress(
                'POBOX', // Default address type
                $mappedData['addressLine1'] ?? null,
                null,
                null,
                null,
                $mappedData['city'] ?? null,
                $mappedData['region'] ?? null,
                $mappedData['postalCode'] ?? null,
                $mappedData['country'] ?? null
            );
        }

        // Create a phones array if phone fields are present
        $phones = [];
        if (
            ! empty($mappedData['phoneNumber']) ||
            ! empty($mappedData['phoneAreaCode']) ||
            ! empty($mappedData['phoneCountryCode'])
        ) {
            $phones[] = ContactDTO::createPhone(
                'DEFAULT', // Default phone type
                $mappedData['phoneNumber'] ?? null,
                $mappedData['phoneAreaCode'] ?? null,
                $mappedData['phoneCountryCode'] ?? null
            );
        }

        return new ContactDTO(
            name: $mappedData['name'] ?? null,
            firstName: $mappedData['firstName'] ?? null,
            lastName: $mappedData['lastName'] ?? null,
            emailAddress: $mappedData['emailAddress'] ?? null,
            accountNumber: $mappedData['accountNumber'] ?? null,
            bankAccountDetails: $mappedData['bankAccountDetails'] ?? null,
            taxNumber: $mappedData['taxNumber'] ?? null,
            isSupplier: $isSupplier,
            isCustomer: $isCustomer,
            website: $mappedData['website'] ?? null,
            addresses: $addresses,
            phones: $phones
        );
    }

    /**
     * Parse a string value to boolean
     */
    private function parseBoolean(string $value): bool
    {
        $value = mb_strtolower(mb_trim($value));

        return in_array($value, ['yes', 'true', '1', 'y', 'on']);
    }

    /**
     * Update the status of the import job in the cache
     *
     * @param  string  $status  The status of the job (queued, processing, completed, failed, completed_with_errors)
     * @param  string  $message  A message describing the current status
     * @param  int  $successCount  The number of contacts successfully created
     * @param  int  $updateCount  The number of contacts successfully updated
     * @param  int  $errorCount  The number of contacts that failed to import
     * @param  int  $processedRows  The number of rows processed so far
     * @param  int  $totalRows  The total number of rows to process
     * @param  array<int, string>  $errors  An array of error messages
     */
    private function updateStatus(
        string $status,
        string $message,
        int $successCount = 0,
        int $updateCount = 0,
        int $errorCount = 0,
        int $processedRows = 0,
        int $totalRows = 0,
        array $errors = []
    ): void {
        $statusData = [
            'status' => $status,
            'message' => $message,
            'success_count' => $successCount,
            'update_count' => $updateCount,
            'error_count' => $errorCount,
            'processed_rows' => $processedRows,
            'total_rows' => $totalRows,
            'progress' => $totalRows > 0 ? round(($processedRows / $totalRows) * 100) : 0,
            'errors' => array_slice($errors, 0, 100), // Limit to first 100 errors
            'updated_at' => now()->toIso8601String(),
        ];

        // Store the status in the cache for 24 hours
        Cache::put('xero_import_'.$this->jobId, $statusData, now()->addDay());
    }

    /**
     * Check if a contact already exists in Xero
     *
     * @param  string  $name  Contact name
     * @param  string|null  $email  Contact email
     * @param  string|null  $accountNumber  Contact account number
     * @return array<string, mixed>|null The existing contact or null if not found
     *
     * @throws Exception If there's an error communicating with the Xero API
     */
    private function findExistingContact(string $name, ?string $email = null, ?string $accountNumber = null): ?array
    {
        // Generate cache keys for each search criteria
        $nameKey = 'name_'.md5($name);
        $emailKey = $email ? 'email_'.md5($email) : null;
        $accountNumberKey = $accountNumber ? 'account_'.md5($accountNumber) : null;

        // Check cache first
        if (isset(self::$contactsCache[$nameKey])) {
            return self::$contactsCache[$nameKey];
        }

        if ($emailKey && isset(self::$contactsCache[$emailKey])) {
            return self::$contactsCache[$emailKey];
        }

        if ($accountNumberKey && isset(self::$contactsCache[$accountNumberKey])) {
            return self::$contactsCache[$accountNumberKey];
        }

        try {
            // Build a combined search query if possible
            $searchTerms = [];
            $searchTerms[] = 'Name=="'.addslashes($name).'"';

            if ($email) {
                $searchTerms[] = 'EmailAddress=="'.$email.'"';
            }

            if ($accountNumber) {
                $searchTerms[] = 'AccountNumber=="'.$accountNumber.'"';
            }

            // Try to find contact with a single API call using OR conditions
            try {
                $query = Xero::contacts();

                // If we have multiple search terms, use OR conditions
                if (count($searchTerms) > 1) {
                    $whereClause = implode(' OR ', $searchTerms);
                    $query->filter('where', $whereClause);
                } else {
                    // Otherwise just use the single condition
                    $query->filter('where', $searchTerms[0]);
                }

                $results = $query->get();

                if (! empty($results)) {
                    $contact = $results[0];

                    // Cache the result under all applicable keys
                    self::$contactsCache[$nameKey] = $contact;

                    if ($emailKey && ! empty($contact['EmailAddress'])) {
                        self::$contactsCache[$emailKey] = $contact;
                    }

                    if ($accountNumberKey && ! empty($contact['AccountNumber'])) {
                        self::$contactsCache[$accountNumberKey] = $contact;
                    }

                    return $contact;
                }
            } catch (Exception $e) {
                Log::warning('Error finding contact with combined query', [
                    'search_terms' => $searchTerms,
                    'error' => $e->getMessage(),
                ]);

                // If the combined query fails, fall back to individual queries
                // but only if the error suggests it's a query syntax issue
                if (mb_strpos($e->getMessage(), 'syntax') !== false) {
                    // Fall back to individual queries (original implementation)
                    return $this->findExistingContactFallback($name, $email, $accountNumber);
                }

                // For other errors (like rate limiting), re-throw to be handled by retry mechanism
                throw $e;
            }

            // If we get here, no contact was found
            return null;
        } catch (Exception $e) {
            // Log the error with detailed context
            Log::error('Error finding existing contact', [
                'name' => $name,
                'email' => $email,
                'accountNumber' => $accountNumber,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception to be handled by the caller
            throw new Exception('Error finding existing contact: '.($e->getMessage() ?: 'Unknown error'), 0, $e);
        }
    }

    /**
     * Fallback method to find existing contact using individual queries
     * Used when the combined query fails due to syntax issues
     *
     * @param  string  $name  Contact name
     * @param  string|null  $email  Contact email
     * @param  string|null  $accountNumber  Contact account number
     * @return array<string, mixed>|null The existing contact or null if not found
     */
    private function findExistingContactFallback(string $name, ?string $email = null, ?string $accountNumber = null): ?array
    {
        // First try to find by exact name match
        try {
            $query = Xero::contacts();
            $query->filter('where', 'Name=="'.addslashes($name).'"');
            $results = $query->get();

            if (! empty($results)) {
                $contact = $results[0];
                self::$contactsCache['name_'.md5($name)] = $contact;

                return $contact;
            }
        } catch (Exception $e) {
            Log::warning('Error finding contact by name', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            // Continue to try other search methods
        }

        // If not found by name and we have an email, try by email
        if ($email) {
            try {
                $query = Xero::contacts();
                $query->filter('where', 'EmailAddress=="'.$email.'"');
                $results = $query->get();

                if (! empty($results)) {
                    $contact = $results[0];
                    self::$contactsCache['email_'.md5($email)] = $contact;

                    return $contact;
                }
            } catch (Exception $e) {
                Log::warning('Error finding contact by email', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                // Continue to try other search methods
            }
        }

        // If not found by email and we have an account number, try by account number
        if ($accountNumber) {
            try {
                $query = Xero::contacts();
                $query->filter('where', 'AccountNumber=="'.$accountNumber.'"');
                $results = $query->get();

                if (! empty($results)) {
                    $contact = $results[0];
                    self::$contactsCache['account_'.md5($accountNumber)] = $contact;

                    return $contact;
                }
            } catch (Exception $e) {
                Log::warning('Error finding contact by account number', [
                    'accountNumber' => $accountNumber,
                    'error' => $e->getMessage(),
                ]);
                // Continue to try other search methods
            }
        }

        // If we get here, no contact was found by any method
        return null;
    }
}
