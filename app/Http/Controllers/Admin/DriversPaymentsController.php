<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\Reports;
use App\Models\Receipt;
use SimpleXMLElement;
use Illuminate\Support\Facades\Storage;

class DriversPaymentsController extends Controller
{

    use Reports;

    public function index()
    {
        abort_if(Gate::denies('drivers_payment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $receipts = Receipt::where([
            'verified' => true,
            'paid' => false
        ])->get()->load('driver');

        return view('admin.driversPayments.index')->with([
            'receipts' => $receipts,
        ]);
    }

    private function gerarXmlSepa(array $dadosPagamentos)
    {
        // Cria o XML raiz
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><CstmrCdtTrfInitn></CstmrCdtTrfInitn>');

        // Group Header
        $grpHdr = $xml->addChild('GrpHdr');
        $grpHdr->addChild('MsgId', 'MSG-001'); // Identificação única da mensagem
        $grpHdr->addChild('CreDtTm', date('Y-m-d\TH:i:s')); // Data e hora de criação
        $grpHdr->addChild('NbOfTxs', count($dadosPagamentos)); // Número de transações
        $grpHdr->addChild('CtrlSum', array_sum(array_column($dadosPagamentos, 'Value'))); // Somatório dos valores

        $initgPty = $grpHdr->addChild('InitgPty');
        $initgPty->addChild('Nm', 'Nome da Empresa Ordenante'); // Nome da entidade que inicia a transação

        // Payment Information
        $pmtInf = $xml->addChild('PmtInf');
        $pmtInf->addChild('PmtInfId', 'PMT-001'); // Identificação única do Payment Information
        $pmtInf->addChild('PmtMtd', 'TRF'); // Método de pagamento
        $pmtInf->addChild('NbOfTxs', count($dadosPagamentos));
        $pmtInf->addChild('CtrlSum', array_sum(array_column($dadosPagamentos, 'Value')));

        $dbtr = $pmtInf->addChild('Dbtr');
        $dbtr->addChild('Nm', 'Nome do Devedor');
        $dbtrAcct = $pmtInf->addChild('DbtrAcct');
        $dbtrAcct->addChild('IBAN', 'PT50001800035580939602006'); // IBAN do Devedor

        foreach ($dadosPagamentos as $pagamento) {
            // Credit Transfer Transaction Information
            $cdtTrfTxInf = $pmtInf->addChild('CdtTrfTxInf');

            $pmtId = $cdtTrfTxInf->addChild('PmtId');
            $pmtId->addChild('EndToEndId', 'NOTPROVIDED'); // Identificador E2E (pode ser NOTPROVIDED)

            $amt = $cdtTrfTxInf->addChild('Amt');
            $amt->addChild('InstdAmt', number_format($pagamento['Value'], 2, '.', ''))->addAttribute('Ccy', 'EUR'); // Valor e Moeda

            $cdtr = $cdtTrfTxInf->addChild('Cdtr');
            $cdtr->addChild('Nm', $pagamento['Driver']); // Nome do Beneficiário

            $cdtrAcct = $cdtTrfTxInf->addChild('CdtrAcct');
            $cdtrAcct->addChild('IBAN', $pagamento['IBAN']); // IBAN do Beneficiário

            $cdtrAgt = $cdtTrfTxInf->addChild('CdtrAgt');
            $cdtrAgt->addChild('FinInstnId')->addChild('BIC', 'NOTPROVIDED'); // BIC do banco do Beneficiário
        }

        // Retorna o XML formatado
        return $xml->asXML();
    }

    public function createXml(Request $request)
    {

        $selectedReceipts = $request->selectedReceipts;

        $dadosPagamentos = [];

        foreach ($selectedReceipts as $value) {
            $receipt = Receipt::find($value)->load('driver');
            $dadosPagamentos[] = [
                'Driver' => $receipt->driver->name,
                'Email' => $receipt->driver->email ?? '',
                'IBAN' => $receipt->driver->iban,
                'VAT' => $receipt->driver->driver_vat,
                'Value' => $receipt->amount_transferred
            ];
        }

        // Criar o XML conforme o exemplo anterior
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><CstmrCdtTrfInitn></CstmrCdtTrfInitn>');
        // Exemplo de estruturação do XML com os dados fornecidos

        // Salva o XML em um arquivo temporário
        $fileName = 'sepa_payment_' . time() . '.xml';
        Storage::disk('public')->put($fileName, $xml->asXML());

        // Retorna a URL do arquivo para download
        return response()->json([
            'downloadUrl' => Storage::disk('public')->url($fileName)
        ]);
    }
}
