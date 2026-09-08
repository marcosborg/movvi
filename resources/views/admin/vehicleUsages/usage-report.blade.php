@php
    $num = fn($value) => number_format($value, 2, ',', '.');
@endphp
<div class="usage-legend">
@foreach($report['categories'] as $key => $label)
    <span><i style="background:{{ $report['colors'][$key] }}"></i>{{ $label }}</span>
@endforeach
</div>
@if(empty($report['rows']))
    <p>Nenhuma viatura disponível para esta seleção e período.</p>
@else
@if($section === 'all' || $section === 'timeline')
<div class="usage-section {{ $pdf ? '' : 'tab-pane active' }}" id="timeline" role="tabpanel">
    <h3>Linha do Tempo das Viaturas</h3>
    <p class="text-muted">Da esquerda para a direita: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}. @unless($pdf) Passe sobre os intervalos para ver as datas e o motorista. @endunless</p>
    <table class="usage-table"><thead><tr><th style="width:18%">Viatura</th><th>Utilização ao longo do período</th><th style="width:12%">Dias em uso</th></tr></thead><tbody>
    @foreach($report['rows'] as $row)
        <tr><td><strong>{{ $row['plate'] }}</strong><br><small>{{ $row['model'] }}</small></td><td>
            <div class="usage-timeline">
            @foreach($row['segments'] as $segment)
                <span style="left:{{ $segment['offset'] }}%;width:{{ $segment['width'] }}%;background:{{ $report['colors'][$segment['category']] }}" title="{{ $report['categories'][$segment['category']] }} | {{ $segment['start'] }} até {{ $segment['end'] }} (fim exclusivo) | {{ $segment['driver'] }}"></span>
            @endforeach
            </div>
        </td><td class="number">{{ $num($row['stats']['usage']) }}</td></tr>
    @endforeach
    </tbody></table>
</div>
@endif
@if($section === 'all' || $section === 'chart')
<div class="usage-section {{ $pdf ? '' : 'tab-pane' }}" id="chart" role="tabpanel">
    <h3>Gráfico da Taxa de Ocupação</h3>
    <p class="text-muted">Percentagem do tempo disponível no período. Ordenação por utilização; os intervalos sem registo contam como sem utilização.</p>
    <table class="usage-table"><thead><tr><th style="width:18%">Viatura</th><th>0% <span style="float:right">100%</span></th><th style="width:12%">Ocupação</th></tr></thead><tbody>
    @foreach($report['ranking'] as $row)
        <tr><td>{{ $row['plate'] }}</td><td><div class="usage-timeline">
        @php($offset = 0)
        @foreach($report['categories'] as $key => $label)
            @php($width = $row['stats']['total'] ? 100 * $row['stats'][$key] / $row['stats']['total'] : 0)
            <span title="{{ $label }}: {{ $num($width) }}%" style="left:{{ $offset }}%;width:{{ $width }}%;background:{{ $report['colors'][$key] }}"></span>
            @php($offset += $width)
        @endforeach
        </div></td><td class="number">{{ $num($row['stats']['percent']) }}%</td></tr>
    @endforeach
    </tbody></table>
</div>
@endif
@if($section === 'all' || $section === 'detail')
<div class="usage-section {{ $pdf ? '' : 'tab-pane' }}" id="detail" role="tabpanel">
    <h3>Detalhe da Ocupação por Viatura</h3>
    <table class="usage-table"><thead><tr><th>Viatura</th><th>Ano / período</th><th>Dias em uso</th><th>Dias disponíveis</th><th>Manutenção</th><th>Sinistrado</th><th>Uso pessoal</th><th>Sem utilização</th><th>Ocupação</th></tr></thead><tbody>
    @foreach($report['rows'] as $row)
        @foreach($row['years'] as $year => $stats)
        <tr><td>{{ $row['plate'] }}</td><td>{{ $year }}</td><td class="number">{{ $num($stats['usage']) }}</td><td class="number">{{ $num($stats['total']) }}</td><td class="number">{{ $num($stats['maintenance']) }}</td><td class="number">{{ $num($stats['accident']) }}</td><td class="number">{{ $num($stats['personal']) }}</td><td class="number">{{ $num($stats['unassigned']) }}</td><td class="number"><strong>{{ $num($stats['percent']) }}%</strong></td></tr>
        @endforeach
    @endforeach
    </tbody></table>
    <p>Os anos são limitados às datas selecionadas. Para ver um mês, selecione o início e o fim desse mês no filtro de período.</p>
</div>
@endif
@endif
