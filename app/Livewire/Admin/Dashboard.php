<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        Gate::authorize('orders.view');

        return view('livewire.admin.dashboard', [
            'categoryCount' => Category::count(),
            'productCount' => Product::count(),
            'publishedCount' => Product::published()->count(),
        ])->layout('layouts.admin');
    }
}
