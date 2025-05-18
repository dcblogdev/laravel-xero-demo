<div>
    <div class="bg-white dark:bg-gray-800 shadow-md rounded p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-semibold">
                {{ $invoice['InvoiceNumber'] ? 'Invoice #' . $invoice['InvoiceNumber'] : 'Invoice' }}
                @if($invoice['Reference'])
                    <span class="text-gray-500 text-lg">({{ $invoice['Reference'] }})</span>
                @endif
            </h2>
            <div class="space-x-4">
                <p>
                    <x-a href="{{ route('xero.invoices.edit', $invoice['InvoiceID']) }}">
                        {{ __('Edit Invoice') }}
                    </x-a>

                    |

                    <x-a href="{{ route('xero.invoices.index') }}">
                        {{ __('Back to List') }}
                    </x-a>
                </p>
            </div>
        </div>

        @if($onlineInvoiceUrl)
            <div class="mb-4">
                <a href="{{ $onlineInvoiceUrl }}" target="_blank" class="inline-flex items-center font-medium ease-in-out disabled:opacity-50 disabled:cursor-not-allowed rounded-md cursor-pointer bg-primary text-white hover:bg-primary/90 shadow-md dark:bg-primary-dark dark:text-gray-200 dark:hover:bg-primary-dark/80 px-2 py-1 text-sm">
                    {{ __('View Online Invoice') }}
                </a>
            </div>
        @endif

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-200">Invoice ID</dt>
                <dd class="text-gray-900 dark:text-gray-200">{{ $invoice['InvoiceID'] }}</dd>
            </div>

            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-200">Status</dt>
                <dd class="text-gray-900 dark:text-gray-200">{{ $invoice['Status'] }}</dd>
            </div>

            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-200">Type</dt>
                <dd class="text-gray-900 dark:text-gray-200">
                    {{ $invoice['Type'] === 'ACCREC' ? 'Sales Invoice' : 'Bill' }}
                </dd>
            </div>

            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-200">Date</dt>
                <dd class="text-gray-900 dark:text-gray-200">
                    {{ isset($invoice['Date']) ? $this->formatXeroDate($invoice['Date']) : 'N/A' }}
                </dd>
            </div>

            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-200">Due Date</dt>
                <dd class="text-gray-900 dark:text-gray-200">
                    {{ isset($invoice['DueDate']) ? $this->formatXeroDate($invoice['DueDate']) : 'N/A' }}
                </dd>
            </div>

            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-200">Updated</dt>
                <dd class="text-gray-900 dark:text-gray-200">
                    {{ isset($invoice['UpdatedDateUTC']) ? $this->formatXeroDate($invoice['UpdatedDateUTC']) : 'N/A' }}
                </dd>
            </div>

            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-200">Currency</dt>
                <dd class="text-gray-900 dark:text-gray-200">{{ $invoice['CurrencyCode'] ?? 'N/A' }}</dd>
            </div>

            <div>
                <dt class="font-medium text-gray-500 dark:text-gray-200">Line Amount Types</dt>
                <dd class="text-gray-900 dark:text-gray-200">{{ $invoice['LineAmountTypes'] ?? 'N/A' }}</dd>
            </div>
        </dl>

        <hr class="my-6">

        <h3 class="text-lg font-semibold mb-2">Contact</h3>
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded border text-sm mb-6">
            @if(isset($invoice['Contact']))
                <p><strong>Name:</strong> {{ $invoice['Contact']['Name'] ?? 'N/A' }}</p>
                <p><strong>ID:</strong> {{ $invoice['Contact']['ContactID'] ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $invoice['Contact']['EmailAddress'] ?? 'N/A' }}</p>
            @else
                <p>No contact information available.</p>
            @endif
        </div>

        <h3 class="text-lg font-semibold mb-2">Summary</h3>
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded border text-sm mb-6">
            <div class="grid grid-cols-2 gap-4">
                <p><strong>Sub Total:</strong> {{ $invoice['SubTotal'] ?? '0.00' }} {{ $invoice['CurrencyCode'] ?? '' }}</p>
                <p><strong>Total Tax:</strong> {{ $invoice['TotalTax'] ?? '0.00' }} {{ $invoice['CurrencyCode'] ?? '' }}</p>
                <p><strong>Total:</strong> {{ $invoice['Total'] ?? '0.00' }} {{ $invoice['CurrencyCode'] ?? '' }}</p>
                <p><strong>Amount Due:</strong> {{ $invoice['AmountDue'] ?? '0.00' }} {{ $invoice['CurrencyCode'] ?? '' }}</p>
                <p><strong>Amount Paid:</strong> {{ $invoice['AmountPaid'] ?? '0.00' }} {{ $invoice['CurrencyCode'] ?? '' }}</p>
            </div>
        </div>

        <h3 class="text-lg font-semibold mb-2">Line Items</h3>
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Quantity</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Unit Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Account</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Tax Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @if(isset($invoice['LineItems']) && is_array($invoice['LineItems']))
                        @foreach($invoice['LineItems'] as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-normal text-sm text-gray-900">{{ $item['Description'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['Quantity'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['UnitAmount'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['AccountCode'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['TaxType'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['LineAmount'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">No line items found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if(count($attachments) > 0)
            <hr class="my-6">
            <h3 class="text-lg font-semibold mb-2">Attachments</h3>
            <div class="space-y-2">
                @foreach($attachments as $attachment)
                    <div class="bg-gray-50 p-4 rounded border text-sm">
                        <p><strong>Name:</strong> {{ $attachment['FileName'] ?? 'N/A' }}</p>
                        <p><strong>Type:</strong> {{ $attachment['MimeType'] ?? 'N/A' }}</p>
                        <p><strong>Size:</strong> {{ isset($attachment['ContentLength']) ? round($attachment['ContentLength'] / 1024, 2) . ' KB' : 'N/A' }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
