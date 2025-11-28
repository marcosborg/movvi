<?php

namespace App\View\Components;

use App\Models\HeroBanner;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Hero extends Component
{
    public $heroBanners;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Allow explicit data to be passed; otherwise fetch from the model.
        $this->heroBanners = $heroBanners ?? HeroBanner::all();

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.hero', [
            'heroBanners' => $this->heroBanners,
        ]);
    }
}
