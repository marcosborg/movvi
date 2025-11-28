<?php

namespace App\View\Components;

use App\Models\Testimonial;
use Illuminate\View\Component;

class Testemunhos extends Component
{
    public $testimonials;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->testimonials = Testimonial::latest()->take(10)->get();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.testemunhos');
    }
}
