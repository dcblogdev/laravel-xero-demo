<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Contacts;

use Dcblogdev\Xero\DTOs\ContactDTO;
use App\Jobs\ImportXeroContactsJob;
use Dcblogdev\Xero\Facades\Xero;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Log;

#[Title('Import Contacts')]
class ImportContacts extends Component
{
    use WithFileUploads;

    public ?\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $csvFile = null;

    public bool $fileUploaded = false;

    public bool $mappingRequired = false;

    /** @var array<int, array<string, string|null>> */
    public array $csvData = [];

    /** @var array<int, string> */
    public array $headers = [];

    /** @var array<string, string> */
    public array $columnMapping = [];

    /** @var array<string, string> */
    public array $availableFields = [
        'name' => 'Name',
        'firstName' => 'First Name',
        'lastName' => 'Last Name',
        'emailAddress' => 'Email Address',
        'accountNumber' => 'Account Number',
        'bankAccountDetails' => 'Bank Account Details',
        'taxNumber' => 'Tax Number',
        'website' => 'Website',
        'isSupplier' => 'Is Supplier',
        'isCustomer' => 'Is Customer',
        'addressLine1' => 'Address Line 1',
        'city' => 'City',
        'region' => 'Region',
        'postalCode' => 'Postal Code',
        'country' => 'Country',
        'phoneNumber' => 'Phone Number',
        'phoneAreaCode' => 'Phone Area Code',
        'phoneCountryCode' => 'Phone Country Code',
    ];

    /** @var array<string, string> */
    protected array $rules = [
        'csvFile' => 'required|file|mimes:csv,txt|max:1024',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'csvFile.required' => 'Please select a CSV file to upload.',
        'csvFile.file' => 'The uploaded file must be a valid file.',
        'csvFile.mimes' => 'The file must be a CSV file.',
        'csvFile.max' => 'The file size must not exceed 1MB.',
    ];

    public function render(): View
    {
        return view('livewire.admin.xero.contacts.import');
    }

    public function uploadCSV(): void
    {
        $this->validate();

        // Ensure csvFile is not null after validation
        if (! $this->csvFile) {
            session()->flash('error', 'No file was uploaded.');

            return;
        }

        try {
            // Store the file temporarily
            $path = $this->csvFile->store('temp');
            if ($path === false) {
                throw new Exception('Failed to store uploaded file');
            }

            // At this point, $path is definitely a string
            /** @var string $path */

            // Get the full path to the file
            $fullPath = Storage::path($path);

            // Parse the CSV file
            $this->parseCSV($fullPath);

            // Remove the temporary file
            Storage::delete($path);

            $this->fileUploaded = true;

            // Try to automatically map columns
            $this->autoMapColumns();

            // Check if manual mapping is required
            $this->checkIfMappingRequired();

        } catch (Exception $e) {
            session()->flash('error', 'Error uploading CSV: '.$e->getMessage());
        }
    }

    /**
     * Update column mapping
     */
    public function updateMapping(string $header, string $field): void
    {
        if ($field === '') {
            unset($this->columnMapping[$header]);
        } else {
            $this->columnMapping[$header] = $field;
        }

        // Re-check if mapping is still required
        $this->checkIfMappingRequired();
    }

    /**
     * Import contacts from CSV
     */
    public function importContacts(): void
    {
        if (! $this->fileUploaded) {
            session()->flash('error', 'Please upload a CSV file first.');

            return;
        }

        if ($this->mappingRequired && ! $this->isHeaderMapped('name')) {
            session()->flash('error', 'Please map the Name field before importing.');

            return;
        }

        try {
            // Count the number of contacts to be imported
            $contactCount = count($this->csvData);

            // Generate a unique job ID
            $jobId = uniqid('import_', true);

            // Dispatch the job to process the import in the background
            ImportXeroContactsJob::dispatch($this->csvData, $this->columnMapping, $jobId);

            // Store the job ID in the session
            session()->put('xero_import_job_id', $jobId);

            // Set success message
            session()->flash('message', 'Import started! '.$contactCount.' contacts are being processed in the background. You can navigate away from this page.');

            // Reset the component
            $this->reset(['csvFile', 'fileUploaded', 'mappingRequired', 'csvData', 'headers', 'columnMapping']);

            // Redirect to the contact index
            $this->redirect(route('xero.contacts.index'));

        } catch (Exception $e) {
            session()->flash('error', 'Error starting import: '.$e->getMessage());
        }
    }

