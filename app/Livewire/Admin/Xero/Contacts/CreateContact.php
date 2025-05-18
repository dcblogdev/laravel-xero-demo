<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Contacts;

use App\DTOs\Xero\ContactDTO;
use Dcblogdev\Xero\Facades\Xero;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create Contact')]
class CreateContact extends Component
{
    public string $name = '';
    public string $firstName = '';
    public string $lastName = '';
    public string $emailAddress = '';
    public string $accountNumber = '';
    public string $bankAccountDetails = '';
    public string $taxNumber = '';
    public string $website = '';
    public bool $isSupplier = false;
    public bool $isCustomer = true;

    // Address fields
    public string $addressType = 'POBOX';
    public string $addressLine1 = '';
    public string $city = '';
    public string $region = '';
    public string $postalCode = '';
    public string $country = '';

    // Phone fields
    public string $phoneType = 'DEFAULT';
    public string $phoneNumber = '';
    public string $phoneAreaCode = '';
    public string $phoneCountryCode = '';

    protected array $rules = [
        'name' => 'required|string|max:255',
        'firstName' => 'nullable|string|max:255',
        'lastName' => 'nullable|string|max:255',
        'emailAddress' => 'nullable|email|max:255',
        'accountNumber' => 'nullable|string|max:50',
        'bankAccountDetails' => 'nullable|string|max:255',
        'taxNumber' => 'nullable|string|max:50',
        'website' => 'nullable|url|max:255',
        'isSupplier' => 'boolean',
        'isCustomer' => 'boolean',
        'addressLine1' => 'nullable|string|max:500',
        'city' => 'nullable|string|max:255',
        'region' => 'nullable|string|max:255',
        'postalCode' => 'nullable|string|max:50',
        'country' => 'nullable|string|max:50',
        'phoneNumber' => 'nullable|string|max:50',
        'phoneAreaCode' => 'nullable|string|max:10',
        'phoneCountryCode' => 'nullable|string|max:10',
    ];

    protected array $messages = [
        'name.required' => 'The contact name is required.',
        'emailAddress.email' => 'Please enter a valid email address.',
        'website.url' => 'Please enter a valid website URL.',
    ];

    public function render(): View
    {
        return view('livewire.admin.xero.contacts.create');
    }

    public function save(): void
    {
        $this->validate();

        $addresses = [];
        if ($this->addressLine1 || $this->city || $this->region || $this->postalCode || $this->country) {
            $addresses[] = ContactDTO::createAddress(
                $this->addressType,
                $this->addressLine1,
                null,
                null,
                null,
                $this->city,
                $this->region,
                $this->postalCode,
                $this->country
            );
        }

        $phones = [];
        if ($this->phoneNumber || $this->phoneAreaCode || $this->phoneCountryCode) {
            $phones[] = ContactDTO::createPhone(
                $this->phoneType,
                $this->phoneNumber,
                $this->phoneAreaCode,
                $this->phoneCountryCode
            );
        }

        $contactDTO = new ContactDTO(
            name: $this->name,
            firstName: $this->firstName ?: null,
            lastName: $this->lastName ?: null,
            emailAddress: $this->emailAddress ?: null,
            accountNumber: $this->accountNumber ?: null,
            bankAccountDetails: $this->bankAccountDetails ?: null,
            taxNumber: $this->taxNumber ?: null,
            isSupplier: $this->isSupplier,
            isCustomer: $this->isCustomer,
            website: $this->website ?: null,
            addresses: $addresses,
            phones: $phones
        );

        try {
            $response = Xero::contacts()->store($contactDTO->toArray());

            if (isset($response['Id']) || isset($response['ContactID'])) {
                session()->flash('message', 'Contact created successfully!');
                $this->redirect(route('xero.contacts.index'));
            } else {
                session()->flash('error', 'Failed to create contact. Please try again.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }
}
