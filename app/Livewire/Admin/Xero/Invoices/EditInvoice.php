<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Invoices;

use Dcblogdev\Xero\DTOs\InvoiceDTO;
use Dcblogdev\Xero\Facades\Xero;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit Invoice')]
class EditInvoice extends Component
{
    public string $invoiceId = '';

    // Basic invoice fields
    public string $type = 'ACCREC'; // ACCREC for sales invoices, ACCPAY for bills

    public string $invoiceNumber = '';

    public string $reference = '';

    public string $date = '';

    public string $dueDate = '';

    public string $status = 'DRAFT';

    public string $lineAmountTypes = 'Exclusive'; // Exclusive, Inclusive, NoTax

    public string $currencyCode = '';

    // Contact fields
    public string $contactId = '';

    public string $contactName = '';

    /** @var array<int, array<string, mixed>> */
    public array $lineItems = [];

    /** @var array<string, string> */
    protected array $rules = [
        'type' => 'required|string|in:ACCREC,ACCPAY',
        'invoiceNumber' => 'nullable|string|max:255',
        'reference' => 'nullable|string|max:255',
        'date' => 'required|date',
        'dueDate' => 'required|date|after_or_equal:date',
        'status' => 'required|string|in:DRAFT,SUBMITTED,AUTHORISED',
        'lineAmountTypes' => 'required|string|in:Exclusive,Inclusive,NoTax',
        'currencyCode' => 'nullable|string|size:3',
        'contactId' => 'required|string',
        'lineItems' => 'required|array|min:1',
        'lineItems.*.description' => 'required|string|max:4000',
        'lineItems.*.quantity' => 'required|numeric|min:0',
        'lineItems.*.unitAmount' => 'required|numeric|min:0',
        'lineItems.*.accountCode' => 'required|string|max:10',
        'lineItems.*.taxType' => 'nullable|string|max:50',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'type.required' => 'The invoice type is required.',
        'type.in' => 'The invoice type must be either ACCREC (Sales) or ACCPAY (Bills).',
        'date.required' => 'The invoice date is required.',
        'date.date' => 'The invoice date must be a valid date.',
        'dueDate.required' => 'The due date is required.',
        'dueDate.date' => 'The due date must be a valid date.',
        'dueDate.after_or_equal' => 'The due date must be on or after the invoice date.',
        'status.required' => 'The invoice status is required.',
        'status.in' => 'The invoice status must be DRAFT, SUBMITTED, or AUTHORISED.',
        'lineAmountTypes.required' => 'The line amount type is required.',
        'lineAmountTypes.in' => 'The line amount type must be Exclusive, Inclusive, or NoTax.',
        'contactId.required' => 'The contact is required.',
        'lineItems.required' => 'At least one line item is required.',
        'lineItems.min' => 'At least one line item is required.',
        'lineItems.*.description.required' => 'The line item description is required.',
        'lineItems.*.quantity.required' => 'The line item quantity is required.',
        'lineItems.*.quantity.numeric' => 'The line item quantity must be a number.',
        'lineItems.*.quantity.min' => 'The line item quantity must be at least 0.',
        'lineItems.*.unitAmount.required' => 'The line item unit amount is required.',
        'lineItems.*.unitAmount.numeric' => 'The line item unit amount must be a number.',
        'lineItems.*.unitAmount.min' => 'The line item unit amount must be at least 0.',
        'lineItems.*.accountCode.required' => 'The line item account code is required.',
    ];

    public function mount(string $invoiceId): void
    {
        $this->invoiceId = $invoiceId;

        try {
            $invoice = Xero::invoices()->find($invoiceId);

            // Set basic information
            $this->type = $invoice['Type'] ?? 'ACCREC';
            $this->invoiceNumber = $invoice['InvoiceNumber'] ?? '';
            $this->reference = $invoice['Reference'] ?? '';
            $this->date = isset($invoice['Date']) ? Xero::formatDate($invoice['Date'], 'd-m-Y') : '';
            $this->dueDate = isset($invoice['DueDate']) ? Xero::formatDate($invoice['DueDate'], 'd-m-Y') : '';
            $this->status = $invoice['Status'] ?? 'DRAFT';
            $this->lineAmountTypes = $invoice['LineAmountTypes'] ?? 'Exclusive';
            $this->currencyCode = $invoice['CurrencyCode'] ?? '';

            // Set contact information
            if (isset($invoice['Contact'])) {
                $this->contactId = $invoice['Contact']['ContactID'] ?? '';
                $this->contactName = $invoice['Contact']['Name'] ?? '';
            }

            // Set line items
            if (isset($invoice['LineItems']) && is_array($invoice['LineItems'])) {
                foreach ($invoice['LineItems'] as $item) {
                    $this->lineItems[] = [
                        'description' => $item['Description'] ?? '',
                        'quantity' => $item['Quantity'] ?? '1',
                        'unitAmount' => $item['UnitAmount'] ?? '0.00',
                        'accountCode' => $item['AccountCode'] ?? '',
                        'taxType' => $item['TaxType'] ?? '',
                    ];
                }
            }

            // If no line items were found, add an empty one
            if (empty($this->lineItems)) {
                $this->addLineItem();
            }
        } catch (Exception $exception) {
            session()->flash('error', 'Error loading invoice: '.$exception->getMessage());
            $this->redirect(route('xero.invoices.index'));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.xero.invoices.edit');
    }

    public function addLineItem(): void
    {
        $this->lineItems[] = [
            'description' => '',
            'quantity' => '1',
            'unitAmount' => '0.00',
            'accountCode' => '',
            'taxType' => '',
        ];
    }

    public function removeLineItem(int $index): void
    {
        if (isset($this->lineItems[$index])) {
            unset($this->lineItems[$index]);
            $this->lineItems = array_values($this->lineItems); // Re-index the array
        }
    }

    /**
     * Search for contacts in Xero
     *
     * @param  string  $search  The search term
     * @return array<int, array<string, mixed>> The list of contacts matching the search term
     */
    public function searchContacts(string $search = ''): array
    {
        if (empty($search)) {
            return [];
        }

        try {
            $query = Xero::contacts();
            $query->filter('searchTerm', $search);
            $query->filter('order', 'name');

            return $query->get();
        } catch (Exception $e) {
            return [];
        }
    }

    public function update(): void
    {
        $this->validate();

        // Format line items for the Xero API
        $formattedLineItems = [];
        foreach ($this->lineItems as $item) {
            $formattedLineItems[] = InvoiceDTO::createLineItem(
                $item['description'],
                $item['quantity'],
                $item['unitAmount'],
                (int) $item['accountCode'],
                null, // itemCode
                $item['taxType']
            );
        }

        $invoiceDTO = new InvoiceDTO(
            invoiceID: $this->invoiceId,
            type: $this->type,
            invoiceNumber: $this->invoiceNumber ?: null,
            reference: $this->reference ?: null,
            date: Xero::formatDate($this->date, 'Y-m-d'),
            dueDate: Xero::formatDate($this->dueDate, 'Y-m-d'),
            status: $this->status,
            lineAmountTypes: $this->lineAmountTypes,
            currencyCode: $this->currencyCode ?: null,
            contactID: $this->contactId,
            lineItems: $formattedLineItems
        );

        $response = Xero::invoices()->update($this->invoiceId, $invoiceDTO->toArray());

        if (isset($response['Id']) || isset($response['InvoiceID'])) {
            session()->flash('message', 'Invoice updated successfully!');
            $this->redirect(route('xero.invoices.show', $this->invoiceId));
        } else {
            session()->flash('error', 'Failed to update invoice. Please try again.');
        }

    }
}
