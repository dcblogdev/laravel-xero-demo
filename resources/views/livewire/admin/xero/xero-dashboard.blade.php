<div>
    <h1 class="text-2xl font-semibold mb-6">{{ __('Xero Dashboard') }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('Contacts') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('Manage your Xero contacts, including customers and suppliers.') }}</p>
            <div class="mt-4">
                <x-a href="{{ route('xero.contacts.index') }}" class="inline-flex items-center font-medium ease-in-out disabled:opacity-50 disabled:cursor-not-allowed rounded-md cursor-pointer bg-primary text-white hover:bg-primary/90 shadow-md dark:bg-primary-dark dark:text-gray-200 dark:hover:bg-primary-dark/80 px-4 py-2">
                    {{ __('View Contacts') }}
                </x-a>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('Invoices') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('Manage your Xero invoices, including sales invoices and bills.') }}</p>
            <div class="mt-4">
                <x-a href="{{ route('xero.invoices.index') }}" class="inline-flex items-center font-medium ease-in-out disabled:opacity-50 disabled:cursor-not-allowed rounded-md cursor-pointer bg-primary text-white hover:bg-primary/90 shadow-md dark:bg-primary-dark dark:text-gray-200 dark:hover:bg-primary-dark/80 px-4 py-2">
                    {{ __('View Invoices') }}
                </x-a>
            </div>
        </div>
    </div>
</div>
