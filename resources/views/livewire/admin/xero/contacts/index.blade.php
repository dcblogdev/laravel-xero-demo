<div>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">{{ __('Contacts') }}</h1>

        <div class="flex space-x-2" x-data="{
            get selectedCount() {
                return Object.values($wire.selectedContacts).filter(value => value === true).length;
            }
        }">
            <x-button
                x-show="selectedCount > 0"
                x-cloak
                wire:click="massArchiveContacts"
                wire:confirm="{{ __('Are you sure you want to delete the selected contacts? This action cannot be undone.') }}"
                class="bg-red-600 hover:bg-red-700 text-white"
            >
                <span x-text="`{{ __('Archive') }} (${selectedCount})`"></span>
            </x-button>

            <x-button wire:click="exportToCsv">
                <span x-text="selectedCount > 0 ? `{{ __('Export') }} (${selectedCount}) {{ __('to CSV') }}` : `{{ __('Export to CSV') }}`"></span>
            </x-button>

            <x-a href="{{ route('xero.contacts.import') }}" class="inline-flex items-center font-medium ease-in-out disabled:opacity-50 disabled:cursor-not-allowed rounded-md cursor-pointer bg-primary text-white hover:bg-primary/90 shadow-md dark:bg-primary-dark dark:text-gray-200 dark:hover:bg-primary-dark/80 px-2 py-1 text-sm">
                {{ __('Import Contacts') }}
            </x-a>

            <x-a href="{{ route('xero.contacts.create') }}" class="inline-flex items-center font-medium ease-in-out disabled:opacity-50 disabled:cursor-not-allowed rounded-md cursor-pointer bg-primary text-white hover:bg-primary/90 shadow-md dark:bg-primary-dark dark:text-gray-200 dark:hover:bg-primary-dark/80 px-2 py-1 text-sm">
                {{ __('Create Contact') }}
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

    @if ($importStatus)
        <div class="mb-4" x-data="{ open: true }" x-show="open"
            @if ($importStatus['status'] === 'processing')
            wire:poll.5s="checkImportStatus"
            @endif
        >
            <div class="border rounded-md overflow-hidden">
                <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 flex justify-between items-center">
                    <h3 class="text-md font-semibold">
                        @if ($importStatus['status'] === 'processing')
                            <span class="text-blue-600">{{ __('Import in Progress') }}</span>
                        @elseif ($importStatus['status'] === 'completed')
                            <span class="text-green-600">{{ __('Import Completed') }}</span>
                        @elseif ($importStatus['status'] === 'completed_with_errors')
                            <span class="text-yellow-600">{{ __('Import Completed with Errors') }}</span>
                        @elseif ($importStatus['status'] === 'failed')
                            <span class="text-red-600">{{ __('Import Failed') }}</span>
                        @endif
                    </h3>
                    <button @click="open = false" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800">
                    <p class="mb-2">{{ $importStatus['message'] }}</p>

                    @if ($importStatus['status'] === 'processing')
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4 dark:bg-gray-700">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $importStatus['progress'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Processing') }} {{ $importStatus['processed_rows'] }} {{ __('of') }} {{ $importStatus['total_rows'] }} {{ __('contacts') }} ({{ $importStatus['progress'] }}%)
                        </p>
                        <div class="mt-2 flex items-center">
                            <span class="text-xs text-gray-500 mr-2">{{ __('Auto-refreshing every 5 seconds') }}</span>
                            <button wire:click="checkImportStatus" class="text-sm text-blue-600 hover:text-blue-800">
                                {{ __('Refresh Now') }}
                            </button>
                        </div>
                    @else
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="border rounded p-3 bg-green-50 dark:bg-green-900/20">
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Created') }}</p>
                                <p class="text-xl font-semibold text-green-600">{{ $importStatus['success_count'] }}</p>
                            </div>
                            <div class="border rounded p-3 bg-blue-50 dark:bg-blue-900/20">
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Updated') }}</p>
                                <p class="text-xl font-semibold text-blue-600">{{ $importStatus['update_count'] }}</p>
                            </div>
                            <div class="border rounded p-3 bg-red-50 dark:bg-red-900/20">
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Errors') }}</p>
                                <p class="text-xl font-semibold text-red-600">{{ $importStatus['error_count'] }}</p>
                            </div>
                        </div>

                        @if (!empty($importStatus['errors']))
                            <div x-data="{ showErrors: false }">
                                <button @click="showErrors = !showErrors" class="text-sm text-red-600 hover:text-red-800 flex items-center">
                                    <span>{{ __('Show Error Details') }}</span>
                                    <svg x-show="!showErrors" class="h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <svg x-show="showErrors" class="h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                                <div x-show="showErrors" class="mt-2 p-3 bg-red-50 dark:bg-red-900/20 rounded text-sm text-red-800 dark:text-red-200">
                                    <ul class="list-disc pl-5">
                                        @foreach ($importStatus['errors'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="card">

        <div class="mt-5 grid sm:grid-cols-1 md:grid-cols-3 gap-4">

            <div class="col-span-2">
                <x-form.input type="search" id="searchTerm" name="searchTerm" wire:model.live="searchTerm" label="none" :placeholder="__('Search Contacts')" />
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

                    <x-form.input type="search" id="accountNumber" name="accountNumber" wire:model.live="accountNumber" :label="__('Account Number')" />
                    <x-form.input type="search" id="email" name="email" wire:model.live="email" :label="__('Email')" />
                    <x-form.input type="search" id="contactId" name="contactId" wire:model.live="contactId" :label="__('ContactId')" />

                    <x-form.select id="includeArchived" name="includeArchived" :label="__('Include Archived')" wire:model.live="includeArchived">
                        <option value="true">Yes</option>
                        <option value="false">No</option>
                    </x-form.select>

                </div>
            </div>

        </div>

        <div class="overflow-scroll">
            <table>
                <thead>
                <tr>
                    <th class="w-10">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                               wire:click="selectAllContacts($event.target.checked)"
                               onclick="toggleAllCheckboxes(this.checked)">
                    </th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('First Name') }}</th>
                    <th>{{ __('Last Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Action') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($this->contacts() as $row)
                    <tr wire:key="{{ $row['ContactID'] }}">
                        <td>
                            <input type="checkbox"
                                   wire:model.live="selectedContacts.{{ $row['ContactID'] }}"
                                   id="contact-{{ $row['ContactID'] }}"
                                   class="contact-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </td>
                        <td>{{ $row['Name'] }}</td>
                        <td>{{ $row['FirstName'] ?? '' }}</td>
                        <td>{{ $row['LastName'] ?? '' }}</td>
                        <td>{{ $row['EmailAddress'] ?? '' }}</td>
                        <td>{{ $row['ContactStatus'] }}</td>
                        <td>
                            <a href="{{ route('xero.contacts.show', $row['ContactID']) }}" class="text-blue-600 hover:text-blue-900 mr-2">View</a>
                            <a href="{{ route('xero.contacts.edit', $row['ContactID']) }}" class="text-green-600 hover:text-green-900 mr-2">Edit</a>
                            @if($row['ContactStatus'] !== 'ARCHIVED')
                                <button
                                    wire:click="archiveContact('{{ $row['ContactID'] }}')"
                                    wire:confirm="{{ __('Are you sure you want to archive this contact?') }}"
                                    class="text-red-600 hover:text-red-900 bg-transparent border-none p-0 cursor-pointer"
                                >
                                    Archive
                                </button>
                            @else
                                <span class="text-gray-400">(Archived)</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to all contact checkboxes
            const checkboxes = document.querySelectorAll('.contact-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectAllCheckbox);
            });

            // Initial update of select-all checkbox
            updateSelectAllCheckbox();
        });

        function toggleAllCheckboxes(checked) {
            // Update UI and trigger Livewire updates for each checkbox
            const checkboxes = document.querySelectorAll('.contact-checkbox');
            checkboxes.forEach(checkbox => {
                if (checkbox.checked !== checked) {
                    checkbox.checked = checked;
                    // Trigger Livewire update for this checkbox
                    checkbox.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }

        function updateSelectAllCheckbox() {
            const checkboxes = document.querySelectorAll('.contact-checkbox');
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
