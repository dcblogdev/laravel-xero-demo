<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero\Contacts;

use Dcblogdev\Xero\Facades\Xero;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contact')]
class ShowContact extends Component
{
    /** @var array<int, mixed> */
    public array $contact = [];

    public function mount(string $contactId): void
    {
        try {
            $this->contact = Xero::contacts()->find($contactId);
        } catch (Exception $exception) {
            abort(404);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.xero.contacts.show');
    }
}
