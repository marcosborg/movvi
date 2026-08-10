<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\TvdeActivityController;
use Tests\TestCase;

class TvdeActivityPlatformCsvTest extends TestCase
{
    public function test_it_reads_current_uber_csv_tips_column(): void
    {
        $harness = new class extends TvdeActivityController {
            public function mapping(array $header): array
            {
                return $this->resolvePlatformCsvMappingFromHeader($header, 'uber', $this->platformCsvMapping('uber'));
            }

            public function activity(array $row, array $mapping): ?array
            {
                return $this->mapPlatformActivityRow($row, $mapping, 10, 20, 30);
            }
        };

        $header = [
            'UUID do motorista',
            'Nome próprio do motorista',
            'Apelido do motorista',
            'Pago a si',
            'Pago a si : Os seus rendimentos',
            'Pago a si : Saldo da viagem : Pagamentos : Dinheiro recebido',
            'Pago a si : Os seus rendimentos : Tarifa',
            'Pago a si : Os seus rendimentos : Impostos',
            'Pago a si:Os seus rendimentos:Tarifa:Tarifa',
            'Pago a si:Os seus rendimentos:Tarifa:Ajuste',
            'Pago a si:Os seus rendimentos:Tarifa:Cancelamento',
            'Pago a si:Os seus rendimentos:Tarifa:Ajuste da taxa de serviço',
            'Pago a si:Os seus rendimentos:Tarifa:Tarifa dinâmica',
            'Pago a si:Os seus rendimentos:Tarifa:Taxa de reserva',
            'Pago a si:Os seus rendimentos:Tarifa:UberX Priority',
            'Pago a si:Os seus rendimentos:Tarifa:Imposto sobre a tarifa',
            'Pago a si:Os seus rendimentos:Tarifa:Tempo de espera na recolha',
            'Pago a si:Os seus rendimentos:Taxa de serviço',
            'Pago a si:Os seus rendimentos:Gratificação',
            'Pago a si:Os seus rendimentos:Outros rendimentos:Taxa de aeroporto',
        ];
        $row = [
            '2fbae627-2ccd-4b42-a500-9f0ab3386743',
            'BRUNO',
            'DINIS',
            '731.24',
            '730.74',
            '',
            '957.32',
            '',
            '844.49',
            '10.06',
            '2.83',
            '-4.3',
            '38.27',
            '3.01',
            '6.41',
            '54.3',
            '0.98',
            '-238.1',
            '11.58',
            '-0.06',
        ];

        $mapping = $harness->mapping($header);
        $activity = $harness->activity($row, $mapping);

        $this->assertSame(18, $mapping['tips']);
        $this->assertSame(957.32, $activity['gross']);
        $this->assertSame(731.24, $activity['net']);
        $this->assertSame(11.58, $activity['tips']);
    }

