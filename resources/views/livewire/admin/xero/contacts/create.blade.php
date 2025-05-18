<div>
    <h1>{{ __('Create Contact') }}</h1>

    <div class="card">
        <form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-2">{{ __('Basic Information') }}</h2>
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="name"
                        name="name"
                        wire:model="name"
                        :label="__('Name')"
                        required
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="accountNumber"
                        name="accountNumber"
                        wire:model="accountNumber"
                        :label="__('Account Number')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="firstName"
                        name="firstName"
                        wire:model="firstName"
                        :label="__('First Name')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="lastName"
                        name="lastName"
                        wire:model="lastName"
                        :label="__('Last Name')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="email"
                        id="emailAddress"
                        name="emailAddress"
                        wire:model="emailAddress"
                        :label="__('Email Address')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="website"
                        name="website"
                        wire:model="website"
                        :label="__('Website')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="bankAccountDetails"
                        name="bankAccountDetails"
                        wire:model="bankAccountDetails"
                        :label="__('Bank Account Details')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="taxNumber"
                        name="taxNumber"
                        wire:model="taxNumber"
                        :label="__('Tax Number')"
                    />
                </div>

                <div>
                    <x-form.checkbox
                        id="isCustomer"
                        name="isCustomer"
                        wire:model="isCustomer"
                        :label="__('Is Customer')"
                    />
                </div>

                <div>
                    <x-form.checkbox
                        id="isSupplier"
                        name="isSupplier"
                        wire:model="isSupplier"
                        :label="__('Is Supplier')"
                    />
                </div>

                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-2 mt-4">{{ __('Address Information') }}</h2>
                </div>

                <div>
                    <x-form.select
                        id="addressType"
                        name="addressType"
                        wire:model="addressType"
                        :label="__('Address Type')"
                    >
                        <option value="POBOX">{{ __('PO Box') }}</option>
                        <option value="STREET">{{ __('Street') }}</option>
                        <option value="DELIVERY">{{ __('Delivery') }}</option>
                    </x-form.select>
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="addressLine1"
                        name="addressLine1"
                        wire:model="addressLine1"
                        :label="__('Address Line 1')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="city"
                        name="city"
                        wire:model="city"
                        :label="__('City')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="region"
                        name="region"
                        wire:model="region"
                        :label="__('Region')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="postalCode"
                        name="postalCode"
                        wire:model="postalCode"
                        :label="__('Postal Code')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="country"
                        name="country"
                        wire:model="country"
                        :label="__('Country')"
                    />
                </div>

                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-2 mt-4">{{ __('Phone Information') }}</h2>
                </div>

                <div>
                    <x-form.select
                        id="phoneType"
                        name="phoneType"
                        wire:model="phoneType"
                        :label="__('Phone Type')"
                    >
                        <option value="DEFAULT">{{ __('Default') }}</option>
                        <option value="DDI">{{ __('DDI') }}</option>
                        <option value="MOBILE">{{ __('Mobile') }}</option>
                        <option value="FAX">{{ __('Fax') }}</option>
                    </x-form.select>
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="phoneCountryCode"
                        name="phoneCountryCode"
                        wire:model="phoneCountryCode"
                        :label="__('Country Code')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="phoneAreaCode"
                        name="phoneAreaCode"
                        wire:model="phoneAreaCode"
                        :label="__('Area Code')"
                    />
                </div>

                <div>
                    <x-form.input
                        type="text"
                        id="phoneNumber"
                        name="phoneNumber"
                        wire:model="phoneNumber"
                        :label="__('Phone Number')"
                    />
                </div>

                <div class="col-span-2 flex justify-between mt-6">
                    <p>
                        <x-a href="{{ route('xero.contacts.index') }}">
                            {{ __('Cancel') }}
                        </x-a>
                    </p>
                    <x-button type="submit">
                        {{ __('Create Contact') }}
                    </x-button>
                </div>
            </div>
        </form>
    </div>
</div>
