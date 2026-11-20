@extends('layouts.website')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted small mb-1">Legal</p>
                <h1 class="h3 mb-2">Informação Legal e Apoio ao Consumidor</h1>
                <p class="text-muted mb-0">Última atualização: 20/11/2026</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ url('/') }}">Voltar</a>
        </div>

        <div class="bg-white rounded shadow-sm p-4 p-md-5">
            <h2 class="h5">Identificação do prestador</h2>
            <dl class="row mb-4">
                <dt class="col-sm-4">Denominação social</dt>
                <dd class="col-sm-8">Adelmo Top, Unipessoal, Lda.</dd>

                <dt class="col-sm-4">NIPC / número de matrícula</dt>
                <dd class="col-sm-8">515 025 712</dd>

                <dt class="col-sm-4">Registo comercial</dt>
                <dd class="col-sm-8">Conservatória do Registo Comercial, sob o número único 515 025 712</dd>

                <dt class="col-sm-4">Capital social</dt>
                <dd class="col-sm-8">1.000,00 €</dd>

                <dt class="col-sm-4">Sede</dt>
                <dd class="col-sm-8">Rua Pereira Reis, n.º 50, 2.º Esq., 4200-445 Porto, Portugal</dd>

                <dt class="col-sm-4">Email</dt>
                <dd class="col-sm-8"><a href="mailto:adelmotop10@gmail.com">adelmotop10@gmail.com</a></dd>

                <dt class="col-sm-4">Telefone</dt>
                <dd class="col-sm-8"><a href="tel:+351913606800">(+351) 913 606 800</a></dd>

                <dt class="col-sm-4">Licença de operador TVDE</dt>
                <dd class="col-sm-8">13897/2019</dd>
            </dl>

            <h2 class="h5 mt-4">Livro de Reclamações</h2>
            <p>
                O consumidor pode apresentar uma reclamação através da Plataforma Digital do Livro de
                Reclamações. O acesso é disponibilizado de forma direta no botão abaixo.
            </p>
            <p>
                <a href="https://www.livroreclamacoes.pt/Inicio/" target="_blank" rel="noopener noreferrer" class="btn btn-danger fw-semibold">
                    Aceder ao Livro de Reclamações Eletrónico
                </a>
            </p>

            <h2 class="h5 mt-4">Resolução de litígios de consumo</h2>
            <p>
                Em caso de litígio de consumo, o consumidor pode consultar a lista oficial de entidades de
                Resolução Alternativa de Litígios de Consumo e identificar a entidade competente para o seu caso.
            </p>
            <p class="mb-0">
                <a href="https://www.consumidor.gov.pt/atendimento" target="_blank" rel="noopener noreferrer">
                    Direção-Geral do Consumidor — informação e entidades de resolução de litígios
                </a>
            </p>
        </div>
    </div>
</section>
@endsection