    public function test_it_reads_current_bolt_csv_export_format(): void
    {
        $harness = new class extends TvdeActivityController {
            public function rows(string $path): array
            {
                return $this->readBoltCsvRows($path);
            }

            public function mapping(array $header): array
            {
                return $this->resolvePlatformCsvMappingFromHeader($header, 'bolt', $this->platformCsvMapping('bolt'));
            }

            public function activity(array $row, array $mapping): ?array
            {
                return $this->mapPlatformActivityRow($row, $mapping, 10, 20, 30);
            }
        };

        $path = tempnam(sys_get_temp_dir(), 'bolt-csv-');

        file_put_contents($path, implode("\n", [
            "\xEF\xBB\xBFMotorista,Email,Telemóvel,Ganhos brutos (total)|€,Ganhos brutos (pagamentos na app)|€,IVA sobre os ganhos brutos (pagamentos na app)|€,Ganhos brutos (pagamentos em dinheiro)|€,IVA sobre os ganhos brutos (pagamentos em dinheiro)|€,Dinheiro recebido|€,Gorjetas dos passageiros|€,Ganhos da campanha|€,Reembolsos de despesas|€,Taxas de cancelamento|€,IVA das taxas de cancelamento|€,Portagens|€,Taxas de reserva|€,IVA das taxas de reserva|€,Total de taxas|€,Comissões|€,Reembolsos aos passageiros|€,Outras taxas|€,Ganhos líquidos|€,Pagamento previsto|€,Ganhos brutos por hora|€/h,Ganhos líquidos por hora|€/h,Desconto de comissão (in-app)|€,Desconto da comissão (dinheiro)|€,Identificador do motorista,Identificador individual,Nível,Categorias ativas,Viagens pagas com dinheiro ativadas,Pontuação de motorista|%,Viagens terminadas,Taxa de aceitação total|%,Tempo online (min),Utilização|%,Taxa de finalização (todas as viagens)|%,Taxa de finalização (viagens aceites)|%,Distância média das viagens|km,Distância total das viagens|km,Classificação média do motorista|★",
            "António Telinhos,antoniotelinhos@gmail.com,3.51913E+11,168.92,164.27,7.57,0,0,0,1,0,0,3.65,0.21,0.3,0,0,39.74,39.74,0,0,129.19,129.19,9.78,7.48,31.17,0,bf0a1f43-c31e-4710-b637-82d53465805b,f8bc360e-a998-42fd-9360-08c50471a0e7,Silver; Level=1; Status=ACTIVE,5-Apr,sim,89,18,13,1035.98,34,9,39,10.48,188.66,4.6",
        ]));

        try {
            $rows = $harness->rows($path);
            $mapping = $harness->mapping($rows[0]);
            $activity = $harness->activity($rows[1], $mapping);
        } finally {
            @unlink($path);
        }

        $this->assertCount(42, $rows[0]);
        $this->assertSame(3, $mapping['gross']);
        $this->assertSame(21, $mapping['net']);
        $this->assertSame(9, $mapping['tips']);
        $this->assertSame(28, $mapping['driver_code_stable']);
        $this->assertSame('f8bc360e-a998-42fd-9360-08c50471a0e7', $activity['driver_code']);
        $this->assertSame(168.92, $activity['gross']);
        $this->assertSame(129.19, $activity['net']);
        $this->assertSame(1.0, $activity['tips']);
    }

    public function test_it_reads_bolt_csv_rows_wrapped_in_an_extra_quote_layer(): void
    {
        $harness = new class extends TvdeActivityController {
            public function rows(string $path): array
            {
                return $this->readBoltCsvRows($path);
            }

            public function mapping(array $header): array
            {
                return $this->resolvePlatformCsvMappingFromHeader($header, 'bolt', $this->platformCsvMapping('bolt'));
            }

            public function activity(array $row, array $mapping): ?array
            {
                return $this->mapPlatformActivityRow($row, $mapping, 10, 20, 30);
            }
        };

        $header = ['Motorista', 'Email', 'Telemóvel', 'Ganhos brutos (total)|€'];
        $header = array_merge($header, array_fill(0, 5, 'Campo'), ['Gorjetas dos passageiros|€']);
        $header = array_merge($header, array_fill(0, 11, 'Campo'), ['Ganhos líquidos|€']);
        $header = array_merge($header, array_fill(0, 5, 'Campo'), ['Identificador do motorista', 'Identificador individual', 'Nível']);
        $header = array_merge($header, array_fill(0, 12, 'Campo'));

        $row = array_fill(0, 42, '');
        $row[0] = 'Alexandre Moreira';
        $row[3] = '301.66';
        $row[9] = '2.00';
        $row[21] = '232.83';
        $row[27] = '388b0192-a54c-41a2-8754-b301643a96a7';
        $row[28] = 'afa0bfb7-3d9b-4c1d-8a97-b10eec3d6338';
        $row[29] = 'Silver; Level=1; Status=ACTIVE';

        $wrap = function (array $values): string {
            $encoded = implode(',', array_map(fn (string $value) => '"' . str_replace('"', '""', $value) . '"', $values));

            return '"' . str_replace('"', '""', $encoded) . '";;';
        };

        $path = tempnam(sys_get_temp_dir(), 'bolt-csv-');
        file_put_contents($path, "\xEF\xBB\xBF" . $wrap($header) . "\r\n" . $wrap($row) . "\r\n");

        try {
            $rows = $harness->rows($path);
            $mapping = $harness->mapping($rows[0]);
            $activity = $harness->activity($rows[1], $mapping);
        } finally {
            @unlink($path);
        }

        $this->assertCount(42, $rows[0]);
        $this->assertCount(42, $rows[1]);
        $this->assertSame('Silver; Level=1; Status=ACTIVE', $rows[1][29]);
        $this->assertSame('afa0bfb7-3d9b-4c1d-8a97-b10eec3d6338', $activity['driver_code']);
        $this->assertSame(301.66, $activity['gross']);
        $this->assertSame(232.83, $activity['net']);
        $this->assertSame(2.0, $activity['tips']);
    }
}
