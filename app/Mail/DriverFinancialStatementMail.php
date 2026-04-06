<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DriverFinancialStatementMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public array $statementData,
        public string $filename,
        public string $pdfContent
    ) {
    }

    public function build(): self
    {
        $driver = $this->statementData['driver'];
        $week = $this->statementData['tvde_week'];

        return $this
            ->subject('Extrato semanal ' . $driver->name . ' · semana ' . ($week->display_number ?? $week->number))
            ->view('emails.financialStatement')
            ->with([
                'driver' => $driver,
                'tvde_week' => $week,
                'company' => $this->statementData['company'],
                'final_total' => $this->statementData['final_total'],
            ])
            ->attachData($this->pdfContent, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}
