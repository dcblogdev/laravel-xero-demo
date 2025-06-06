<div>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">{{ __('Invoices') }}</h1>

        <div class="flex space-x-2" x-data="{
            get selectedCount() {
                return Object.values($wire.selectedInvoices).filter(value => value === true).length;
            }
        }">
            <x-button wire:click="exportToCsv">
                <span x-text="selectedCount > 0 ? `{{ __('Export') }} (${selectedCount}) {{ __('to CSV') }}` : `{{ __('Export to CSV') }}`"></span>
            </x-button>

            <x-a href="{{ route('xero.invoices.create') }}" class="inline-flex items-center font-medium ease-in-out disabled:opacity-50 disabled:cursor-not-allowed rounded-md cursor-pointer bg-primary text-white hover:bg-primary/90 shadow-md dark:bg-primary-dark dark:text-gray-200 dark:hover:bg-primary-dark/80 px-2 py-1 text-sm">
                {{ __('Create Invoice') }}
            </x-a>
        </div>
    </div>

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if (session('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="card">

        <div class="mt-5 grid sm:grid-cols-1 md:grid-cols-3 gap-4">
            <div class="col-span-2">
                <x-form.input type="search" id="searchTerm" name="searchTerm" wire:model.live="searchTerm" label="none" :placeholder="__('Search Invoices')" />
            </div>
        </div>

        <div class="mb-5" x-data="{ isOpen: @if($openFilter) true @else false @endif }">

            <button type="button" @click="isOpen = !isOpen" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs leading-4 font-medium rounded-t text-grey-700 bg-gray-200 hover:bg-grey-300 dark:bg-gray-700 dark:text-gray-200 transition ease-in-out duration-150">
                <svg class="h-5 w-5 text-gray-500 dark:text-gray-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                {{ __('Advanced Search') }}
            </button>

            <button type="button" wire:click="resetFilters" @click="isOpen = false" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs leading-4 font-medium rounded text-grey-700 bg-gray-200 hover:bg-grey-300 dark:bg-gray-700 dark:text-gray-200 transition ease-in-out duration-150">
                <svg class="h-5 w-5 text-gray-500 dark:text-gray-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ __('Reset form') }}
            </button>

            <div
                    x-show="isOpen"
                    x-transition:enter="transition ease-out duration-100 transform"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75 transform"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="bg-gray-200 dark:bg-gray-700 rounded-b-md p-5"
                    wire:ignore.self>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">

                    <x-form.input type="search" id="invoiceNumber" name="invoiceNumber" wire:model.live="invoiceNumber" :label="__('Invoice Number')" />
                    <x-form.input type="search" id="reference" name="reference" wire:model.live="reference" :label="__('Reference')" />
                    <x-form.input type="search" id="contactId" name="contactId" wire:model.live="contactId" :label="__('Contact ID')" />

                    <x-form.select id="status" name="status" :label="__('Status')" wire:model.live="status">
                        <option value="">{{ __('All') }}</option>
                        <option value="DRAFT">{{ __('Draft') }}</option>
                        <option value="SUBMITTED">{{ __('Submitted') }}</option>
                        <option value="AUTHORISED">{{ __('Authorised') }}</option>
                        <option value="PAID">{{ __('Paid') }}</option>
                        <option value="VOIDED">{{ __('Voided') }}</option>
                    </x-form.select>

                    <x-form.input type="date" id="fromDate" name="fromDate" wire:model.live="fromDate" :label="__('From Date')" />
                    <x-form.input type="date" id="toDate" name="toDate" wire:model.live="toDate" :label="__('To Date')" />

                </div>
            </div>

        </div>

        <div class="overflow-scroll">
            <table>
                <thead>
                <tr>
                    <th class="w-10">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                               wire:click="selectAllInvoices($event.target.checked)"
                               onclick="toggleAllCheckboxes(this.checked)">
                    </th>
                    <th>{{ __('Invoice Number') }}</th>
                    <th>{{ __('Reference') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Due Date') }}</th>
                    <th>{{ __('Contact') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Total') }}</th>
                    <th>{{ __('Action') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($this->invoices() as $row)
                    <tr wire:key="{{ $row['InvoiceID'] }}">
                        <td>
                            <input type="checkbox"
                                   wire:model.live="selectedInvoices.{{ $row['InvoiceID'] }}"
                                   id="invoice-{{ $row['InvoiceID'] }}"
                                   class="invoice-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </td>
                        <td>{{ $row['InvoiceNumber'] ?? '' }}</td>
                        <td>{{ $row['Reference'] ?? '' }}</td>
                        <td>{{ isset($row['Date']) ? Xero::formatDate($row['Date'], 'jS M Y') : '' }}</td>
                        <td>{{ isset($row['DueDate']) ? Xero::formatDate($row['DueDate'], 'jS M Y') : '' }}</td>
                        <td>{{ $row['Contact']['Name'] ?? '' }}</td>
                        <td>{{ $row['Status'] }}</td>
                        <td>{{ $row['Total'] }} {{ $row['CurrencyCode'] ?? '' }}</td>
                        <td>
                            <a href="{{ route('xero.invoices.show', $row['InvoiceID']) }}" class="text-blue-600 hover:text-blue-900 mr-2">View</a>
                            <a href="{{ route('xero.invoices.edit', $row['InvoiceID']) }}" class="text-green-600 hover:text-green-900 mr-2">Edit</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to all invoice checkboxes
            const checkboxes = document.querySelectorAll('.invoice-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectAllCheckbox);
            });

            // Initial update of select-all checkbox
            updateSelectAllCheckbox();
        });

        function toggleAllCheckboxes(checked) {
            // Update UI and trigger Livewire updates for each checkbox
            const checkboxes = document.querySelectorAll('.invoice-checkbox');
            checkboxes.forEach(checkbox => {
                if (checkbox.checked !== checked) {
                    checkbox.checked = checked;
                    // Trigger Livewire update for this checkbox
                    checkbox.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }

        function updateSelectAllCheckbox() {
            const checkboxes = document.querySelectorAll('.invoice-checkbox');
            const selectAllCheckbox = document.getElementById('select-all');

            // If no checkboxes, do nothing
            if (checkboxes.length === 0) return;

            // Check if all checkboxes are checked
            const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
            const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

            // Update select-all checkbox without triggering its change event
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = anyChecked && !allChecked;
        }
    </script>
</div>
