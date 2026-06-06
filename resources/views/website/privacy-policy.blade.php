@extends('layouts.website')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted small mb-1">Legal</p>
                <h1 class="h3 mb-2">Política de Privacidade</h1>
                <p class="text-muted mb-0">Última atualização: 06/06/2026</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ url('/') }}">Voltar</a>
        </div>

        <div class="bg-white rounded shadow-sm p-4 p-md-5">
            <div class="fs-6 lh-lg">
                <p>
                    A presente Política de Privacidade descreve a forma como a Movvi.pt recolhe, utiliza,
                    conserva e protege os dados pessoais dos utilizadores do website e dos serviços
                    disponibilizados através do mesmo.
                </p>

                <h2 class="h5 mt-4">1. Responsável pelo tratamento</h2>
                <p>
                    O responsável pelo tratamento dos dados pessoais recolhidos através deste website é a
                    entidade responsável pela exploração da Movvi.pt. Para questões relacionadas com dados
                    pessoais, o titular dos dados pode contactar a Movvi.pt através dos canais de contacto
                    disponíveis no website.
                </p>

                <h2 class="h5 mt-4">2. Dados pessoais recolhidos</h2>
                <p>
                    Podemos recolher dados fornecidos diretamente pelo utilizador, incluindo nome, contacto
                    telefónico, endereço de email, localidade, mensagens enviadas através de formulários,
                    preferências indicadas e outros elementos necessários à resposta aos pedidos submetidos.
                </p>
                <p>
                    No âmbito dos serviços prestados, poderão ainda ser tratados dados relacionados com pedidos
                    de cedência de viaturas, contactos comerciais, pedidos de informação, marcações,
                    candidaturas, documentos ou outros conteúdos enviados voluntariamente pelo utilizador.
                </p>

                <h2 class="h5 mt-4">3. Finalidades do tratamento</h2>
                <p>Os dados pessoais são tratados para as seguintes finalidades:</p>
                <ul>
                    <li>Responder a pedidos de contacto, informação, orçamento ou reserva.</li>
                    <li>Gerir pedidos relacionados com cedência de viaturas, stand, transfers e tours.</li>
                    <li>Prestar apoio ao cliente e apoio operacional aos utilizadores.</li>
                    <li>Cumprir obrigações legais, fiscais, administrativas ou contratuais aplicáveis.</li>
                    <li>Melhorar os serviços, o funcionamento do website e a experiência do utilizador.</li>
                </ul>

                <h2 class="h5 mt-4">4. Fundamento legal</h2>
                <p>
                    O tratamento dos dados pessoais pode basear-se no consentimento do titular dos dados, na
                    execução de diligências pré-contratuais ou contratuais, no cumprimento de obrigações legais
                    e no interesse legítimo da Movvi.pt em responder aos contactos recebidos e gerir os seus
                    serviços.
                </p>

                <h2 class="h5 mt-4">5. Conservação dos dados</h2>
                <p>
                    Os dados pessoais serão conservados apenas pelo período necessário às finalidades para as
                    quais foram recolhidos, salvo quando exista obrigação legal de conservação por prazo superior
                    ou necessidade de conservação para defesa de direitos em processo judicial ou administrativo.
                </p>

                <h2 class="h5 mt-4">6. Partilha de dados</h2>
                <p>
                    Os dados pessoais não serão vendidos a terceiros. Poderão ser partilhados com prestadores de
                    serviços que apoiem a atividade da Movvi.pt, como serviços de alojamento, manutenção técnica,
                    comunicação, gestão operacional, contabilidade ou apoio jurídico, sempre na medida necessária
                    e com garantias adequadas de proteção.
                </p>

                <h2 class="h5 mt-4">7. Direitos dos titulares</h2>
                <p>
                    Nos termos da legislação aplicável, o titular dos dados pode solicitar o acesso, retificação,
                    apagamento, limitação do tratamento, oposição ao tratamento e portabilidade dos seus dados,
                    quando aplicável. Pode ainda retirar o consentimento quando o tratamento se baseie nesse
                    fundamento.
                </p>

                <h2 class="h5 mt-4">8. Cookies e tecnologias semelhantes</h2>
                <p>
                    O website pode utilizar cookies ou tecnologias semelhantes para assegurar o seu correto
                    funcionamento, melhorar a navegação e analisar a utilização do serviço. O utilizador pode
                    configurar o seu navegador para bloquear ou eliminar cookies, sem prejuízo de algumas
                    funcionalidades poderem ficar limitadas.
                </p>

                <h2 class="h5 mt-4">9. Segurança</h2>
                <p>
                    A Movvi.pt adota medidas técnicas e organizativas adequadas para proteger os dados pessoais
                    contra acesso não autorizado, perda, alteração, divulgação ou destruição indevida.
                </p>

                <h2 class="h5 mt-4">10. Reclamações</h2>
                <p>
                    O titular dos dados tem o direito de apresentar reclamação junto da autoridade de controlo
                    competente em matéria de proteção de dados, sem prejuízo de poder contactar previamente a
                    Movvi.pt para esclarecimento ou resolução de qualquer questão.
                </p>

                <h2 class="h5 mt-4">11. Alterações a esta política</h2>
                <p>
                    A Movvi.pt pode atualizar esta Política de Privacidade sempre que necessário. A versão em
                    vigor será publicada nesta página, com indicação da data da última atualização.
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
