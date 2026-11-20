<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebsiteLegalInformationTest extends TestCase
{
    public function test_legal_information_page_exposes_required_consumer_links(): void
    {
        $response = $this->get(route('website.legal-information'));

        $response->assertOk()
            ->assertSee('Adelmo Top, Unipessoal, Lda.')
            ->assertSee('515 025 712')
            ->assertSee('Livro de Reclamações Eletrónico')
            ->assertSee('https://www.livroreclamacoes.pt/Inicio/', false)
            ->assertSee('Resolução de litígios de consumo');
    }
}
