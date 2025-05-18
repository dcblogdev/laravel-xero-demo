<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Contacts;

use Dcblogdev\Xero\Facades\Xero;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Title;
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

    public function render(): View
    {
        return view('livewire.admin.xero.contacts.index');
    }

    /** @return array<int, string> */
    public function contacts(): array
    {
        /*$contacts = Xero::contacts()
        ->filter('page', 1)
        ->filter('where', 'AccountNumber=="info@abfl.com"')
        ->filter('where', 'EmailAddress=="info@abfl.com"')
        ->filter('where', 'ContactID==Guid("74ea95ea-6e1e-435d-9c30-0dff8ae1bd80")')
        ->filter('searchTerm', 'info')
        ->filter('includeArchived', 'false')
        ->filter('order', 'name')
        ->filter('summaryOnly', 'true')
        ->get();*/

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

        $query
            ->filter('order', 'name');

        return $query->get();
    }

    public function resetFilters(): void
    {
        $this->reset();
    }

    public function exportToCsv()
    {
        try {
            $contacts = $this->contacts();

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
            $filename = 'xero-contacts-' . date('Y-m-d') . '.csv';
            $tempFile = tmpfile();
            $tempFilePath = stream_get_meta_data($tempFile)['uri'];

            // Write headers to CSV
            $file = fopen($tempFilePath, 'w');
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
                    isset($contact['UpdatedDateUTC']) ? date('Y-m-d H:i:s', strtotime(preg_replace('/\/Date\((\d+)\+\d+\)\//', '@$1', $contact['UpdatedDateUTC']))) : '',
                ];
                fputcsv($file, $row);
            }

            fclose($file);

            // Read the file content
            $fileContent = file_get_contents($tempFilePath);

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
        } catch (\Exception $e) {
            session()->flash('error', 'Error exporting contacts: ' . $e->getMessage());
            return null;
        }
    }
}
