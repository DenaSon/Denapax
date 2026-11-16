<?php

namespace App\Livewire\Store;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NetworkStatus extends Component
{
    public function render(): View
    {
        return view('livewire.store.network-status');
    }
}
