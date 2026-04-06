@extends('layouts.admin')
@section('styles')
<style>
    table {
        width: 100%;
        font-size: 14px;
    }

    tr {
        line-height: 25px;
    }

    tr:nth-child(even) {
        background-color: #eeeeee;
    }

    tr:nth-child(odd) {
        background-color: #ffffff;
    }

    .btn-sm {
        padding: 0px 5px;
        font-size: 12px;
        line-height: 1.5;
        border-radius: 3px;
        margin-left: 10px;
    }

    .unverified {
        color: #cccccc;
    }

    .verified {
        color: #00a65a;
    }
</style>
@endsection
@section('scripts')
@parent
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    validateData = () => {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)');
        const data = [];
        checkboxes.forEach((checkbox) => {
            let driver = JSON.parse(checkbox.value);
            data.push({
                driver: driver,
                tvde_week_id: {{ session()->get('tvde_week_id') }}
            });
        });
        $.post({
            url: '/admin/company-reports/validate-data',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                data: data,
            },
            success: (resp) => {
                Swal.fire('Atualizado com sucesso').then(() => {
                    location.reload();
                });
            },
            error: (error) => {
                console.log(error);
            }
        });
    }

    function selectAll() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:not(:checked):not(:disabled)');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = true;
        });

        document.getElementById('selectAll').style.display = 'none';
        document.getElementById('unselectAll').style.display = 'block';
        checkCheckedCheckboxes();
    }

    function unselectAll() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });

        document.getElementById('selectAll').style.display = 'block';
        document.getElementById('unselectAll').style.display = 'none';
        checkCheckedCheckboxes();
    }

    function checkCheckedCheckboxes() {
        const checkedCheckboxes = document.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)');
        const validateButton = document.getElementById('validateData');

        validateButton.disabled = checkedCheckboxes.length === 0;
    }

    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', checkCheckedCheckboxes);
    });
