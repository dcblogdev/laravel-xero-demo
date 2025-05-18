<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Contacts;

use App\DTOs\Xero\ContactDTO;
use Dcblogdev\Xero\Facades\Xero;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit Contact')]
class EditContact extends Component
{
    public string $contactId = '';
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

    public function mount(string $contactId): void
    {
        $this->contactId = $contactId;

        try {
            $contact = Xero::contacts()->find($contactId);

            // Set basic information
            $this->name = $contact['Name'] ?? '';
            $this->firstName = $contact['FirstName'] ?? '';
            $this->lastName = $contact['LastName'] ?? '';
            $this->emailAddress = $contact['EmailAddress'] ?? '';
            $this->accountNumber = $contact['AccountNumber'] ?? '';
            $this->bankAccountDetails = $contact['BankAccountDetails'] ?? '';
            $this->taxNumber = $contact['TaxNumber'] ?? '';
            $this->website = $contact['Website'] ?? '';
            $this->isSupplier = $contact['IsSupplier'] ?? false;
            $this->isCustomer = $contact['IsCustomer'] ?? true;

            // Set address information if available
            if (!empty($contact['Addresses'])) {
                foreach ($contact['Addresses'] as $address) {
                    // Use the first address of each type, prioritizing POBOX, then STREET, then DELIVERY
                    if ($address['AddressType'] === 'POBOX' || empty($this->addressLine1)) {
                        $this->addressType = $address['AddressType'];
                        $this->addressLine1 = $address['AddressLine1'] ?? '';
                        $this->city = $address['City'] ?? '';
                        $this->region = $address['Region'] ?? '';
                        $this->postalCode = $address['PostalCode'] ?? '';
                        $this->country = $address['Country'] ?? '';

                        if ($address['AddressType'] === 'POBOX') {
                            break;
                        }
                    }
                }
            }

            // Set phone information if available
            if (!empty($contact['Phones'])) {
                foreach ($contact['Phones'] as $phone) {
                    // Use the first phone of each type, prioritizing DEFAULT, then MOBILE, then others
                    if ($phone['PhoneType'] === 'DEFAULT' || empty($this->phoneNumber)) {
                        $this->phoneType = $phone['PhoneType'];
                        $this->phoneNumber = $phone['PhoneNumber'] ?? '';
                        $this->phoneAreaCode = $phone['PhoneAreaCode'] ?? '';
                        $this->phoneCountryCode = $phone['PhoneCountryCode'] ?? '';

                        if ($phone['PhoneType'] === 'DEFAULT') {
                            break;
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            session()->flash('error', 'Error loading contact: ' . $exception->getMessage());
            $this->redirect(route('xero.contacts.index'));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.xero.contacts.edit');
    }

    public function update(): void
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
            $response = Xero::contacts()->update($this->contactId, $contactDTO->toArray());

            if (isset($response['Id']) || isset($response['ContactID'])) {
                session()->flash('message', 'Contact updated successfully!');
                $this->redirect(route('xero.contacts.show', $this->contactId));
            } else {
                session()->flash('error', 'Failed to update contact. Please try again.');
            }
        } catch (Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }
}
