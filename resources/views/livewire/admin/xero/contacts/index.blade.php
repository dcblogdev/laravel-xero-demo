<div>
    <h1>{{ __('Contacts') }}</h1>

    <div class="card">

        <div class="mt-5 grid sm:grid-cols-1 md:grid-cols-3 gap-4">

            <div class="col-span-2">
                <x-form.input type="search" id="searchTerm" name="searchTerm" wire:model.live="searchTerm" label="none" :placeholder="__('Search Actions')" />
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

{{--                    <x-form.select id="user_id" name="user_id" :label="__('User')" wire:model.live="user_id">--}}
{{--                        <option value="">Select</option>--}}
{{--                        @foreach($users as $user)--}}
{{--                            <option value="{{ $user->id }}">{{ $user->name }}</option>--}}
{{--                        @endforeach--}}
{{--                    </x-form.select>--}}

{{--                    <x-form.select id="section" name="section" :label="__('Section')" wire:model.live="section">--}}
{{--                        <option value="">Select</option>--}}
{{--                        @foreach($sections as $section)--}}
{{--                            <option value="{{ $section }}">{{ $section }}</option>--}}
{{--                        @endforeach--}}
{{--                    </x-form.select>--}}

{{--                    <x-form.select id="type" name="type" :label="__('Type')" wire:model.live="type">--}}
{{--                        <option value="">Select</option>--}}
{{--                        @foreach($types as $type)--}}
{{--                            <option value="{{ $type }}">{{ $type }}</option>--}}
{{--                        @endforeach--}}
{{--                    </x-form.select>--}}

                    <x-form.daterange id="created_at" name="created_at" :label="__('Created Date Range')" wire:model.blur="created_at" />
                </div>
            </div>

        </div>

        <div class="overflow-scroll">
            <table>
                <thead>
                <tr>
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
                        <td>{{ $row['Name'] }}</td>
                        <td>{{ $row['FirstName'] ?? '' }}</td>
                        <td>{{ $row['LastName'] ?? '' }}</td>
                        <td>{{ $row['EmailAddress'] ?? '' }}</td>
                        <td>{{ $row['ContactStatus'] }}</td>
                        <td><a href="{{ route('xero.contacts.show', $row['ContactID']) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
