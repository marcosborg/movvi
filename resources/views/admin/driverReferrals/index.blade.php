@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        Indique e Ganhe
    </div>

    <div class="card-body">
        <p class="text-muted">Associe a indicação ao motorista que entrou. Ao completar 30 dias, o estado passa automaticamente para "Prémio disponível".</p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Quem indicou</th>
                        <th>Contacto indicado</th>
                        <th>Motorista associado</th>
                        <th>Estado e prémio</th>
                        <th>Notas</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($referrals as $referral)
                        <tr>
                                <td>
                                    <form id="referral-{{ $referral->id }}" method="POST" action="{{ route('admin.driver-referrals.update', $referral) }}">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                    {{ optional($referral->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td>{{ optional($referral->referrer)->name ?: '-' }}</td>
                                <td>
                                    <strong>{{ $referral->referred_name }}</strong><br>
                                    <a href="tel:{{ $referral->referred_phone }}">{{ $referral->referred_phone }}</a>
                                </td>
                                <td style="min-width: 230px;">
                                    <select name="referred_driver_id" form="referral-{{ $referral->id }}" class="form-control">
                                        <option value="">Ainda não entrou</option>
                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}" {{ (int) $referral->referred_driver_id === (int) $driver->id ? 'selected' : '' }}>
                                                {{ $driver->name }}{{ $driver->start_date ? ' · desde '.$driver->start_date : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="min-width: 190px;">
                                    <select name="status" form="referral-{{ $referral->id }}" class="form-control mb-2">
                                        @foreach($statuses as $status => $label)
                                            <option value="{{ $status }}" {{ $referral->status === $status ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group">
                                        <input type="number" name="reward_amount" form="referral-{{ $referral->id }}" step="0.01" min="0" class="form-control" value="{{ number_format($referral->reward_amount, 2, '.', '') }}">
                                        <div class="input-group-append"><span class="input-group-text">€</span></div>
                                    </div>
                                </td>
                                <td style="min-width: 220px;">
                                    <textarea name="notes" form="referral-{{ $referral->id }}" class="form-control" rows="3">{{ $referral->notes }}</textarea>
                                </td>
                                <td><button class="btn btn-primary" type="submit" form="referral-{{ $referral->id }}">Guardar</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Ainda não existem indicações.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
