<div>
    <h1>{{ __('Edit Invoice') }}</h1>

    <div class="card">
        <form wire:submit="update">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-2">{{ __('Basic Information') }}</h2>
                </div>

                <div>
                    <x-form.select
                        id="type"
                        name="type"
                        wire:model="type"
                        :label="__('Invoice Type')"
                        required
                    >
                        <option value="ACCREC">{{ __('Sales Invoice (ACCREC)') }}</option>
                        <option value="ACCPAY">{{ __('Bill (ACCPAY)') }}</option>
                    </x-form.select>
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="invoiceNumber"
                        name="invoiceNumber"
                        wire:model="invoiceNumber"
                        :label="__('Invoice Number')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="reference"
                        name="reference"
                        wire:model="reference"
                        :label="__('Reference')"
                    />
                </div>

                <div>
                    <x-form.date
                        id="date"
                        name="date"
                        wire:model="date"
                        :label="__('Date')"
                        required
                    />
                </div>

                <div>
                    <x-form.date
                        id="dueDate"
                        name="dueDate"
                        wire:model="dueDate"
                        :label="__('Due Date')"
                        required
                    />
                </div>

                <div>
                    <x-form.select
                        id="status"
                        name="status"
                        wire:model="status"
                        :label="__('Status')"
                        required
                    >
                        <option value="DRAFT">{{ __('Draft') }}</option>
                        <option value="SUBMITTED">{{ __('Submitted') }}</option>
                        <option value="AUTHORISED">{{ __('Authorised') }}</option>
                    </x-form.select>
                </div>

                <div>
                    <x-form.select
                        id="lineAmountTypes"
                        name="lineAmountTypes"
                        wire:model="lineAmountTypes"
                        :label="__('Line Amount Types')"
                        required
                    >
                        <option value="Exclusive">{{ __('Exclusive (Tax exclusive)') }}</option>
                        <option value="Inclusive">{{ __('Inclusive (Tax inclusive)') }}</option>
                        <option value="NoTax">{{ __('No Tax') }}</option>
                    </x-form.select>
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="currencyCode"
                        name="currencyCode"
                        wire:model="currencyCode"
                        :label="__('Currency Code')"
                        placeholder="USD"
                    />
                </div>

                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-2 mt-4">{{ __('Contact Information') }}</h2>
                </div>

                <div class="col-span-2" x-data="{
                    search: '',
                    contacts: [],
                    showResults: false,
                    selectedContact: '{{ $contactName }}',
                    searchContacts() {
                        if (this.search.length < 2) {
                            this.contacts = [];
                            this.showResults = false;
                            return;
                        }

                        $wire.searchContacts(this.search).then(results => {
                            this.contacts = results;
                            this.showResults = true;
                        });
                    },
                    selectContact(id, name) {
                        $wire.set('contactId', id);
                        $wire.set('contactName', name);
                        this.selectedContact = name;
                        this.showResults = false;
                        this.search = '';
                    }
                }">
                    <label for="contactSearch" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Contact') }} <span class="text-red-500">*</span>
                    </label>

                    <div class="relative">
                        <input
                            type="text"
                            id="contactSearch"
                            placeholder="Search for a contact..."
                            x-model="search"
                            @input.debounce.300ms="searchContacts()"
                            @focus="if(search.length >= 2) showResults = true"
                            @click.away="showResults = false"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                        >

                        <div x-show="selectedContact" class="mt-2 p-2 bg-gray-100 dark:bg-gray-700 rounded">
                            <p class="text-sm">
                                <span class="font-semibold">{{ __('Selected Contact') }}:</span>
                                <span x-text="selectedContact"></span>
                                <button type="button" @click="selectedContact = ''; $wire.set('contactId', ''); $wire.set('contactName', '');" class="ml-2 text-red-600 hover:text-red-800">
                                    <svg class="h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </p>
                        </div>

                        <div x-show="showResults" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 shadow-lg rounded-md overflow-hidden">
                            <div x-show="contacts.length === 0" class="p-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No contacts found. Try a different search term.') }}
                            </div>
                            <ul x-show="contacts.length > 0" class="max-h-60 overflow-y-auto">
                                <template x-for="contact in contacts" :key="contact.ContactID">
                                    <li @click="selectContact(contact.ContactID, contact.Name)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                        <p class="font-semibold" x-text="contact.Name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="contact.EmailAddress || 'No email'"></p>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <input type="hidden" id="contactId" name="contactId" wire:model="contactId">
                    @error('contactId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-2 mt-4">{{ __('Line Items') }}</h2>
                </div>

                <div class="col-span-2">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Description') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Quantity') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Unit Price') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Account Code') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Tax Type') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($lineItems as $index => $item)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <x-form.input
                                                type="text"
                                                id="lineItems.{{ $index }}.description"
                                                name="lineItems.{{ $index }}.description"
                                                wire:model="lineItems.{{ $index }}.description"
                                                label="none"
                                                placeholder="{{ __('Description') }}"
                                                required
                                            />
                                            @error("lineItems.{$index}.description") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-form.input
                                                type="number"
                                                id="lineItems.{{ $index }}.quantity"
                                                name="lineItems.{{ $index }}.quantity"
                                                wire:model="lineItems.{{ $index }}.quantity"
                                                label="none"
                                                placeholder="{{ __('Quantity') }}"
                                                step="0.01"
                                                min="0"
                                                required
                                            />
                                            @error("lineItems.{$index}.quantity") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-form.input
                                                type="number"
                                                id="lineItems.{{ $index }}.unitAmount"
                                                name="lineItems.{{ $index }}.unitAmount"
                                                wire:model="lineItems.{{ $index }}.unitAmount"
                                                label="none"
                                                placeholder="{{ __('Unit Price') }}"
                                                step="0.01"
                                                min="0"
                                                required
                                            />
                                            @error("lineItems.{$index}.unitAmount") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-form.input
                                                type="text"
                                                id="lineItems.{{ $index }}.accountCode"
                                                name="lineItems.{{ $index }}.accountCode"
                                                wire:model="lineItems.{{ $index }}.accountCode"
                                                label="none"
                                                placeholder="{{ __('Account Code') }}"
                                                required
                                            />
                                            @error("lineItems.{$index}.accountCode") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-form.input
                                                type="text"
                                                id="lineItems.{{ $index }}.taxType"
                                                name="lineItems.{{ $index }}.taxType"
                                                wire:model="lineItems.{{ $index }}.taxType"
                                                label="none"
                                                placeholder="{{ __('Tax Type') }}"
                                            />
                                            @error("lineItems.{$index}.taxType") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-4">
                                            <button type="button" wire:click="removeLineItem({{ $index }})" class="text-red-600 hover:text-red-900">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <x-button type="button" wire:click="addLineItem" class="bg-green-600 hover:bg-green-700 text-white">
                            <svg class="h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            {{ __('Add Line Item') }}
                        </x-button>
                    </div>
                </div>

                <div class="col-span-2 flex justify-between mt-6">
                    <p>
                        <x-a href="{{ route('xero.invoices.show', $invoiceId) }}">
                            {{ __('Cancel') }}
                        </x-a>
                    </p>
                    <x-button type="submit">
                        {{ __('Update Invoice') }}
                    </x-button>
                </div>
            </div>
        </form>
    </div>
</div>
