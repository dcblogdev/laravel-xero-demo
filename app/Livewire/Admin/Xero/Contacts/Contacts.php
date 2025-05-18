<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Contacts;

use Dcblogdev\Xero\Facades\Xero;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Livewire\Component;

#[Title('Contacts')]
class Contacts extends Component
{
    public string $accountNumber = '';

    public string $email = '';

    public string $contactId = '';

    public string $searchTerm = '';

    public bool $includeArchived = false;

    public bool $openFilter = false;

    /** @var array<string, bool> */
    public array $selectedContacts = [];

    /**
     * The status of the most recent import job
     *
     * @var array<string, mixed>|null
     */
    public ?array $importStatus = null;

    public function mount(): void
    {
        // Check if there's an import job in progress
        $this->checkImportStatus();
    }

    public function render(): View
    {
        return view('livewire.admin.xero.contacts.index');
    }

    /**
     * Check the status of the most recent import job
     */
    public function checkImportStatus(): void
    {
        $jobId = session('xero_import_job_id');

        if ($jobId) {
            $this->importStatus = Cache::get('xero_import_' . $jobId);

            // If the job is completed or failed, we can remove the job ID from the session
            if ($this->importStatus && in_array($this->importStatus['status'], ['completed', 'failed', 'completed_with_errors'])) {
                // Keep the status in the session for this request, but clear it for future requests
                // This ensures the user sees the completion message once
                session()->forget('xero_import_job_id');
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function contacts(): array
    {
        $query = Xero::contacts();

        if ($this->accountNumber) {
            $query->filter('where', 'AccountNumber=="'.$this->accountNumber.'"');
        }

        if ($this->email) {
            $query->filter('where', 'EmailAddress=="'.$this->email.'"');
        }

        if ($this->contactId) {
            $query->filter('where', 'ContactID==Guid("'.$this->contactId.'")');
        }

        if ($this->searchTerm) {
            $query->filter('searchTerm', $this->searchTerm);
        }

        if ($this->includeArchived) {
            $query->filter('includeArchived', $this->includeArchived);
        }

        $query->filter('order', 'name');

        return $query->get() ?? [];
    }

    public function resetFilters(): void
    {
        $this->reset();
    }

    /**
     * Format a Xero date string to a readable date
     *
     * @param string $xeroDate The date string from Xero API
     * @return string Formatted date string
     */
    private function formatXeroDate(string $xeroDate): string
    {
        $pattern = '/\/Date\((\d+)\+\d+\)\//';
        $replacement = '@$1';
        $dateStr = preg_replace($pattern, $replacement, $xeroDate);

        if ($dateStr === null) {
            return '';
        }

        $timestamp = strtotime($dateStr);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    public function selectAllContacts(bool $checked): void
    {
        $contacts = $this->contacts();

        if ($checked) {
            // Select all contacts
            foreach ($contacts as $contact) {
                $this->selectedContacts[$contact['ContactID']] = true;
            }
        } else {
            // Deselect all contacts
            foreach ($contacts as $contact) {
                $this->selectedContacts[$contact['ContactID']] = false;
            }
        }
    }

    /**
     * Export contacts to a CSV file
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|null
     */
    public function exportToCsv()
    {
        try {
            $allContacts = $this->contacts();

            // Check if any contacts are selected
            $hasSelectedContacts = count(array_filter($this->selectedContacts)) > 0;

            // If contacts are selected, filter the contacts array to only include selected ones
            $contacts = $allContacts;
            if ($hasSelectedContacts) {
                $contacts = array_filter($allContacts, function ($contact) {
                    return isset($this->selectedContacts[$contact['ContactID']]) && $this->selectedContacts[$contact['ContactID']];
                });
            }

            // If no contacts are left after filtering (which shouldn't happen), use all contacts
            if (empty($contacts)) {
                $contacts = $allContacts;
                session()->flash('message', 'No contacts were selected. Exporting all contacts.');
            } elseif ($hasSelectedContacts) {
                session()->flash('message', count($contacts).' selected contacts exported successfully!');
            } else {
                session()->flash('message', 'All contacts exported successfully!');
            }

            // Define CSV headers
            $headers = [
                'Name',
                'First Name',
                'Last Name',
                'Email',
                'Account Number',
                'Status',
                'Is Customer',
                'Is Supplier',
                'Website',
                'Tax Number',
                'Updated Date',
            ];

            // Create a temporary file
            $filename = 'xero-contacts-'.date('Y-m-d').'.csv';
            $tempFile = tmpfile();
            if ($tempFile === false) {
                throw new Exception('Failed to create temporary file');
            }
            $tempFilePath = stream_get_meta_data($tempFile)['uri'];

            // Write headers to CSV
            $file = fopen($tempFilePath, 'w');
            if ($file === false) {
                throw new Exception('Failed to open file for writing');
            }
            fputcsv($file, $headers);

            // Write contact data to CSV
            foreach ($contacts as $contact) {
                $row = [
                    $contact['Name'] ?? '',
                    $contact['FirstName'] ?? '',
                    $contact['LastName'] ?? '',
                    $contact['EmailAddress'] ?? '',
                    $contact['AccountNumber'] ?? '',
                    $contact['ContactStatus'] ?? '',
                    isset($contact['IsCustomer']) ? ($contact['IsCustomer'] ? 'Yes' : 'No') : '',
                    isset($contact['IsSupplier']) ? ($contact['IsSupplier'] ? 'Yes' : 'No') : '',
                    $contact['Website'] ?? '',
                    $contact['TaxNumber'] ?? '',
                    isset($contact['UpdatedDateUTC']) && is_string($contact['UpdatedDateUTC']) ? $this->formatXeroDate($contact['UpdatedDateUTC']) : '',
                ];
                fputcsv($file, $row);
            }

            fclose($file);

            // Read the file content
            $fileContent = file_get_contents($tempFilePath);
            if ($fileContent === false) {
                throw new Exception('Failed to read file content');
            }

            // Close the temporary file
            fclose($tempFile);

            // Set success message
            session()->flash('message', 'Contacts exported successfully!');

            // Return the response
            return response()->streamDownload(function () use ($fileContent) {
                echo $fileContent;
            }, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        } catch (Exception $e) {
            session()->flash('error', 'Error exporting contacts: '.$e->getMessage());

            return null;
        }
    }
}
