<?php

namespace App\Livewire\Storefront;

use App\Support\RecentlyViewedProducts;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.home', [
            'recentlyViewedProducts' => RecentlyViewedProducts::forDisplay(),
        ])
            ->layoutData([
                'title' => 'Shop Trusted Local Vendors in Nepal',
                'description' => 'Discover quality products from trusted local vendors across Nepal. Browse categories, compare prices, and enjoy fast delivery -- all in one place.',
            ]);
    }
}
