<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Invoices;

use Dcblogdev\Xero\Facades\Xero;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Invoice')]
class ShowInvoice extends Component
{
    /** @var array<string, mixed> */
    public array $invoice = [];

    public string $onlineInvoiceUrl = '';

    /** @var array<int, array<string, mixed>> */
    public array $attachments = [];

    public function mount(string $invoiceId): void
    {
        try {
            $this->invoice = Xero::invoices()->find($invoiceId);

            // Get the online invoice URL
            try {
                $this->onlineInvoiceUrl = Xero::invoices()->onlineUrl($invoiceId);
            } catch (Exception $e) {
                // If there's an error getting the online URL, just ignore it
                $this->onlineInvoiceUrl = '';
            }

            // Get the attachments
            try {
                $this->attachments = Xero::invoices()->attachments($invoiceId);
            } catch (Exception $e) {
                // If there's an error getting the attachments, just ignore it
                $this->attachments = [];
            }
        } catch (Exception $exception) {
            abort(404);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.xero.invoices.show');
    }

    /**
     * Format date string to a readable date
     *
     * @param string $xeroDate The date string from Xero API
     * @return string Formatted date string
     */
    public function formatXeroDate(string $xeroDate): string
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

        return date('jS M Y H:i:s', $timestamp);
    }
}
