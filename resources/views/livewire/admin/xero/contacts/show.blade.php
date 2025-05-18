<div>
    <div class="bg-white dark:bg-gray-800 shadow-md rounded p-6 max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-semibold">{{ $contact['Name'] ?? 'Unnamed Contact' }}</h2>
        <div class="space-x-4">
            <p>
                <x-a href="{{ route('xero.contacts.edit', $contact['ContactID']) }}">
                    {{ __('Edit Contact') }}
                </x-a>

                <x-a href="{{ route('xero.contacts.index') }}">
                    {{ __('Back to List') }}
                </x-a>
            </p>
        </div>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-200">Contact ID</dt>
            <dd class="text-gray-900 dark:text-gray-200">{{ $contact['ContactID'] }}</dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-200">Status</dt>
            <dd class="text-gray-900 dark:text-gray-200">{{ $contact['ContactStatus'] }}</dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-200">Email</dt>
            <dd class="text-gray-900 dark:text-gray-200">{{ $contact['EmailAddress'] ?? 'N/A' }}</dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-200">Bank Account</dt>
            <dd class="text-gray-900 dark:text-gray-200">{{ $contact['BankAccountDetails'] ?? 'N/A' }}</dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-200">Updated</dt>
            <dd class="text-gray-900 dark:text-gray-200">
                {{ \Carbon\Carbon::parse(preg_replace('/\/Date\((\d+)\+\d+\)\//', '@$1', $contact['UpdatedDateUTC']))->toDayDateTimeString() }}
            </dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-200">Is Supplier</dt>
            <dd class="text-gray-900 dark:text-gray-200">{{ $contact['IsSupplier'] ? 'Yes' : 'No' }}</dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-200">Is Customer</dt>
            <dd class="text-gray-900 dark:text-gray-200">{{ $contact['IsCustomer'] ? 'Yes' : 'No' }}</dd>
        </div>
    </dl>

    <hr class="my-6">

    <h3 class="text-lg font-semibold mb-2">Addresses</h3>
    <div class="space-y-2">
        @foreach ($contact['Addresses'] as $address)
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded border text-sm">
                <p><strong>Type:</strong> {{ $address['AddressType'] }}</p>
                <p><strong>City:</strong> {{ $address['City'] ?? 'N/A' }}</p>
                <p><strong>Region:</strong> {{ $address['Region'] ?? 'N/A' }}</p>
                <p><strong>Postal Code:</strong> {{ $address['PostalCode'] ?? 'N/A' }}</p>
                <p><strong>Country:</strong> {{ $address['Country'] ?? 'N/A' }}</p>
            </div>
        @endforeach
    </div>

    <hr class="my-6">

    <h3 class="text-lg font-semibold mb-2">Phone Numbers</h3>
    <div class="space-y-2">
        @foreach ($contact['Phones'] as $phone)
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded border text-sm">
                <p><strong>Type:</strong> {{ $phone['PhoneType'] }}</p>
                <p><strong>Number:</strong>
                    {{ trim(
                        ($phone['PhoneCountryCode'] ?? '') . ' ' .
                        ($phone['PhoneAreaCode'] ?? '') . ' ' .
                        ($phone['PhoneNumber'] ?? '')
                    ) ?: 'N/A' }}
                </p>
            </div>
        @endforeach
    </div>
</div>
</div>
