<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Contacts;

use Dcblogdev\Xero\Facades\Xero;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contacts')]
class Contacts extends Component
{
    public string $accountNumber = '';

    public string $email = '';

    public string $contactId = '';

    public string $searchTerm = 'Eleven';

    public bool $includeArchived = true;

    public bool $openFilter = false;

    public function render(): View
    {
        return view('livewire.admin.xero.contacts.index');
    }

    /** @return array<int, string> */
    public function contacts(): array
    {
        // $contacts = Xero::contacts()
        // ->filter('page', 1)
        // ->filter('where', 'AccountNumber=="info@abfl.com"')
        // ->filter('where', 'EmailAddress=="info@abfl.com"')
        // ->filter('where', 'ContactID==Guid("74ea95ea-6e1e-435d-9c30-0dff8ae1bd80")')
        // ->filter('searchTerm', 'info')
        // ->filter('includeArchived', 'false')
        // ->filter('order', 'name')
        // ->filter('summaryOnly', 'true')
        // ->get();

        $query = Xero::contacts();

        if ($this->accountNumber) {
            $query->filter('where', 'AccountNumber=="'.$this->accountNumber.'"');
        }

        if ($this->email) {
            $query->filter('where', 'EmailAddress=="'.$this->email.'"');
        }

        if ($this->contactId) {
            $query->filter('where', 'ContactID==Guid("'.$this->contactId.'")');
        }

        if ($this->searchTerm) {
            $query->filter('searchTerm', $this->searchTerm);
        }

        if ($this->includeArchived === false) {
            $query->filter('includeArchived', 'false');
        }

        $query->filter('page', 1)
            ->filter('order', 'name')
            ->filter('summaryOnly', 'true');

        $contacts = $query->get();

        return $contacts;
    }

    public function resetFilters(): void
    {
        $this->reset();
    }
}
