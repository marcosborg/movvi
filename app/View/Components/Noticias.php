<?php

namespace App\View\Components;

use App\Models\NewsArticle;
use Illuminate\View\Component;

class Noticias extends Component
{
    public $newsArticles;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->newsArticles = NewsArticle::with('media')
            ->latest()
            ->take(3)
            ->get();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.noticias');
    }
}
