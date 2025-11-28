@if (auth()->user()->hasRole('Admin'))
<li class="nav-item" style="padding-top: 8px;">
    <select class="form-control select2" style="min-width: 200px;" onchange="selectCompany(this.value)"
        autocomplete="off">
        <option {{ !session()->get('company_id') || session()->get('company_id') == 0 ?
            'selected' : '' }} value="0">Todas as empresas</option>
        @foreach ($companies as $company)
        <option {{ session()->get('company_id') && session()->get('company_id') == $company->id
            ? 'selected' : '' }} value="{{ $company->id }}">{{ $company->name }}</option>
        @endforeach
    </select>
</li>
@endif
