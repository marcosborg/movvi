<?php

namespace App\View\Components;

use App\Models\HomeInfo;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class About extends Component
{
    public Collection $abouts;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->abouts = HomeInfo::with('media')
            ->latest()
            ->get();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.about', [
            'abouts' => $this->abouts,
        ]);
    }
}
