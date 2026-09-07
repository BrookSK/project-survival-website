<?php

/**
 * Dicionário de textos — pt-BR (idioma base).
 *
 * Estrutura por grupos. Acesse via Lang::get('grupo.chave') ou o helper __().
 * Placeholders no formato :nome são substituídos pelo array $replace.
 */
return [
    'common' => [
        'read_more'   => 'Ler mais',
        'back'        => 'Voltar',
        'send'        => 'Enviar',
        'loading'     => 'Carregando...',
        'search'      => 'Buscar',
        'home'        => 'Início',
        'menu'        => 'Menu',
        'close'       => 'Fechar',
    ],
    'home' => [
        'community'   => 'Comunidade',
        'join'        => 'Entrar na comunidade',
    ],
    'news' => [
        'title'       => 'Notícias',
        'latest'      => 'Últimas notícias',
        'published_at' => 'Publicado em :date',
        'empty'       => 'Nenhuma notícia publicada ainda.',
    ],
    'contact' => [
        'title'       => 'Contato',
        'name'        => 'Nome',
        'email'       => 'E-mail',
        'subject'     => 'Assunto',
        'message'     => 'Mensagem',
        'success'     => 'Mensagem enviada com sucesso. Retornaremos em breve.',
        'error'       => 'Não foi possível enviar sua mensagem. Tente novamente.',
    ],
    'footer' => [
        'navigation'  => 'Navegação',
        'legal'       => 'Legal',
        'rights'      => 'Todos os direitos reservados.',
    ],
    'errors' => [
        '404_title'   => 'Página não encontrada',
        '404_message' => 'A página que você procura não existe ou foi movida.',
        '500_title'   => 'Erro interno',
        '500_message' => 'Ocorreu um erro inesperado. Tente novamente mais tarde.',
    ],
];
