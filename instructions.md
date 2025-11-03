# 📘 LaraInk – Documentação Oficial (Final Consolidada)

---

## 1. Objetivo e visão geral
O **LaraInk** é um compilador que transforma arquivos DSL em um **SPA independente** com Alpine.js.  
O frontend gerado pode ser hospedado em qualquer lugar (CDN, Netlify, Vercel, S3, etc.) e se comunica com o Laravel apenas via **API REST** usando **Bearer Token**.

- Faça tudo em ingles, seguindo os padrões recomendados pelo PHP >= 8.3, Laravel >= 11 e PHPStan versão 3 no level 5, Pest versão 4, Alpine.js.
- Separe as responsabilidades de forma bem estruturada, seguindo o padrão de Services dentro da pasta "src/Services".
- Os helpers ficam na pasta "src/Support/Helpers/helper.php".
- Sempre atento ao detalhe para perceber se algum erro foi causado.
- Altere o composer para para refletir as dependências do projeto e a descrição e namespace corretos.

---

## 2. Estrutura do projeto
```
resources/lara-ink/
  pages/        # páginas DSL
  layouts/      # layouts (com suporte a subpastas)
  assets/       # JS e CSS base
public/build/   # saída compilada (app-<id>.css, app-<id>.js)
public/pages/   # páginas compiladas
public/index.html # ponto de entrada SPA
config/lara-ink.php # arquivo de configuração publicado pelo pacote
```

---

## 3. Sintaxe DSL
```php
<?php

ink_make()
    ->cache(600) // cache for 600 seconds
    ->layout('dashboard/app')
    ->title('User Dashboard')
    ->auth(true); // requires any valid login

<<<HTML
<h1>{{ trans('app.title') }}</h1>
HTML;

<<<JS
let message = @getWelcomeMessage();
JS;

<<<CSS
h1 { color: $primaryColor; }
CSS;

$primaryColor = '#ff6600';
```

---

## 4. Navegação SPA
- O `index.html` contém o roteador SPA.  
- Links internos são interceptados e carregam páginas de `/pages/{slug}.html`.  
- Conteúdo injetado em `#lara-ink-root` e reativado com Alpine.js.  
- Pré-carregamento: ao passar o mouse sobre um link, a página é buscada e armazenada em cache se tiver cache ativo para ela
  - Cria cache ao entrar na página diretamente pela rota se tiver cache ativo para ela
  - rotina JS para excluir os caches antigos do navegador

---

## 5. ink_route() no PHP
### Assinatura
```php
ink_route(string $nameOrSlug, array $params = [], string $method = null)
```

### Regras

Tudo isso deve ser configurado no JS compilado, impedindo que o usuário final consiga modificar o html e ter acesso a rotas e a metodos que não devem ser acessadas.

1. **Primeiro parâmetro** → slug de página DSL ou rota nomeada Laravel.  
2. **Segundo parâmetro** → parâmetros para placeholders.  
3. **Terceiro parâmetro (method)** → define método HTTP.  
   - Se informado → prioridade absoluta.  
   - Se não informado e dentro de `<form>`:  
     - Se existir `@method('X')` → usa esse valor (sobrescreve `method` do form).  
     - Senão, usa `method="..."` do form.  
     - Se nada definido → `GET`.  
   - Fora de `<form>` → usa o que foi informado pelo desenvolvedor por padrão ou  `GET` se nada for informado.

### Saída
Retorna objeto `{ url, method }`.  
- Em `<a>` → usa apenas `url`.  
- Em `<form>` → injeta `url` e `method`.
- Em JS → objeto completo para `lara_ink.newReq()`.
- A validação é sempre feita no JS gerado pelo compilador, nunca pegar o que está escrito nas tags html.

---

## 6. Rotas dinâmicas via nome de arquivo

O JS gerado pelo compilador deve ter essas rotas registradas pra entender o parametro e exibir a página correta.

- Arquivo: `produto/[slug].[id].php`  
- URL: `/produto/camiseta/42`  
- Parâmetros disponíveis:  
  - `request()->string('slug')` → `"camiseta"`  
  - `request()->int('id')` → `42`  

---

## 7. Query strings
- URL: `/produto/42?color=red&size=M`  
- `request()->input('color')` → `"red"`  
- `request()->string('size')` → `"M"`  

---

## 8. Objeto request() no frontend
Gerado automaticamente no `x-data` do Alpine.JS, com suporte a parâmetros e query strings.  

---

## 9. Formulários via AJAX
- Validação automática com base em atributos informados no HTML do php, que depois será compilado no JS (`required`, `accept`, `type`, etc.).  
- Envio via `fetch` com `FormData`.  
- Todas as requisições usam **Bearer Token**.  

---

## 10. Layouts (com subpastas)
- Definidos em `resources/lara-ink/layouts/`.  
- Podem estar em subpastas.  
- Usam `{{ $slot }}` para injetar conteúdo da página.  

