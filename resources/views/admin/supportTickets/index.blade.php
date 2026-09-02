@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-life-ring"></i> Suporte técnico</h3>
            @unless($isStaff)
                <a href="{{ route('admin.support-tickets.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Abrir ticket</a>
            @endunless
        </div>
        <div class="box-body">
            <form method="GET" class="form-inline" style="margin-bottom:20px">
                <select name="status" class="form-control">
                    <option value="">Todos os estados</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-default">Filtrar</button>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Ticket</th><th>Assunto</th>@if($isStaff)<th>Cliente</th>@endif<th>Estado</th><th>Responsável</th><th>Última atividade</th></tr></thead>
                    <tbody>
                    @forelse($tickets as $ticket)
                        <tr style="cursor:pointer" onclick="window.location='{{ route('admin.support-tickets.show', $ticket) }}'">
                            <td><strong>{{ $ticket->number }}</strong></td>
                            <td>{{ $ticket->subject }}</td>
                            @if($isStaff)<td>{{ optional($ticket->company)->name }}</td>@endif
                            <td>
                                <span class="label {{ $ticket->status === 'closed' ? 'label-default' : ($ticket->status === 'awaiting_technical' ? 'label-warning' : 'label-info') }}">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td>{{ optional($ticket->assignee)->name ?: 'Por atribuir' }}</td>
                            <td>{{ optional($ticket->last_message_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted" style="padding:35px">Ainda não existem tickets.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection
