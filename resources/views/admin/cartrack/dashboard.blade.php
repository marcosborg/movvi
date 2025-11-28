@extends('layouts.admin')

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading d-flex justify-content-between align-items-center">
            <div>
                <strong>Cartrack · Quilómetros & Incidentes</strong>
                @if($from && $to)
                    <span class="text-muted small">({{ $from }} a {{ $to }})</span>
                @endif
            </div>
        </div>

        <div class="panel-body">
            <form method="GET" class="form-inline" style="margin-bottom: 15px;">
                <div class="form-group" style="min-width: 240px;">
                    <label for="tvde_week_id" class="control-label" style="margin-right: 6px;">Semana</label>
                    <select name="tvde_week_id" id="tvde_week_id" class="form-control select2">
                        @foreach($tvde_weeks as $week)
                            <option value="{{ $week->id }}" @if($tvde_week && $tvde_week->id === $week->id) selected @endif>
                                Semana {{ $week->number }} ({{ $week->start_date }} → {{ $week->end_date }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Atualizar</button>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="cartrack-table">
                    <thead>
                        <tr>
                            <th>Motorista</th>
                            <th>Matrícula</th>
                            <th class="text-right">Km (semana)</th>
                            <th class="text-right">Travagens</th>
                            <th class="text-right">Viragens bruscas</th>
                            <th class="text-right">Acelerações</th>
                            <th class="text-right">Outros</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr class="js-cartrack-row" data-driver-id="{{ $row['driver']->id }}">
                                <td>{{ $row['driver']->name }}</td>
                                <td class="js-plate">{{ $row['license'] }}</td>
                                <td class="text-right js-km"><span class="text-muted">—</span></td>
                                <td class="text-right js-braking">0</td>
                                <td class="text-right js-cornering">0</td>
                                <td class="text-right js-acceleration">0</td>
                                <td class="text-right js-other">0</td>
                                <td class="js-status">
                                    @if($row['error'])
                                        <span class="label label-danger">Erro</span>
                                        <small class="text-danger d-block">{{ \Illuminate\Support\Str::limit($row['error'], 80) }}</small>
                                    @else
                                        <span class="label label-default">Pendente</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    Sem motoristas com matrícula definida para este período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-muted small" style="margin-top: 10px;">
                Distâncias somadas a partir das trips por matrícula (conversão para km aplicada quando o total parece vir em metros).
                Incidentes contam travagens/viragens bruscas e acelerações com base no tipo de evento devolvido pelo Cartrack.
            </p>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script>
        (function () {
            const fetchUrl = "{{ route('admin.cartrack.fetch') }}";
            const weekId = document.getElementById('tvde_week_id')?.value || null;
            const rows = Array.from(document.querySelectorAll('.js-cartrack-row'));
            const queue = rows.slice();
            const maxConcurrent = 3;
            let active = 0;

            const setStatus = ($row, html) => {
                const cell = $row.querySelector('.js-status');
                if (cell) cell.innerHTML = html;
            };

            const fillData = ($row, data) => {
                if (data.plate) {
                    const plateCell = $row.querySelector('.js-plate');
                    if (plateCell && !plateCell.textContent.trim()) {
                        plateCell.textContent = data.plate;
                    }
                }
                const kmCell = $row.querySelector('.js-km');
                if (kmCell) kmCell.innerHTML = data.km !== undefined ? Number(data.km).toLocaleString('pt-PT', {minimumFractionDigits: 2}) : '<span class="text-muted">—</span>';
                const fields = ['braking','cornering','acceleration','other'];
                fields.forEach(f => {
                    const cell = $row.querySelector('.js-' + f);
                    if (cell) cell.textContent = data.incidents && data.incidents[f] !== undefined ? data.incidents[f] : 0;
                });
                setStatus($row, '<span class="label label-success">OK</span>');
            };

            const fillError = ($row, message) => {
                const kmCell = $row.querySelector('.js-km');
                if (kmCell) kmCell.innerHTML = '<span class="text-muted">—</span>';
                setStatus($row, '<span class="label label-danger">Erro</span><small class="text-danger d-block">' + (message || 'Falha ao obter dados') + '</small>');
            };

            const processNext = () => {
                if (active >= maxConcurrent || queue.length === 0) return;
                const $row = queue.shift();
                active++;

                setStatus($row, '<span class="label label-info">A carregar...</span>');

                const driverId = $row.dataset.driverId;
                const params = new URLSearchParams({driver_id: driverId});
                if (weekId) params.append('tvde_week_id', weekId);

                fetch(fetchUrl + '?' + params.toString(), {
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                    .then(resp => resp.json().then(body => ({ok: resp.ok, status: resp.status, body})))
                    .then(({ok, body}) => {
                        if (ok && !body.error) {
                            fillData($row, body);
                        } else {
                            fillError($row, body.error || 'Erro ' + (body.status || 'desconhecido'));
                        }
                    })
                    .catch(() => fillError($row, 'Erro de rede'))
                    .finally(() => {
                        active--;
                        processNext();
                    });
            };

            for (let i = 0; i < maxConcurrent; i++) {
                processNext();
            }
        })();
    </script>
@endsection
