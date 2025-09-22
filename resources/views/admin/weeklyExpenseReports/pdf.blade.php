<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Extrato</title>
    <style>
        html {
            font-family: sans-serif;
            font-size: 10px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            padding: 8px;
        }

        @page {
            margin-top: 40px;
            margin-bottom: 0;
            margin-left: 40px;
            margin-right: 40px;
        }

        body {
            margin: 0;
        }

        footer {
            position: fixed;
            bottom: -0px;
            left: 0px;
            right: 0px;
            height: 50px;
            line-height: 35px;
        }

        table.bordered {
            border-collapse: collapse;
        }

        table.bordered th {
            border: solid 1px #ccc;
        }

        table.bordered td {
            border: solid 1px #ccc;
        }

        table.bordered thead th {
            background: #eeeeee;
        }
    </style>
</head>

<body>
    <table>
        <thead>
            <tr>
                <td style="vertical-align: top; width: 60%;">
                    <h1>{{ $main_company->name }}</h1>
                    <p>{{ $main_company->vat }}<br>
                        {{ $main_company->address }}, {{ $main_company->zip }}<br>
                        {{ $main_company->location }}<br>
                        {{ $main_company->email }}
                    </p>
                </td>
                <td style="vertical-align: top; width: 40%;">
                    <h1>{{ $tvde_week->start_date }} a {{ $tvde_week->end_date }}</h1>
                    <p>
                        <strong>{{ $company->name }}</strong><br>
                        {{ $company->vat }}<br>
                        {{ $company->address }}, {{ $company->zip }}<br>
                        {{ $company->location }}<br>
                        {{ $company->email }}
                    </p>
                </td>
            </tr>
        </thead>
    </table>
    <table>
        <tbody>
            <tr>
                <td style="vertical-align: top; width: 60%;">
                    <table class="bordered">
                        <thead>
                            <tr>
                                <th colspan="4" style="text-align: left; text-transform: uppercase;">Custos operacionais
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th></th>
                                <th style="text-align: right;">Qtd.</th>
                                <th style="text-align: right;">Unitário</th>
                                <th style="text-align: right;">Semanal</th>
                            </tr>
                            <tr>
                                <th colspan="4" style="text-align: left;">Despesas fixas</th>
                            </tr>
                            @foreach ($company_expenses as $company_expense)
                            <tr>
                                <td>{{ $company_expense->name }}</td>
                                <td style="text-align: right;">{{ $company_expense->qty }}</td>
                                <td style="text-align: right;">{{ $company_expense->weekly_value }} <small>€</small>
                                </td>
                                <td style="text-align: right;">{{ number_format($company_expense->total, 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            @endforeach
                            <tr>
                                <th colspan="4" style="text-align: left;">Despesas variáveis</th>
                            </tr>
                            <tr>
                                <td>Prevenção de frota</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ number_format(-$totals['total_company_adjustments'],
                                    2)
                                    }} <small>€</small></td>
                            </tr>
                            <tr>
                                <td>Park</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ $company_park }} <small>€</small></td>
                            </tr>
                            <tr>
                                <td>Consultoria</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ number_format($total_consultancy, 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            <tr>
                                <td>Pagamentos a motoristas</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ number_format($totals['total_drivers'], 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Total de despesas</th>
                                <td></td>
                                <td></td>
                                <th style="text-align: right;">{{ number_format($final_total, 2) }} <small>€</small>
                                </th>
                            </tr>
                        </tbody>
                    </table>
                    <!--<img src="" style="width: 100%; margin-top: 40px;">-->
                </td>
                <td style="vertical-align: top; width: 40%;">
                    <table class="bordered">
                        <thead>
                            <tr>
                                <th colspan="2" style="text-align: left; text-transform: uppercase;">Rentabilidade
                                    semanal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th style="text-align: left;">Ganhos</th>
                                <td style="text-align: right;">{{ number_format($totals['total_operators'], 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Total de despesas</th>
                                <td style="text-align: right;">{{ number_format($final_total, 2) }} <small>€</small>
                                </td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Rentabilidade</th>
                                <td style="text-align: right;">{{ number_format($profit, 2) }} <small>€</small></td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">ROI (Return of investment)</th>
                                <td style="text-align: right;">
                                    <h1>{{ round($roi) }}<small>%</small></h1>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <img src="{{ $chart1 }}" style="width: 100%; margin-top: 40px;">
                </td>
            </tr>
        </tbody>
    </table>
    <footer>
        Tribos&Montanhas ©
        <?php echo date("Y");?>
    </footer>
</body>