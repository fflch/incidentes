<?php


$menu = [
    [
        'text' => '<i class="fas fa-tv me-1"></i> Home',
        'url' => '/',
    ],
    [
        'text' => '<i class="fas fa-user-check me-1"></i> Meus Alertas',
        'url' => 'meus-alertas',
        'can' => 'user',
    ],
    [
        'text' => '<i class="fas fa-chart-line me-1"></i> Dashboard',
        'url' => 'dashboard',
        'can' => 'admin',
    ],
    [
        'text' => '<i class="fas fa-exclamation-triangle me-1"></i> Alertas Pendentes',
        'url' => 'alerts',
        'can' => 'admin',
    ],
    [
        'text' => '<i class="fas fa-check-circle me-1"></i> Alertas Resolvidos',
        'url' => 'alerts/resolvidos',
        'can' => 'admin',
    ],
    [
        'text' => '<i class="fas fa-magic me-1"></i> Regras de Atribuição',
        'url' => 'assignment-rules',
        'can' => 'admin',
    ],
];

$right_menu = [
    [
        // menu utilizado para views da biblioteca senhaunica-socialite.
        'key' => 'senhaunica-socialite',
    ],
    [
        'key' => 'laravel-tools',
    ],
    [
        'text' => '<i class="fas fa-cog"></i>',
        'title' => 'Configurações',
        'target' => '_blank',
        'url' => config('app.url') . '/item1',
        'align' => 'right',
    ],
];

$cadastrosAuxiliaresUrl = rtrim((string) env('CADASTROS_AUXILIARES_URL', ''), '/');
$cadastrosAuxiliaresMensagensEndpoint = $cadastrosAuxiliaresUrl !== ''
    ? $cadastrosAuxiliaresUrl . '/api/mensagens'
    : '';

return [
    # valor default para a tag title, dentro da section title.
    # valor pode ser substituido pela aplicação.
    'title' => config('app.name'),

    # USP_THEME_SKIN deve ser colocado no .env da aplicação
    'skin' => env('USP_THEME_SKIN', 'uspdev'),

    # chave da sessão. Troque em caso de colisão com outra variável de sessão.
    'session_key' => 'laravel-usp-theme',

    # usado na tag base, permite usar caminhos relativos nos menus e demais elementos html
    # na versão 1 era dashboard_url
    'app_url' => config('app.url'),

    # login e logout
    'logout_method' => 'POST',
    'logout_url' => 'logout',
    'login_url' => 'login',

    # menus
    'menu' => $menu,
    'right_menu' => $right_menu,

    # mensagens flash - https://uspdev.github.io/laravel#31-mensagens-flash
    'mensagensFlash' => false,

    # integração opcional com endpoint de mensagens do cadastros-auxiliares
    'cadastros_auxiliares_mensagens_integracao' => env('CADASTROS_AUXILIARES_MENSAGENS_INTEGRACAO', false),
    'cadastros_auxiliares_mensagens_endpoint_url' => env('CADASTROS_AUXILIARES_MENSAGENS_ENDPOINT_URL', $cadastrosAuxiliaresMensagensEndpoint),
    'cadastros_auxiliares_mensagens_sistema' => env('CADASTROS_AUXILIARES_SISTEMA_NAME', ''),
    'cadastros_auxiliares_mensagens_limite' => (int) env('CADASTROS_AUXILIARES_MENSAGENS_LIMITE', 5),
    'cadastros_auxiliares_mensagens_timeout' => (int) env('CADASTROS_AUXILIARES_MENSAGENS_TIMEOUT', 5),
    'cadastros_auxiliares_mensagens_refresh' => (int) env('CADASTROS_AUXILIARES_MENSAGENS_REFRESH', 30),
    'cadastros_auxiliares_password' => env('CADASTROS_AUXILIARES_PASSWORD', ''),

    # container ou container-fluid
    'container' => 'container-fluid',

];
