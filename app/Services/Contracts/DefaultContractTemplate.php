<?php

namespace App\Services\Contracts;

class DefaultContractTemplate
{
    public const NAME = 'Contrato de prestação de serviços de motorista e cedência de viatura TVDE';
    public const VERSION = 7;

    public static function content(): string
    {
        return <<<'HTML'
<h1>CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE MOTORISTA E CEDÊNCIA DE VIATURA PARA TVDE</h1>
<p><strong>Contrato n.º {{contract.number}}</strong></p>
<p><strong>PRIMEIRO OUTORGANTE - {{company.name}}</strong>, pessoa coletiva n.º {{company.vat}}, titular da licença de operador TVDE n.º <strong>{{company.tvde_license}}</strong>, com sede em {{company.full_address}}, doravante designada "A CONTRATANTE", representada pelo seu gerente com poderes para o ato.</p>
<p><strong>SEGUNDO OUTORGANTE - {{driver.name}}</strong>, titular do {{driver.identification_type}} n.º {{driver.citizen_card}}, NIF {{driver.vat}}, carta de condução n.º {{driver.driver_license}} e certificado de motorista TVDE n.º {{driver.tvde_certificate_number}}, residente em {{driver.full_address}}{{driver.iban_clause}}, doravante designado "O MOTORISTA".</p>
<table class="document-validities">
    <thead><tr><th>Documento</th><th>Validade</th></tr></thead>
    <tbody>
        <tr><td>{{driver.identification_type}}</td><td>{{documents.citizen_card_expires_at}}</td></tr>
        <tr><td>Certificado de motorista TVDE</td><td>{{documents.tvde_certificate_expires_at}}</td></tr>
        <tr><td>Carta de condução</td><td>{{documents.driving_license_expires_at}}</td></tr>
        <tr><td>Autorização de residência (quando aplicável)</td><td>{{documents.residence_permit_expires_at}}</td></tr>
    </tbody>
</table>
<p>Considerando que a CONTRATANTE tem como atividade o transporte de passageiros em plataformas autorizadas e que o MOTORISTA se encontra apto a exercer a atividade TVDE, as partes celebram de boa-fé o presente contrato, que se rege pelas cláusulas seguintes.</p>

<h2>CLÁUSULA PRIMEIRA - OBJETO</h2>
<p>1. O contrato tem por objeto a prestação de serviços de condução no âmbito da atividade TVDE, com utilização exclusiva para esse fim da viatura disponibilizada pela CONTRATANTE.</p>
<table class="vehicle-details">
    <tbody>
        <tr><th>Marca / Modelo</th><td>{{vehicle.brand_model}}</td></tr>
        <tr><th>Ano</th><td>{{vehicle.year}}</td></tr>
        <tr><th>Matrícula</th><td>{{vehicle.license_plate}}</td></tr>
    </tbody>
</table>

<h2>CLÁUSULA SEGUNDA - NATUREZA DA RELAÇÃO</h2>
<p>1. O presente contrato escrito titula a relação entre a CONTRATANTE e o MOTORISTA, sendo-lhe aplicável o artigo 12.º do Código do Trabalho, por força dos n.os 16 e 17 do artigo 10.º da Lei n.º 45/2018, de 10 de agosto, com as alterações introduzidas pela Lei n.º 59/2026, de 25 de agosto, independentemente da designação adotada pelas partes.</p>
<p>2. A execução da relação deve corresponder à situação real. Quando o MOTORISTA exerça a atividade de forma independente, organiza livremente o seu tempo, sem horário fixo imposto, sem exclusividade e sem subordinação jurídica, dentro dos limites legais de condução e repouso.</p>
<p>3. Nenhuma disposição deste contrato afasta normas laborais imperativas ou impede o reconhecimento de um contrato de trabalho quando se verifiquem os respetivos pressupostos legais.</p>

<h2>CLÁUSULA TERCEIRA - CONDIÇÕES FINANCEIRAS</h2>
<p>1. {{financial.weekly_clause}}</p>
<p>2. Se a faturação semanal for inferior ao valor acordado, o montante em falta será liquidado até à quarta-feira seguinte ao fecho da semana.</p>
<p>3. O MOTORISTA compromete-se a emitir a respetiva fatura ou recibo verde referente à semana anterior.</p>
<p>4. São deduzidos os impostos, carregamentos, portagens não reembolsadas e demais encargos imputáveis ao MOTORISTA nos termos legais e contratuais.</p>
<p>5. O MOTORISTA poderá circular até 2.000 km por semana sem custo adicional. Cada quilómetro excedente será cobrado a €0,10, acrescido de IVA à taxa legal aplicável quando devido.</p>
<p>6. Para controlo dos quilómetros, prevalecem os dados do sistema telemático Cartrack ou de outro sistema de rastreio adotado pela CONTRATANTE. A fotografia do odómetro pode ser solicitada como elemento complementar de conferência.</p>
<p>7. {{financial.commission_clause}}</p>
<p>8. Todos os acertos, deduções e encargos devem constar de registo discriminado disponibilizado ao MOTORISTA.</p>

<h2>CLÁUSULA QUARTA - CAUÇÃO</h2>
<p>1. O MOTORISTA pagará uma caução de €300,00, em seis parcelas semanais de €50,00.</p>
<p>2. Na entrega da viatura será realizada inspeção, podendo o respetivo registo visual ser partilhado com ambas as partes.</p>
<p>3. A caução é registada contabilisticamente em conta individual do MOTORISTA, não vence juros salvo disposição legal imperativa e não pode ser utilizada para fins diferentes da garantia das obrigações deste contrato.</p>
<p>4. A caução será devolvida no prazo máximo de 14 dias após a devolução da viatura, descontados apenas danos, multas, portagens, carregamentos ou outros valores comprovadamente pendentes, mediante apresentação do respetivo apuramento.</p>

<h2>CLÁUSULA QUINTA - OBRIGAÇÕES DO MOTORISTA</h2>
<p>1. O MOTORISTA assume a responsabilidade pela viatura enquanto estiver sob a sua posse, incluindo portagens, coimas, estacionamento, danos por uso indevido ou negligente, carregamentos e demais encargos diretamente relacionados com a utilização.</p>
<p>2. O MOTORISTA cumprirá a Lei n.º 45/2018, de 10 de agosto, com as alterações introduzidas pela Lei n.º 59/2026, de 25 de agosto, e demais legislação aplicável à atividade TVDE, manterá o veículo limpo, não o utilizará para fins pessoais nem fora de Portugal e não desligará o sistema de rastreio.</p>
<p>3. Antes de iniciar funções, entregará os comprovativos legalmente exigidos, incluindo certificado de motorista TVDE válido, comprovativo de formação inicial de 50 horas ou título legalmente equivalente e os documentos necessários à verificação de idoneidade.</p>
<p>4. O MOTORISTA manterá o certificado válido, atualmente emitido por cinco anos e renovável por iguais períodos, comunicando e comprovando a respetiva renovação atempada.</p>
<p>5. O MOTORISTA deve possuir domínio funcional da língua portuguesa suficiente para comunicar com utilizadores, autoridades e a CONTRATANTE e para compreender as regras de segurança e da atividade.</p>
<p>6. O MOTORISTA manterá o dístico identificador inamovível emitido pelo IMT visível, intacto e associado ao veículo, comunicando imediatamente qualquer perda, dano ou desconformidade.</p>

<h2>CLÁUSULA SEXTA - MANUTENÇÃO E ASSISTÊNCIA TÉCNICA</h2>
<p>1. O MOTORISTA comparecerá nas revisões, inspeções e manutenções agendadas pela CONTRATANTE, mediante aviso prévio mínimo de 24 horas, salvo urgência.</p>
<p>2. Em caso de avaria, comunicará imediatamente à CONTRATANTE e seguirá as instruções recebidas.</p>
<p>3. Durante a imobilização por manutenção programada não serão cobrados os dias sem utilização, exceto quando a imobilização resulte de dano ou negligência do MOTORISTA.</p>

<h2>CLÁUSULA SÉTIMA - VIATURA ELÉTRICA</h2>
<p>1. Sendo a viatura elétrica, o MOTORISTA evitará circular com autonomia inferior a 20% ou 50 km e responderá por danos comprovadamente causados por utilização indevida.</p>
<p>2. A viatura deverá ser devolvida com o mesmo nível de carga com que foi entregue, salvo acordo diferente registado por escrito.</p>

<h2>CLÁUSULA OITAVA - OBRIGAÇÕES DA CONTRATANTE</h2>
<p>1. A CONTRATANTE disponibilizará a viatura com seguro, revisões em dia e em condições de funcionamento.</p>
<p>2. O MOTORISTA respeitará as condições da apólice e suportará a franquia quando o sinistro lhe seja imputável.</p>

<h2>CLÁUSULA NONA - VIGÊNCIA, CESSAÇÃO E DEVOLUÇÃO</h2>
<p>1. O contrato entra em vigor em {{contract.date}} e vigora por <strong>365 dias, prorrogável por períodos sucessivos de 365 dias</strong>, salvo denúncia por qualquer das partes.</p>
<p>2. A denúncia deve ser comunicada por escrito com antecedência mínima de 14 dias.</p>
<p>3. A falta de aviso prévio determina o pagamento de €200,00, acrescido de €45,00 por cada dia de atraso na devolução efetiva da viatura. Estes valores constituem cláusula penal, devem manter proporcionalidade com o dano efetivamente sofrido e não prejudicam a redução legal ou judicial prevista no artigo 812.º do Código Civil.</p>
<p>4. O incumprimento de obrigações financeiras, o uso indevido da viatura, a recusa injustificada de manutenção ou outra infração grave permite a resolução imediata.</p>
<p>5. No termo do contrato, o MOTORISTA devolverá a viatura limpa, com os documentos, acessórios e carregadores entregues.</p>

<h2>CLÁUSULA DÉCIMA - OBRIGAÇÕES FISCAIS E SEGURANÇA SOCIAL</h2>
<p>São da responsabilidade do MOTORISTA as despesas, seguros pessoais, contribuições para a Segurança Social e impostos inerentes ao exercício da sua atividade, sem prejuízo das obrigações legais da CONTRATANTE.</p>

<h2>CLÁUSULA DÉCIMA PRIMEIRA - PROTEÇÃO DE DADOS E COMUNICAÇÕES</h2>
<p>1. O tratamento dos dados pessoais do MOTORISTA encontra-se descrito no Anexo I - Aviso de Privacidade, que faz parte integrante deste contrato e deve ser disponibilizado e assinado em separado.</p>
<p>2. As mensagens automáticas estritamente operacionais, incluindo alertas de manutenção, segurança, sinalética, procedimentos e utilização da viatura, são enviadas quando necessárias à execução do contrato, ao cumprimento de obrigações legais ou à prossecução de interesses legítimos devidamente ponderados.</p>
<p>3. Comunicações de marketing ou outras finalidades não necessárias dependem de consentimento autónomo, específico e revogável, não sendo a celebração deste contrato condicionada a esse consentimento.</p>

<h2>CLÁUSULA DÉCIMA SEGUNDA - DISPOSIÇÕES GERAIS E FORO</h2>
<p>1. Em tudo o que for omisso aplicam-se as normas legais em vigor em Portugal, designadamente os artigos 1154.º e seguintes do Código Civil.</p>
<p>2. Para os litígios emergentes deste contrato é competente o foro da Comarca do Porto, sem prejuízo das regras legais imperativas.</p>
<p>3. As alterações contratuais devem ser reduzidas a escrito e aceites por ambas as partes.</p>

<p>Feito em duas vias de igual teor, que ambas as partes leem e assinam.</p>
<p>Vila Nova de Gaia, {{contract.date}}</p>
<div class="signatures">
    <div><strong>O PRIMEIRO OUTORGANTE</strong><br><br>_________________________________<br>{{company.name}}</div>
    <div><strong>O SEGUNDO OUTORGANTE</strong><br><br>_________________________________<br>{{driver.name}}</div>
</div>

<section class="vehicle-delivery">
    <h2>REGISTO DE ENTREGA DA VIATURA</h2>
    <p>Data de entrega: ____/____/________ &nbsp;&nbsp; KM: __________ &nbsp;&nbsp; Carga: ______%</p>
    <p>Acessórios: [ ] Carregador &nbsp; [ ] Documentos &nbsp; [ ] Cartão &nbsp; [ ] Outro: __________</p>
    <p>Inspeção realizada: [ ] Sim [ ] Não &nbsp;&nbsp; Registo partilhado: [ ] Sim [ ] Não</p>
    <p>Observações: ____________________________________________________________________</p>
</section>

<div class="page-break"></div>
<h1>ANEXO I - AVISO DE PRIVACIDADE DO MOTORISTA</h1>
<p><strong>Responsável pelo tratamento:</strong> {{company.name}}, NIF {{company.vat}}, com sede em {{company.full_address}}, contacto {{company.email}}.</p>

<h2>1. DADOS TRATADOS E FINALIDADES</h2>
<p>São tratados dados de identificação e contacto, documentos de habilitação e certificação TVDE, dados fiscais e bancários, dados contratuais e de faturação, dados da viatura, localização e telemetria, quilometragem, tempos de atividade, registos de manutenção, ocorrências, sinistros, comunicações e demais dados necessários à gestão da relação e ao cumprimento das obrigações legais.</p>

<h2>2. FUNDAMENTOS JURÍDICOS</h2>
<p>Os tratamentos assentam, consoante a finalidade, na execução do contrato e diligências pré-contratuais, no cumprimento de obrigações legais e regulamentares, na defesa de direitos em processo, no interesse legítimo da CONTRATANTE em gerir a frota, prevenir fraude e garantir segurança, ou no consentimento quando este seja legalmente necessário.</p>

<h2>3. DESTINATÁRIOS E PARTILHA DE DADOS</h2>
<p>Os dados podem ser transmitidos, na medida necessária, a plataformas TVDE, IMT, AMT, Autoridade Tributária, Segurança Social, ACT, autoridades policiais ou judiciais, seguradoras, oficinas, prestadores de contabilidade, pagamentos, alojamento informático, comunicações e telemática, incluindo Cartrack, bem como à plataforma de partilha de dados do IMT prevista na legislação TVDE.</p>

<h2>4. CONSERVAÇÃO</h2>
<p>Os dados são conservados durante a vigência do contrato e, após o seu termo, pelos prazos legais fiscais, contributivos, laborais, contraordenacionais e de prescrição aplicáveis. Dados sem prazo legal específico são eliminados ou anonimizados quando deixarem de ser necessários à finalidade que justificou a recolha.</p>

<h2>5. DIREITOS DO TITULAR</h2>
<p>O MOTORISTA pode exercer os direitos de acesso, retificação, apagamento, limitação, oposição e portabilidade, quando aplicáveis, e retirar consentimentos sem afetar a licitude do tratamento anterior. Pode ainda apresentar reclamação à Comissão Nacional de Proteção de Dados. Os pedidos são dirigidos a {{company.email}}.</p>

<h2>6. LOCALIZAÇÃO, TELEMETRIA E REGISTOS DE VÍDEO</h2>
<p>Os dados de localização e telemetria são tratados para gestão da frota, segurança, controlo contratual de quilometragem, manutenção, prevenção de fraude e cumprimento legal, com acesso limitado a pessoas autorizadas. Se vier a ser ativado registo de vídeo permitido pela legislação TVDE, será prestada informação específica a motoristas e passageiros, aplicadas medidas de cifragem e acesso restrito e respeitado o prazo máximo legal de conservação, atualmente de 30 dias, salvo obrigação de preservação por autoridade competente.</p>

<h2>7. COMUNICAÇÕES AUTOMÁTICAS</h2>
<p>As comunicações operacionais necessárias podem ser enviadas pelos contactos fornecidos. O envio de marketing depende de consentimento separado e pode ser recusado ou revogado a qualquer momento.</p>

<div class="privacy-acknowledgement">
    <p>Declaro que recebi e compreendi o presente Aviso de Privacidade.</p>
    <p>Nome: {{driver.name}}</p>
    <p>Data: ____/____/________</p>
    <p>Assinatura: _________________________________________________</p>
</div>
HTML;
    }
}
