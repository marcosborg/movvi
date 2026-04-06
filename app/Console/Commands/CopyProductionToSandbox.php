<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CopyProductionToSandbox extends Command
{
    protected $signature = 'db:copy-production-to-sandbox
        {--target-database= : Nome da base de dados destino (default da ligacao mysql_sandbox)}
        {--transport=pipe : pipe (stream direto) ou file (dump temporario em disco)}
        {--mysqldump-bin=mysqldump : Binario mysqldump a usar no modo dump}
        {--mysql-bin=mysql : Binario mysql a usar no modo dump}';

    protected $description = 'Clona a base mysql_production para mysql_sandbox com dump/import integral';

    public function handle()
    {
        $arguments = [
            '--source' => 'mysql_production',
            '--target' => 'mysql_sandbox',
            '--mode' => 'dump',
            '--transport' => (string) ($this->option('transport') ?: 'pipe'),
            '--mysqldump-bin' => (string) ($this->option('mysqldump-bin') ?: 'mysqldump'),
            '--mysql-bin' => (string) ($this->option('mysql-bin') ?: 'mysql'),
        ];

        if ($this->option('target-database')) {
            $arguments['--target-database'] = (string) $this->option('target-database');
        }

        return Artisan::call('db:make-install-package', $arguments, $this->output);
    }
}
