<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Invoices;

use Dcblogdev\Xero\Facades\Xero;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Invoices')]
class Invoices extends Component
{
    public string $invoiceNumber = '';

    public string $reference = '';

    public string $contactId = '';

    public string $searchTerm = '';

    public string $status = '';

    public string $fromDate = '';

    public string $toDate = '';

    public bool $openFilter = false;

    /** @var array<string, bool> */
    public array $selectedInvoices = [];

    public function render(): View
    {
        return view('livewire.admin.xero.invoices.index');
    }

    /** @return array<int, array<string, mixed>> */
    public function invoices(): array
    {
        $query = Xero::invoices();

        if ($this->invoiceNumber) {
            $query->filter('where', 'InvoiceNumber=="'.$this->invoiceNumber.'"');
        }

        if ($this->reference) {
            $query->filter('where', 'Reference=="'.$this->reference.'"');
        }

        if ($this->contactId) {
            $query->filter('where', 'ContactID==Guid("'.$this->contactId.'")');
        }

        if ($this->searchTerm) {
            $query->filter('searchTerm', $this->searchTerm);
        }

        if ($this->status) {
            $query->filter('where', 'Status=="'.$this->status.'"');
        }

        if ($this->fromDate) {
            $query->filter('where', 'Date>=DateTime('.$this->fromDate.')');
        }

        if ($this->toDate) {
            $query->filter('where', 'Date<=DateTime('.$this->toDate.')');
        }

        $query->filter('order', 'Date DESC');

        return $query->get();
    }

    public function resetFilters(): void
    {
        $this->reset();
    }

    public function selectAllInvoices(bool $checked): void
    {
        $invoices = $this->invoices();

        if ($checked) {
            // Select all invoices
            foreach ($invoices as $invoice) {
                $this->selectedInvoices[$invoice['InvoiceID']] = true;
            }
        } else {
            // Deselect all invoices
            foreach ($invoices as $invoice) {
                $this->selectedInvoices[$invoice['InvoiceID']] = false;
            }
        }
    }

    /**
     * Export invoices to CSV
     */
    public function exportToCsv(): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $allInvoices = $this->invoices();

            // Check if any invoices are selected
            $hasSelectedInvoices = count(array_filter($this->selectedInvoices)) > 0;

            // If invoices are selected, filter the invoice array to only include selected ones
            $invoices = $allInvoices;
            if ($hasSelectedInvoices) {
                $invoices = array_filter($allInvoices, function ($invoice) {
                    return isset($this->selectedInvoices[$invoice['InvoiceID']]) && $this->selectedInvoices[$invoice['InvoiceID']];
                });
            }

            // If no invoices are left after filtering (which shouldn't happen), use all invoices
            if (empty($invoices)) {
                $invoices = $allInvoices;
                session()->flash('message', 'No invoices were selected. Exporting all invoices.');
            } elseif ($hasSelectedInvoices) {
                session()->flash('message', count($invoices).' selected invoices exported successfully!');
            } else {
                session()->flash('message', 'All invoices exported successfully!');
            }

            // Define CSV headers
            $headers = [
                'Invoice Number',
                'Reference',
                'Date',
                'Due Date',
                'Status',
                'Contact Name',
                'Type',
                'Sub Total',
                'Total Tax',
                'Total',
                'Currency',
                'Updated Date',
            ];

            // Create a temporary file
            $filename = 'xero-invoices-'.date('Y-m-d').'.csv';
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

            // Write invoice data to CSV
            foreach ($invoices as $invoice) {
                $row = [
                    $invoice['InvoiceNumber'] ?? '',
                    $invoice['Reference'] ?? '',
                    isset($invoice['Date']) ? Xero::formatDate($invoice['Date'], 'd/m/Y') : '',
                    isset($invoice['DueDate']) ? Xero::formatDate($invoice['DueDate'], 'd/m/Y') : '',
                    $invoice['Status'] ?? '',
                    $invoice['Contact']['Name'] ?? '',
                    $invoice['Type'] ?? '',
                    $invoice['SubTotal'] ?? '',
                    $invoice['TotalTax'] ?? '',
                    $invoice['Total'] ?? '',
                    $invoice['CurrencyCode'] ?? '',
                    isset($invoice['UpdatedDateUTC']) && is_string($invoice['UpdatedDateUTC']) ? Xero::formatDate($invoice['UpdatedDateUTC']) : '',
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
            session()->flash('message', 'Invoices exported successfully!');

            // Return the response
            return response()->streamDownload(function () use ($fileContent) {
                echo $fileContent;
            }, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        } catch (Exception $e) {
            session()->flash('error', 'Error exporting invoices: '.$e->getMessage());

            return null;
        }
    }
}
