<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Invoices;

use DateTimeImmutable;
use DateTimeZone;
use Dcblogdev\Xero\Facades\Xero;
use Exception;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
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
     * @param  string  $xeroDate  The date string from Xero API
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

    /**
     * Parse a .NET-style JSON date string into a formatted date.
     *
     * @param  string  $netDate  e.g. "/Date(1747606289380+0000)/"
     * @param  string  $format  any DateTime format, default "Y-m-d H:i:s"
     *
     * @throws InvalidArgumentException if the input isn’t valid
     */
    public function formatNetJsonDate(string $netDate, string $format = 'Y-m-d H:i:s'): string
    {
        if (! preg_match('#/Date\((\d+)([+-]\d{4})\)/#', $netDate, $m)) {
            throw new InvalidArgumentException("Invalid .NET date string: $netDate");
        }

        // 1) milliseconds → seconds
        $timestamp = (int) ($m[1]) / 1000;

        // 2) create UTC DateTime from timestamp
        $dt = new DateTimeImmutable("@{$timestamp}");
        $dt->setTimezone(new DateTimeZone('UTC'));

        // 3) apply the parsed offset (e.g. "+0000" → "+00:00")
        $offset = $m[2];
        $tz = mb_substr($offset, 0, 3).':'.mb_substr($offset, 3);
        $dt->setTimezone(new DateTimeZone($tz));

        return $dt->format($format);
    }
}
