@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Importar Movvi Charge
                </div>
                <div class="panel-body">
                    <p class="text-muted">
                        O ficheiro deve ser Excel (.xlsx) e conter a folha <strong>Por Motorista</strong>.
                        A semana é detetada automaticamente pelo padrão <code>YYYY-Www</code>.
                    </p>

                    @can('tesla_charging_create')
                        <form method="POST" action="{{ route('admin.movvi-charge.import') }}" enctype="multipart/form-data" class="form-inline">
                            @csrf
                            <div class="form-group {{ $errors->has('charge_file') ? 'has-error' : '' }}">
                                <label class="sr-only" for="charge_file">Ficheiro Movvi Charge</label>
                                <input type="file" name="charge_file" id="charge_file" class="form-control" accept=".xlsx" required>
                                @if($errors->has('charge_file'))
                                    <span class="help-block" role="alert">{{ $errors->first('charge_file') }}</span>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-import"></i> Importar
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Histórico de importações
                </div>
                <div class="panel-body table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Semana</th>
                                <th>Ficheiro</th>
                                <th>Motoristas</th>
                                <th>Sessões</th>
                                <th>kWh</th>
                                <th>Valor</th>
                                <th>Importado por</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($imports as $import)
                                <tr>
                                    <td>
                                        Semana {{ optional($import->tvdeWeek)->display_number ?? '-' }}/{{ optional($import->tvdeWeek)->display_year ?? '-' }}
                                    </td>
                                    <td>{{ $import->original_filename }}</td>
                                    <td>{{ $import->row_count }}</td>
                                    <td>{{ $import->total_sessions }}</td>
                                    <td>{{ number_format($import->total_kwh, 2, ',', '.') }}</td>
                                    <td>{{ number_format($import->total_value, 2, ',', '.') }} €</td>
                                    <td>{{ optional($import->importedBy)->name ?? '-' }}</td>
                                    <td>{{ optional($import->imported_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Ainda não existem importações Movvi Charge.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $imports->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
