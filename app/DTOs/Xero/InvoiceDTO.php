<?php

declare(strict_types=1);

namespace App\DTOs\Xero;

class InvoiceDTO
{
    public function __construct(
        public ?string $invoiceID = null,
        public ?string $type = 'ACCREC', // ACCREC for sales invoices, ACCPAY for bills
        public ?string $invoiceNumber = null,
        public ?string $reference = null,
        public ?string $date = null,
        public ?string $dueDate = null,
        public ?string $status = 'DRAFT', // DRAFT, SUBMITTED, AUTHORISED, PAID, etc.
        public ?string $lineAmountTypes = 'Exclusive', // Exclusive, Inclusive, NoTax
        public ?string $currencyCode = null,
        public ?string $currencyRate = null,
        public ?string $subTotal = null,
        public ?string $totalTax = null,
        public ?string $total = null,
        public ?string $contactID = null,
        /** @var array<int, array<string, mixed>> */
        public ?array $contact = null,
        /** @var array<int, array<string, mixed>> */
        public ?array $lineItems = [],
        /** @var array<int, array<string, mixed>> */
        public ?array $payments = [],
        /** @var array<int, array<string, mixed>> */
        public ?array $creditNotes = [],
        /** @var array<int, array<string, mixed>> */
        public ?array $prepayments = [],
        /** @var array<int, array<string, mixed>> */
        public ?array $overpayments = [],
        public ?bool $hasAttachments = false,
        public ?bool $isDiscounted = false,
        public ?bool $hasErrors = false,
    ) {}

    /**
     * Create a line item array for the invoice
     *
     * @param  array<int, array<string, mixed>>|null  $tracking
     * @return array<string, mixed>
     */
    public static function createLineItem(
        ?string $description = null,
        string|int|null $quantity = null,
        string|float|null $unitAmount = null,
        ?int $accountCode = null,
        ?string $itemCode = null,
        ?string $taxType = null,
        ?string $taxAmount = null,
        ?string $lineAmount = null,
        ?string $discountRate = null,
        ?array $tracking = null,
    ): array {
        return array_filter([
            'Description' => $description,
            'Quantity' => $quantity,
            'UnitAmount' => $unitAmount,
            'AccountCode' => $accountCode,
            'ItemCode' => $itemCode,
            'TaxType' => $taxType,
            'TaxAmount' => $taxAmount,
            'LineAmount' => $lineAmount,
            'DiscountRate' => $discountRate,
            'Tracking' => $tracking,
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * Convert the DTO to an array for the Xero API
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'InvoiceID' => $this->invoiceID,
            'Type' => $this->type,
            'InvoiceNumber' => $this->invoiceNumber,
            'Reference' => $this->reference,
            'Date' => $this->date,
            'DueDate' => $this->dueDate,
            'Status' => $this->status,
            'LineAmountTypes' => $this->lineAmountTypes,
            'CurrencyCode' => $this->currencyCode,
            'CurrencyRate' => $this->currencyRate,
            'SubTotal' => $this->subTotal,
            'TotalTax' => $this->totalTax,
            'Total' => $this->total,
            'Contact' => $this->contactID
                ? ['ContactID' => $this->contactID]
                : $this->contact, // fallback if full contact object is passed
            'LineItems' => $this->lineItems,
            'HasAttachments' => $this->hasAttachments,
            'IsDiscounted' => $this->isDiscounted,
        ], fn ($value) => $value !== null && $value !== []);
    }
}
