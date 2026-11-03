<?php

namespace App\Services\Contracts;

class DefaultContractTemplate
{
    public const NAME = 'Contrato de prestação de serviços de motorista e cedência de viatura TVDE';

    public static function content(): string
    {
        return <<<'HTML'
<h1>CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE MOTORISTA E CEDÊNCIA DE VIATURA PARA TVDE</h1>
<p><strong>Contrato n.º {{contract.number}}</strong></p>
<p><strong>PRIMEIRO OUTORGANTE — {{company.name}}</strong>, pessoa coletiva n.º {{company.vat}}, com sede na Avenida Vasco da Gama, N.º 774, Espaço 30, 4430-247 Mafamude, Vila Nova de Gaia, doravante designada “A CONTRATANTE”, representada pelo seu gerente com poderes para o ato.</p>
<p><strong>SEGUNDO OUTORGANTE — {{driver.name}}</strong>, titular do documento de identificação n.º {{driver.citizen_card}}, NIF {{driver.vat}}, carta de condução n.º {{driver.driver_license}}, residente em {{driver.full_address}}, com IBAN {{driver.iban}}, doravante designado “O MOTORISTA”.</p>
<p>Considerando que a CONTRATANTE tem como atividade o transporte de passageiros em plataformas autorizadas e que o MOTORISTA se encontra apto a exercer a atividade TVDE, as partes celebram de boa-fé o presente contrato, que se rege pelas cláusulas seguintes.</p>

<h2>CLÁUSULA PRIMEIRA — OBJETO</h2>
<p>1. O contrato tem por objeto a prestação de serviços de condução no âmbito da atividade TVDE, com utilização exclusiva para esse fim da viatura disponibilizada pela CONTRATANTE.</p>
<p>2. A viatura é: <strong>{{vehicle.brand}} {{vehicle.model}}</strong>, matrícula <strong>{{vehicle.license_plate}}</strong>, ano {{vehicle.year}}.</p>

<h2>CLÁUSULA SEGUNDA — NATUREZA DA RELAÇÃO</h2>
<p>1. O contrato é celebrado em regime de prestação de serviços e não estabelece vínculo laboral entre as partes.</p>
<p>2. O MOTORISTA atua de forma autónoma, sem horários fixos impostos pela CONTRATANTE, gere o seu tempo e assume a responsabilidade pelos resultados da sua atividade.</p>

<h2>CLÁUSULA TERCEIRA — CONDIÇÕES FINANCEIRAS</h2>
<p>1. Pela cedência da viatura, o MOTORISTA pagará à CONTRATANTE €280,00 por semana, a deduzir da faturação semanal.</p>
<p>2. Se a faturação semanal for inferior ao valor acordado, o montante em falta será liquidado até à quarta-feira seguinte ao fecho da semana.</p>
<p>3. O MOTORISTA compromete-se a emitir a respetiva fatura ou recibo verde referente à semana anterior.</p>
<p>4. São deduzidos os impostos, carregamentos, portagens não reembolsadas e demais encargos imputáveis ao MOTORISTA nos termos legais e contratuais.</p>
<p>5. O MOTORISTA poderá circular até 2.000 km por semana sem custo adicional. Cada quilómetro excedente será cobrado a €0,10.</p>
<p>6. Para controlo dos quilómetros, o MOTORISTA enviará semanalmente uma fotografia legível do odómetro.</p>
<p>7. Além do valor indicado no n.º 1, será deduzida da faturação total do MOTORISTA uma taxa de 6%.</p>

<h2>CLÁUSULA QUARTA — CAUÇÃO</h2>
<p>1. O MOTORISTA pagará uma caução de €300,00, em seis parcelas semanais de €50,00.</p>
<p>2. Na entrega da viatura será realizada inspeção, podendo o respetivo registo visual ser partilhado com ambas as partes.</p>
<p>3. A caução será devolvida no prazo máximo de 14 dias após a devolução da viatura, descontados danos, multas, portagens, carregamentos ou outros valores comprovadamente pendentes.</p>

<h2>CLÁUSULA QUINTA — OBRIGAÇÕES DO MOTORISTA</h2>
<p>1. O MOTORISTA assume a responsabilidade pela viatura enquanto estiver sob a sua posse, incluindo portagens, coimas, estacionamento, danos por uso indevido ou negligente, carregamentos e demais encargos diretamente relacionados com a utilização.</p>
<p>2. O MOTORISTA cumprirá a Lei n.º 45/2018 e demais legislação aplicável à atividade TVDE, manterá o veículo limpo, não o utilizará para fins pessoais nem fora de Portugal e não desligará o sistema de rastreio.</p>
<p>3. Antes de iniciar funções, entregará os comprovativos legalmente exigidos para o exercício da atividade.</p>

<h2>CLÁUSULA SEXTA — MANUTENÇÃO E ASSISTÊNCIA TÉCNICA</h2>
<p>1. O MOTORISTA comparecerá nas revisões, inspeções e manutenções agendadas pela CONTRATANTE, mediante aviso prévio mínimo de 24 horas, salvo urgência.</p>
<p>2. Em caso de avaria, comunicará imediatamente à CONTRATANTE e seguirá as instruções recebidas.</p>
<p>3. Durante a imobilização por manutenção programada não serão cobrados os dias sem utilização, exceto quando a imobilização resulte de dano ou negligência do MOTORISTA.</p>

<h2>CLÁUSULA SÉTIMA — VIATURA ELÉTRICA</h2>
<p>1. Sendo a viatura elétrica, o MOTORISTA evitará circular com autonomia inferior a 20% ou 50 km e responderá por danos comprovadamente causados por utilização indevida.</p>
<p>2. A viatura deverá ser devolvida com o mesmo nível de carga com que foi entregue, salvo acordo diferente registado por escrito.</p>

<h2>CLÁUSULA OITAVA — OBRIGAÇÕES DA CONTRATANTE</h2>
<p>1. A CONTRATANTE disponibilizará a viatura com seguro, revisões em dia e em condições de funcionamento.</p>
<p>2. O MOTORISTA respeitará as condições da apólice e suportará a franquia quando o sinistro lhe seja imputável.</p>

<h2>CLÁUSULA NONA — VIGÊNCIA, CESSAÇÃO E DEVOLUÇÃO</h2>
<p>1. O contrato entra em vigor em {{contract.date}} e vigora por <strong>365 dias, prorrogável por períodos sucessivos de 365 dias</strong>, salvo denúncia por qualquer das partes.</p>
<p>2. A denúncia deve ser comunicada por escrito com antecedência mínima de 14 dias.</p>
<p>3. A falta de aviso prévio determina o pagamento de €200,00, acrescido de €45,00 por cada dia de atraso na devolução efetiva da viatura.</p>
<p>4. O incumprimento de obrigações financeiras, o uso indevido da viatura, a recusa injustificada de manutenção ou outra infração grave permite a resolução imediata.</p>
<p>5. No termo do contrato, o MOTORISTA devolverá a viatura limpa, com os documentos, acessórios e carregadores entregues.</p>

<h2>CLÁUSULA DÉCIMA — OBRIGAÇÕES FISCAIS E SEGURANÇA SOCIAL</h2>
<p>São da responsabilidade do MOTORISTA as despesas, seguros pessoais, contribuições para a Segurança Social e impostos inerentes ao exercício da sua atividade, sem prejuízo das obrigações legais da CONTRATANTE.</p>

<h2>CLÁUSULA DÉCIMA PRIMEIRA — PROTEÇÃO DE DADOS E COMUNICAÇÕES AUTOMÁTICAS</h2>
<p>1. Os dados pessoais do MOTORISTA são tratados na medida necessária à celebração e execução deste contrato, ao cumprimento de obrigações legais, à gestão da viatura e à comunicação operacional, nos termos da política de privacidade da CONTRATANTE e da legislação aplicável.</p>
<p>2. O MOTORISTA declara que aceita receber mensagens automáticas relacionadas com <strong>sinalética, dicas, sugestões e procedimentos da empresa</strong>, bem como alertas operacionais necessários à utilização da viatura e ao exercício da atividade TVDE.</p>
<p>3. O MOTORISTA pode exercer os direitos de acesso, retificação, limitação e oposição nos termos legais, através do endereço {{company.email}}.</p>

<h2>CLÁUSULA DÉCIMA SEGUNDA — DISPOSIÇÕES GERAIS E FORO</h2>
<p>1. Em tudo o que for omisso aplicam-se as normas legais em vigor em Portugal, designadamente os artigos 1154.º e seguintes do Código Civil.</p>
<p>2. Para os litígios emergentes deste contrato é competente o foro da Comarca do Porto, sem prejuízo das regras legais imperativas.</p>
<p>3. As alterações contratuais devem ser reduzidas a escrito e aceites por ambas as partes.</p>

<p>Feito em duas vias de igual teor, que ambas as partes leem e assinam.</p>
<p>Vila Nova de Gaia, {{contract.date}}</p>
<div class="signatures">
    <div><strong>O PRIMEIRO OUTORGANTE</strong><br><br>_________________________________<br>{{company.name}}</div>
    <div><strong>O SEGUNDO OUTORGANTE</strong><br><br>_________________________________<br>{{driver.name}}</div>
</div>

<h2>REGISTO DE ENTREGA DA VIATURA</h2>
<p>Data de entrega: ____/____/________ &nbsp;&nbsp; KM: __________ &nbsp;&nbsp; Carga: ______%</p>
<p>Acessórios: ☐ Carregador &nbsp; ☐ Documentos &nbsp; ☐ Cartão &nbsp; ☐ Outro: __________</p>
<p>Inspeção realizada: ☐ Sim ☐ Não &nbsp;&nbsp; Registo partilhado: ☐ Sim ☐ Não</p>
<p>Observações: ____________________________________________________________________</p>
HTML;
    }
}
