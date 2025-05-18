<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Xero;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class XeroDashboard extends Component
{
    public function render(): View
    {
        return view('livewire.admin.xero.xero-dashboard');
    }
}