---

## 11. ink_get_css() e ink_get_js()
- Substituídos automaticamente por assets com **cache busting**.  
- Manifesto salvo em `public/build/manifest.json`.  

---

## 12. Estruturas condicionais
- `<?php if ($cond): ?>` → `<template x-if="cond">`
- `<?php @if ($cond) ... @elseif ($cond2) ... @else ... @endif ?>` → `<template x-if="cond">`
- `<?php foreach ($items as $item): ?>` → `<template x-for="item in items">`  
- `<?php @foreach ($items as $item) ... @endforeach ?>` → `<template x-for="item in items">`  

---

## 13. Comando Artisan
```bash
php artisan lara-ink:build
```
- Lê DSL  na pasta correta em forma recursiva
- Valida páginas e rotas  
- Gera HTML, JS, CSS com cache busting  
- Salva em `public/pages/` e `public/build/`  
- Gera `index.html` com roteador SPA  
- Executa **Vite 6+** para empacotar assets and get hot reload working

---

## 14. Configuração global (`config/lara-ink.php`)
```php
<?php

declare(strict_types=1);

return [
    'api_base_url' => env('LARAINK_API_URL', null),

    'default_layout' => 'app', // the "app.php" file inside "resources/lara-ink/layouts/"

    'output' => [
        'dir' => 'public', // your-project-root-dir/public
        'pages_dir' => 'public/pages', // your-project-root-dir/public/pages
        'build_dir' => 'public/build', // your-project-root-dir/public/build
    ],

    'cache' => [
        'enable' => true,
        'ttl' => 300,
    ],

    'auth' => [
        'route' => [
            'prefix' => '/api/ink',
            // Send user to this routes
            'login' => '/login',
            'unauthorized' => '/unauthorized',
            'authorize_api' => '/authorize',
        ],
        'token_ttl' => 900, // Token expiration time in seconds
    ],
];

```

---

## 15. Segurança
- `ink_route()` cancela build se rota/página não existir.  
- Uploads e formulários usam **Bearer Token**.  
- `request()` retorna `null` se parâmetro não existir.  

---

## 16. Autenticação via Bearer Token
- Todas as requisições `fetch` incluem:  
```js
headers: {
  'Authorization': `Bearer ${window.lara_ink.token || ''}`,
  'Accept': 'application/json'
}
```

---

## 17. API fluente (`ink_make()`) no PHP
```php
ink_make()
    ->cache(600)
    ->layout('admin/panel')
    ->title(__('app.admin_panel'))
    ->seo([
        'description' => __('app.admin_panel'),
        'keywords' => 'admin, panel',
        // ...
    ])
    ->auth(true)
    ->middleware(['verified', 'role:admin']);
```

---

## 18. Controle de acesso e Middleware
- **auth(true)** → exige login válido (qualquer usuário autenticado).  
- **middleware('...')** → aceita string ou array de middlewares.
  - String: `->middleware('role:admin')`
  - Array: `->middleware(['auth', 'verified', 'role:admin'])`
- Os middlewares são **automaticamente registrados nas rotas do Laravel** durante o build.
- A stack de middleware é: `['web', 'auth:sanctum', ...custom_middlewares]`
- Middlewares são aplicados no servidor (Laravel) e informações são passadas ao frontend (JavaScript).  

---

## 19. Rotas de API (instalação do pacote)
O pacote registra automaticamente as rotas necessárias para autenticação e autorização, respeitando o prefixo configurado.  
- `/login`, `/logout`, `/is-authenticated`  

---

## 20. Tradução integrada
- O compilador coleta todas as chaves usadas em `trans()`, `trans()`, `trans_choice()`.  
- Gera `lara-ink-lang.js` com apenas as traduções necessárias.  
- Estrutura JSON por locale (`en_US`, `pt_BR`, etc.).  
- O SPA usa `lara_ink.set_locale('pt_BR')` para trocar idioma em tempo real.  

---

## 21. Objeto JavaScript `lara_ink`
Funções utilitárias centralizadas:  
- `lara_ink.set_locale(locale)`  
- `lara_ink.trans(key, replace)`  
- `lara_ink.newReq(url, options)` // Envia a request de acordo com o padrão recebido pelo compilador
- `lara_ink.is_authenticated()`  // Verifica se o token ainda é válido e se estiver faltando pouco tempo, usa a rota "is-authenticated" para validar e renova o token se estiver válido
- `lara_ink.logout()` Quando fizer logout, envia uma request para excluir o token do servidor.

---

## 22. Substituição de PHP → JS/AlpineJS
- `{{ $var }}` → `x-text="var"`  
- `if/foreach` → `x-if` / `x-for`  
- `trans('key')` → `lara_ink.trans('key')`  

---

## 23. Uso do Vite
- Vite 6+ como bundler oficial.  
- Configuração mínima em `vite.config.js`.  
- Gera assets com hash para cache busting.  
