<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">{{ __('Import Contacts') }}</h1>
        <div>
            <x-a href="{{ route('xero.contacts.index') }}" class="inline-flex items-center font-medium ease-in-out disabled:opacity-50 disabled:cursor-not-allowed rounded-md cursor-pointer bg-gray-200 text-gray-700 hover:bg-gray-300 shadow-md dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 px-2 py-1 text-sm">
                {{ __('Back to Contacts') }}
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
        @if (!$fileUploaded)
            <form wire:submit="uploadCSV" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="csvFile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('CSV File') }}
                    </label>
                    <input type="file" id="csvFile" wire:model="csvFile" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                    @error('csvFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Upload a CSV file containing contact information.') }}</p>
                </div>
                <div class="flex justify-end">
                    <x-button type="submit">
                        {{ __('Upload CSV') }}
                    </x-button>
                </div>
            </form>
        @else
            <div class="mb-4">
                <h2 class="text-lg font-semibold mb-2">{{ __('Column Mapping and Preview') }}</h2>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Please review and adjust the column mapping if needed before importing.') }}
                    <span class="text-red-600 font-semibold">{{ __('The Name field is required.') }}</span>
                    {{ __('Email and Account Number are recommended for identifying existing contacts.') }}
                </p>

                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded text-sm text-blue-700">
                    <p class="font-semibold">{{ __('Note:') }}</p>
                    <p>{{ __('If a contact with the same Name, Email, or Account Number already exists in Xero, it will be updated instead of creating a duplicate.') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Column Mapping -->
                    <div>
                        <h3 class="text-md font-semibold mb-2">{{ __('Column Mapping') }}</h3>
                        <div class="overflow-y-auto max-h-96 border rounded">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('CSV Column') }}
                                        </th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ __('Maps to Field') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                                    @foreach ($headers as $header)
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $header }}
                                                @if (isset($columnMapping[$header]))
                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $availableFields[$columnMapping[$header]] ?? $columnMapping[$header] }}
                                                        @if ($columnMapping[$header] === 'name')
                                                            <span class="ml-1 text-red-500">*</span>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        {{ __('Not Mapped') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <select
                                                    wire:model="columnMapping.{{ $header }}"
                                                    wire:change="updateMapping('{{ $header }}', $event.target.value)"
                                                    class="block w-full pl-3 pr-10 py-1 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                                >
                                                    <option value="">{{ __('-- Not Mapped --') }}</option>
                                                    @foreach ($availableFields as $field => $label)
                                                        @if ($field === 'name')
                                                            <option value="{{ $field }}" class="font-semibold">{{ $label }} ({{ __('Required') }})</option>
                                                        @elseif (in_array($field, ['emailAddress', 'accountNumber']))
                                                            <option value="{{ $field }}">{{ $label }} ({{ __('Recommended') }})</option>
                                                        @else
                                                            <option value="{{ $field }}">{{ $label }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Data Preview -->
                    <div>
                        <h3 class="text-md font-semibold mb-2">{{ __('Data Preview') }} ({{ __('First 5 rows') }})</h3>
                        <div class="overflow-x-auto max-h-96 border rounded">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                                    <tr>
                                        @foreach ($headers as $header)
                                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ $header }}
                                                @if (isset($columnMapping[$header]))
                                                    <span class="block text-xs font-normal normal-case text-gray-400 dark:text-gray-500">
                                                        {{ $availableFields[$columnMapping[$header]] ?? '' }}
                                                    </span>
                                                @endif
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                                    @foreach (array_slice($csvData, 0, 5) as $row)
                                        <tr>
                                            @foreach ($headers as $header)
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $row[$header] ?? '' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                            <p>{{ __('Showing 5 of') }} {{ count($csvData) }} {{ __('rows') }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <x-button wire:click="cancel" class="bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        {{ __('Cancel') }}
                    </x-button>
                    <x-button
                        wire:click="importContacts"
                        class="bg-primary text-white hover:bg-primary/90 dark:bg-primary-dark dark:text-gray-200 dark:hover:bg-primary-dark/80"
                        :disabled="!$this->isHeaderMapped('name')"
                    >
                        {{ __('Import Contacts') }}
                        @if(!$this->isHeaderMapped('name'))
                            <span class="ml-1 text-xs">({{ __('Name field required') }})</span>
                        @endif
                    </x-button>
                </div>
            </div>
        @endif
    </div>
</div>