</script>
@endsection
@section('content')
<div class="content">
    @if ($company_id == 0)
        <div class="alert alert-info" role="alert">
            Selecione uma empresa para ver os extratos.
        </div>
    @else
        <div class="btn-group btn-group-justified" role="group">
            @foreach ($tvde_years as $tvde_year)
                <a href="/admin/financial-statements/year/{{ $tvde_year->id }}" class="btn btn-default {{ $tvde_year->id == $tvde_year_id ? 'disabled selected' : '' }}">{{ $tvde_year->name }}</a>
            @endforeach
        </div>
        <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
            @foreach ($tvde_months as $tvde_month)
                <a href="/admin/financial-statements/month/{{ $tvde_month->id }}" class="btn btn-default {{ $tvde_month->id == $tvde_month_id ? 'disabled selected' : '' }}">{{ $tvde_month->name }}</a>
            @endforeach
        </div>
        <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
            @foreach ($tvde_weeks as $tvde_week)
                <a href="/admin/financial-statements/week/{{ $tvde_week->id }}" class="btn btn-default {{ $tvde_week->id == $tvde_week_id ? 'disabled selected' : '' }}">Semana de {{ \Carbon\Carbon::parse($tvde_week->start_date)->format('d') }} a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d') }}</a>
            @endforeach
        </div>

        <div class="row" style="margin-top: 20px;">
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading">Total motoristas</div>
                    <div class="panel-body">
                        <h3 style="margin: 0;">{{ number_format($totals['total_drivers'] ?? 0, 2) }} <small>€</small></h3>
                        <small>Valor final semanal validado</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading">Alugueres recebidos</div>
                    <div class="panel-body">
                        <h3 style="margin: 0;">{{ number_format($totals['total_car_hire'] ?? 0, 2) }} <small>€</small></h3>
                        <small>Total de aluguer efetivo da semana</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading">Percentuais recebidos</div>
                    <div class="panel-body">
                        <h3 style="margin: 0;">{{ number_format($totals['total_percent_value'] ?? 0, 2) }} <small>€</small></h3>
                        <small>Total de percentagem cobrada</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading">Receita operacional</div>
                    <div class="panel-body">
                        <h3 style="margin: 0;">{{ number_format(($totals['total_car_hire'] ?? 0) + ($totals['total_percent_value'] ?? 0) + ($totals['total_adjustments'] ?? 0), 2) }} <small>€</small></h3>
                        <small>Aluguer + percentagem + ajustes operacionais</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default" style="margin-top: 20px;">
            <div class="panel-heading">
                Importações da semana
            </div>
            <div class="panel-body" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                @if ($importState['uber'])
                    <form action="/admin/tvde-activities/delete-filter" method="post" style="margin:0;">
                        @csrf
                        <input type="hidden" name="week_filter" value="{{ $tvde_week_id }}">
                        <input type="hidden" name="company_filter" value="{{ $company_id }}">
                        <input type="hidden" name="platform" value="uber">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem a certeza que pretende eliminar a importação Uber desta semana?')">Eliminar Uber</button>
                    </form>
                @else
                    <form action="{{ route('admin.tvde-activities.uploadPlatformCsv') }}" method="POST" enctype="multipart/form-data" class="inline-upload-form" style="margin:0;">
                        @csrf
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <input type="hidden" name="company_id" value="{{ $company_id }}">
                        <input type="hidden" name="platform" value="uber">
                        <input type="file" name="csv_file" id="uberCsvFile" accept=".csv,.txt" style="display:none;" required>
                        <button type="button" class="btn btn-info btn-sm js-inline-upload-trigger" data-file-input="uberCsvFile">Uber</button>
                    </form>
                @endif

                @if ($importState['bolt'])
                    <form action="/admin/tvde-activities/delete-filter" method="post" style="margin:0;">
                        @csrf
                        <input type="hidden" name="week_filter" value="{{ $tvde_week_id }}">
                        <input type="hidden" name="company_filter" value="{{ $company_id }}">
                        <input type="hidden" name="platform" value="bolt">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem a certeza que pretende eliminar a importação Bolt desta semana?')">Eliminar Bolt</button>
                    </form>
                @else
                    <form action="{{ route('admin.tvde-activities.uploadPlatformCsv') }}" method="POST" enctype="multipart/form-data" class="inline-upload-form" style="margin:0;">
                        @csrf
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <input type="hidden" name="company_id" value="{{ $company_id }}">
                        <input type="hidden" name="platform" value="bolt">
                        <input type="file" name="csv_file" id="boltCsvFile" accept=".csv,.txt" style="display:none;" required>
                        <button type="button" class="btn btn-primary btn-sm js-inline-upload-trigger" data-file-input="boltCsvFile">Bolt</button>
                    </form>
                @endif

                @if ($importState['fuel'])
                    <form action="{{ route('admin.combustion-transactions.deleteFilter') }}" method="post" style="margin:0;">
                        @csrf
                        <input type="hidden" name="week_filter" value="{{ $tvde_week_id }}">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem a certeza que pretende eliminar os abastecimentos desta semana?')">Eliminar Abastecimentos</button>
                    </form>
                @else
                    <form action="{{ route('admin.combustion-transactions.uploadSupplierFile') }}" method="POST" enctype="multipart/form-data" class="inline-upload-form" style="margin:0;">
                        @csrf
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <input type="file" name="supplier_file" id="fuelFile" accept=".csv,.txt,.xlsx" style="display:none;" required>
                        <button type="button" class="btn btn-danger btn-sm js-inline-upload-trigger" data-file-input="fuelFile">Abastecimentos</button>
                    </form>
                @endif

                @if ($importState['via_verde'])
                    <form action="{{ route('admin.car-tracks.deleteFilter') }}" method="post" style="margin:0;">
                        @csrf
                        <input type="hidden" name="week_filter" value="{{ $tvde_week_id }}">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem a certeza que pretende eliminar a Via Verde desta semana?')">Eliminar Via Verde</button>
                    </form>
                @else
                    <form action="{{ route('admin.car-tracks.uploadViaVerde') }}" method="POST" enctype="multipart/form-data" class="inline-upload-form" style="margin:0;">
                        @csrf
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <input type="file" name="via_verde_file" id="viaVerdeFile" accept=".csv,.txt,.xlsx" style="display:none;" required>
                        <button type="button" class="btn btn-warning btn-sm js-inline-upload-trigger" data-file-input="viaVerdeFile">Via Verde</button>
                    </form>
                @endif

                @if ($importState['mileage'])
                    <form action="{{ route('admin.company-reports.delete-mileage') }}" method="POST" style="margin:0;">
                        @csrf
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem a certeza que pretende eliminar os quilómetros importados desta semana?')">Eliminar Quilómetros</button>
                    </form>
                @else
                    <form action="{{ route('admin.company-reports.upload-mileage') }}" method="POST" enctype="multipart/form-data" class="inline-upload-form" style="margin:0;">
                        @csrf
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <input type="file" name="mileage_file" id="mileageFile" accept=".csv,.txt,.xlsx" style="display:none;" required>
                        <button type="button" class="btn btn-default btn-sm js-inline-upload-trigger" data-file-input="mileageFile">Quilómetros</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="panel panel-default" style="margin-top: 20px;">
            <div class="panel-heading">
                Faturação
                <span class="label label-default" style="margin-left: 8px;">KM importados: {{ $mileageCount }}</span>
                <a class="btn btn-info btn-sm pull-right" style="margin-left:10px" href="{{ route('admin.company-reports.pdf', ['download' => 1]) }}" target="_blank">Gerar PDF</a>
                <button class="btn btn-success btn-sm pull-right" onclick="validateData()" id="validateData" disabled>Validar selecionados</button>
                <button class="btn btn-primary btn-sm pull-right" onclick="selectAll()" id="selectAll">Selecionar todos</button>
                <button class="btn btn-primary btn-sm pull-right" onclick="unselectAll()" id="unselectAll" style="display: none;">Remover seleção</button>
            </div>
            <div class="table-sticky-container">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Condutor</th>
                            <th>Viatura(s)</th>
                            <th style="text-align: right; background: #eeeeee; display: none;">Bruto Uber</th>
                            <th style="text-align: right; background: #eeeeee; display: none;">Bruto Bolt</th>
                            <th style="text-align: right; background: #eeeeee; display: none;">Bruto operadores</th>
                            <th style="text-align: right;">Líquido Uber</th>
                            <th style="text-align: right;">Líquido Bolt</th>
                            <th style="text-align: right;">KM</th>
                            <th style="text-align: right;">€/km</th>
                            <th style="text-align: right; display: none;">Líquido operadores</th>
                            <th style="text-align: right;">Gorjetas</th>
                            <th style="text-align: right;">Taxa 6%</th>
                            <th style="text-align: right; display: none;">Depois da taxa 6%</th>
                            <th style="text-align: right;">Abastecimento</th>
                            <th style="text-align: right;">Ajustes</th>
                            <th style="text-align: right;">Via verde</th>
                            <th style="text-align: right;">Percentagem</th>
                            <th style="text-align: right;">Aluguer</th>
                            <th style="text-align: right">Valor da semana</th>
                            <th style="text-align: right">Último saldo</th>
                            <th style="text-align: right">Novo saldo</th>
                            <th style="text-align: center">Estado</th>
                            <th style="text-align: right">Validar</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($drivers as $driver)
                            @if ($driver->earnings)
                                <tr>
                                    <td>{{ $driver->name }}</td>
                                    <td>{{ $driver->license_plate ?? '-' }}</td>
                                    <td style="text-align: right; background: #eeeeee; display: none;">{{ number_format($driver->earnings['uber']['uber_gross'] ?? 0, 2) }} <small>€</small></td>
                                    <td style="text-align: right; background: #eeeeee; display: none;">{{ number_format($driver->earnings['bolt']['bolt_gross'] ?? 0, 2) }} <small>€</small></td>
                                    <td style="text-align: right; background: #eeeeee; display: none;">{{ number_format($driver->earnings['total_gross'] ?? 0, 2) }} <small>€</small></td>
                                    <td style="text-align: right">{{ number_format($driver->earnings['uber']['uber_net'] ?? 0, 2) }}<small> €</small></td>
                                    <td style="text-align: right">{{ number_format($driver->earnings['bolt']['bolt_net'] ?? 0, 2) }} <small>€</small></td>
                                    <td style="text-align: right">{{ number_format($driver->weekly_km ?? 0, 1) }} <small>km</small></td>
                                    <td style="text-align: right">{{ number_format($driver->earnings_per_km ?? 0, 3) }} <small>€</small></td>
                                    <td style="text-align: right; display: none;">{{ number_format($driver->earnings['total_net'] ?? 0, 2) }} <small>€</small></td>
                                    <td style="text-align: right;">{{ number_format($driver->earnings['tips_total'], 2) }} <small>€</small></td>
                                    <td style="text-align: right; color: red;">{{ number_format($driver->earnings['iva_value'], 2) }} <small>€</small></td>
                                    <td style="text-align: right; display: none;">{{ number_format($driver->earnings['total_after_vat'], 2) }} <small>€</small></td>
                                    <td style="text-align: right;">-{{ number_format($driver->fuel, 2) }} <small>€</small></td>
                                    <td style="text-align: right">{{ number_format($driver->adjustments, 2) }} <small>€</small><button class="btn btn-sm" data-toggle="popover" title="Movimentos" data-html="true" data-content="
                                        @foreach($driver->earnings['adjustments_array'] as $adjustment)
                                            <strong>{{ $adjustment['name'] }} ({{ $adjustment['category_label'] ?? ($adjustment['category'] ?? 'geral') }}): </strong>{{ $adjustment['type'] == 'deduct' ? '-' : '' }}{{ $adjustment['amount'] }}€<br>
                                        @endforeach
                                        "><i class="fa-fw fas fa-eye"></i></button></td>
                                    <td style="text-align: right">{{ number_format($driver->earnings['car_track'], 2) }} <small>€</small></td>
                                    <td style="text-align: right; color: red;">{{ number_format($driver->earnings['percent_value'], 2) }} <small>€</small></td>
                                    <td style="text-align: right">-{{ number_format($driver->earnings['car_hire'], 2) }} <small>€</small>
                                        @if(($driver->earnings['abatimento_aluguer'] ?? 0) > 0)
                                            <br><small class="text-success">abatimento: {{ number_format($driver->earnings['abatimento_aluguer'], 2) }} €</small>
                                        @endif
                                    </td>
                                    <td style="text-align: right">{{ number_format($driver->total, 2) }} <small>€</small></td>
                                    <td style="text-align: right">{{ number_format($driver->last_balance, 2) }} <small>€</small></td>
                                    <td style="text-align: right">{{ number_format($driver->new_balance, 2) }} <small>€</small></td>
                                    <td style="text-align: center">
                                        @if($driver->balance_manual_status_label)
                                            <span class="label label-primary">{{ $driver->balance_manual_status_label }}</span>
                                        @else
                                            <span class="label label-default">Sem estado</span>
                                        @endif
                                        @if($driver->balance_record_id)
                                            <br><a class="btn btn-xs btn-info" href="{{ route('admin.drivers-balances.edit', $driver->balance_record_id) }}" target="_blank" style="margin-top: 4px;">Editar</a>
                                        @endif
                                    </td>
                                    <td style="text-align: right">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" value="{{ json_encode($driver) }}" {{ $driver->current_account ? 'checked disabled' : '' }}><span class="glyphicon glyphicon-ok green-checkmark {{ $driver->current_account ? 'verified' : 'unverified' }}"></span>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" onclick="deleteData({{ $tvde_week_id }}, {{ $driver->id }})" class="btn btn-sm"><span class="glyphicon glyphicon-trash"></span></button>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Totais</th>
                            <th></th>
                            <th style="text-align: right; background: #eeeeee; display: none;">{{ number_format($totals['gross_uber'], 2) }} <small>€</small></th>
                            <th style="text-align: right; background: #eeeeee; display: none;">{{ number_format($totals['gross_bolt'], 2) }} <small>€</small></th>
                            <th style="text-align: right; background: #eeeeee; display: none;">{{ number_format($totals['total_operators'], 2) }} <small>€</small></th>
                            <th style="text-align: right;">{{ number_format($totals['net_uber'], 2) }} <small>€</small></th>
                            <th style="text-align: right;">{{ number_format($totals['net_bolt'], 2) }} <small>€</small></th>
                            <th style="text-align: right;">{{ number_format($totals['total_weekly_km'] ?? 0, 1) }} <small>km</small></th>
                            <th style="text-align: right;">{{ number_format($totals['total_earnings_per_km'] ?? 0, 3) }} <small>€</small></th>
                            <th style="text-align: right; display: none;">{{ number_format($totals['total_net_operators'], 2) }} <small>€</small></th>
                            <th style="text-align: right;">{{ number_format($totals['tips_total'], 2) }} <small>€</small></th>
                            <th style="text-align: right; color: red;">{{ number_format($totals['total_iva_value'], 2) }} <small>€</small></th>
                            <th style="text-align: right; display: none;">{{ number_format($totals['total_earnings_after_vat'], 2) }} <small>€</small></th>
                            <th style="text-align: right;">-{{ number_format($totals['total_fuel_transactions'], 2) }} <small>€</small></th>
                            <th style="text-align: right;">{{ number_format($totals['total_adjustments'], 2) }} <small>€</small></th>
                            <th style="text-align: right;">{{ number_format($totals['total_car_track'], 2) }} <small>€</small></th>
                            <th style="text-align: right; color: red;">{{ number_format($totals['total_percent_value'], 2) }} <small>€</small></th>
                            <th style="text-align: right;">-{{ number_format($totals['total_car_hire'], 2) }} <small>€</small></th>
                            <th style="text-align: right;">{{ number_format($totals['total_drivers'], 2) }} <small>€</small></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="panel panel-default" style="margin-top: 20px;">
            <div class="panel-heading">
                Resumo de categorias
            </div>
            <div class="panel-body">
                <table class="table table-bordered table-striped table-sm" style="margin-bottom: 0;">
                    <tbody>
                        <tr>
                            <th>Ajustes gerais / manuais</th>
                            <td style="text-align: right;">{{ number_format($totals['total_general_adjustments'] ?? 0, 2) }} <small>€</small></td>
                        </tr>
                        <tr>
                            <th>Abatimento de aluguer</th>
                            <td style="text-align: right;">{{ number_format($totals['total_rent_discounts'] ?? 0, 2) }} <small>€</small></td>
                        </tr>
                        <tr>
                            <th>Diferença de faturação mínima</th>
                            <td style="text-align: right;">{{ number_format($totals['total_minimum_billing_difference'] ?? 0, 2) }} <small>€</small></td>
                        </tr>
                        <tr>
                            <th>Caução recebida</th>
                            <td style="text-align: right;">{{ number_format($totals['total_caution_received'] ?? 0, 2) }} <small>€</small></td>
                        </tr>
                        <tr>
                            <th>Caução devolvida</th>
                            <td style="text-align: right;">{{ number_format($totals['total_caution_returned'] ?? 0, 2) }} <small>€</small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
@section('scripts')
<script>
    $(function() {
        $('[data-toggle="popover"]').popover();

        document.querySelectorAll('.js-inline-upload-trigger').forEach((button) => {
            button.addEventListener('click', function () {
                const fileInput = document.getElementById(this.dataset.fileInput);

                if (fileInput) {
                    fileInput.click();
                }
            });
        });

        document.querySelectorAll('.inline-upload-form input[type="file"]').forEach((fileInput) => {
            fileInput.addEventListener('change', function () {
                if (!this.files.length) {
                    return;
                }

                this.form.submit();
            });
        });
    });

    function deleteData(tvde_week_id, driver_id) {
        Swal.fire({
            title: 'Tem a certeza?',
            text: 'Isto irá remover os dados do extrato deste condutor para esta semana.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, remover!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.get(`/admin/company-reports/delete-data/${tvde_week_id}/${driver_id}`, function(response) {
                    Swal.fire(
                        'Removido!',
                        'Os dados foram removidos com sucesso.',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                }).fail(function() {
                    Swal.fire(
                        'Erro!',
                        'Ocorreu um erro ao remover os dados.',
                        'error'
                    );
                });
            }
        });
    }
</script>
@endsection