    /**
     * Cancel import and return to contacts index
     */
    public function cancel(): void
    {
        $this->redirect(route('xero.contacts.index'));
    }

    /**
     * Parse the CSV file and extract headers and data
     */
    private function parseCSV(string $filePath): void
    {
        $file = fopen($filePath, 'r');
        if ($file === false) {
            throw new Exception('Failed to open CSV file');
        }

        // Get headers
        $headers = fgetcsv($file);
        if ($headers === false) {
            fclose($file);
            throw new Exception('CSV file is empty or invalid');
        }

        $this->headers = array_map(function ($value) {
            return mb_trim((string) $value);
        }, $headers);

        // Get data (limit to first 100 rows for preview)
        $this->csvData = [];
        $rowCount = 0;
        while (($row = fgetcsv($file)) !== false && $rowCount < 100) {
            if (count($row) === count($this->headers)) {
                $rowData = [];
                foreach ($row as $index => $value) {
                    $rowData[$this->headers[$index]] = $value;
                }
                $this->csvData[] = $rowData;
                $rowCount++;
            }
        }

        fclose($file);
    }

    /**
     * Try to automatically map CSV columns to Xero contact fields
     */
    private function autoMapColumns(): void
    {
        $this->columnMapping = [];

        foreach ($this->headers as $header) {
            $headerLower = mb_strtolower($header);

            // Try to find a match in available fields
            foreach ($this->availableFields as $field => $label) {
                if (
                    $headerLower === mb_strtolower($field) ||
                    $headerLower === mb_strtolower($label) ||
                    $headerLower === str_replace(' ', '', mb_strtolower($label)) ||
                    $headerLower === str_replace('_', '', mb_strtolower($field))
                ) {
                    $this->columnMapping[$header] = $field;
                    break;
                }
            }
        }
    }

    /**
     * Check if manual mapping is required
     */
    private function checkIfMappingRequired(): void
    {
        // Always show mapping interface to allow users to correct any mapping issues
        $this->mappingRequired = true;
    }

    /**
     * Check if a specific field is mapped
     */
    private function isHeaderMapped(string $fieldName): bool
    {
        return in_array($fieldName, $this->columnMapping);
    }

    /**
     * Check if a contact already exists in Xero
     *
     * @param  string  $name  Contact name
     * @param  string|null  $email  Contact email
     * @param  string|null  $accountNumber  Contact account number
     * @return array<string, mixed>|null The existing contact or null if not found
     *
     * @internal This method is used by the ImportXeroContactsJob
     *
     * @phpstan-ignore-next-line
     */
    private function findExistingContact(string $name, ?string $email = null, ?string $accountNumber = null): ?array
    {
        try {
            $query = Xero::contacts();

            // First try to find by exact name match
            $query->filter('where', 'Name=="'.addslashes($name).'"');
            $results = $query->get();

            if (! empty($results)) {
                return $results[0];
            }

            // If not found by name and we have an email, try by email
            if ($email) {
                $query = Xero::contacts();
                $query->filter('where', 'EmailAddress=="'.$email.'"');
                $results = $query->get();

                if (! empty($results)) {
                    return $results[0];
                }
            }

            // If not found by email and we have an account number, try by account number
            if ($accountNumber) {
                $query = Xero::contacts();
                $query->filter('where', 'AccountNumber=="'.$accountNumber.'"');
                $results = $query->get();

                if (! empty($results)) {
                    return $results[0];
                }
            }

            return null;
        } catch (Exception $e) {
            // If there's an error, log it but return null to continue with creating a new contact
            Log::error('Error finding existing contact: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Map a CSV row to a ContactDTO object
     *
     * @param  array<string, string>  $row
     *
     * @internal This method is used by the ImportXeroContactsJob
     *
     * @phpstan-ignore-next-line
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
}
