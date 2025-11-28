<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Company;

class CompanySelector extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */

    private $companies;

    public function __construct()
    {
        $this->companies = Company::whereHas('user.roles', function ($query) {
            $query->where('title', 'Empresas Associadas')->orWhere('title', 'Admin');
        })->get()->load('user.roles');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.company-selector')->with('companies', $this->companies);
    }
}
