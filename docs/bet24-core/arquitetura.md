# Arquitetura de temas e páginas — sistema `banca`

> Documento de contrato para a criação do tema **bet24** (`site11` / `app/modules/website11` + `app/views/website11`).
> Referência de implementação: tema ativo hoje **`site9`** (`app/modules/website9`, `app/views/website9`), que é o espelho a ser seguido.
> Todo o conteúdo foi levantado por leitura do código em `C:\wwwroot\banca` (nenhum arquivo de `app/`, `inc.config.php`, banco ou Apache foi alterado).

Índice:

1. [Resumo executivo (TL;DR)](#1-resumo-executivo-tldr)
2. [Stack e diretórios](#2-stack-e-diretórios)
3. [Ciclo de vida de uma requisição](#3-ciclo-de-vida-de-uma-requisição)
4. [Resolução de rotas (módulo / controller / action)](#4-resolução-de-rotas-módulo--controller--action)
5. [O contrato de um tema](#5-o-contrato-de-um-tema)
6. [Registro do tema (3 lugares + banco)](#6-registro-do-tema-3-lugares--banco)
7. [Twig no projeto](#7-twig-no-projeto)
8. [Variáveis globais disponíveis em todo template](#8-variáveis-globais-disponíveis-em-todo-template)
9. [Funções Twig disponíveis (`app/functions/twig.php`)](#9-funções-twig-disponíveis-appfunctionstwigphp)
10. [Objetos e helpers expostos aos templates](#10-objetos-e-helpers-expostos-aos-templates)
11. [JavaScript global injetado pelo layout (`seo_footer`)](#11-javascript-global-injetado-pelo-layout-seo_footer)
12. [Armadilhas estruturais (resumo)](#12-armadilhas-estruturais-resumo)
13. [Mapa de arquivos de referência do site9](#13-mapa-de-arquivos-de-referência-do-site9)

---

## 1. Resumo executivo (TL;DR)

Um tema no `banca` é a combinação de **três peças**:

| Peça | Caminho | Papel |
|---|---|---|
| Módulo PHP | `app/modules/websiteN/{SiteN.php, controllers/**}` | Registra o módulo no roteador; classes `SiteN` (bootstrap de view_vars) e controllers finos que só trocam o caminho do template |
| Views Twig | `app/views/websiteN/{layout.twig, page/*.twig, financeiro/*.twig}` | Markup; herdam de `layout` e preenchem blocos (`main`, `script`, `head`, e blocos renderizados por AJAX: `list`, `bilhete`, etc.) |
| Registro | `inc.config.php` (+ `use`) e `app/models/DadosModel.php::getTemas()` | Torna o tema visível/selecionável no admin e válido para `getTemaAtivo()` |

O tema ativo é escolhido pelo registro em `sis_dados.tema` (admin → Personalização → Temas). O `inc.config.php` cria um **alias**: a chave `site` do array `$config['modules']` recebe a configuração do tema ativo, e o roteador usa o módulo `site` como **módulo padrão** — por isso as URLs do site **não** têm prefixo de tema (`/apostar`, `/financeiro/deposito`, ...). As chaves `site1…site10` também existem e permitem acessar um tema explicitamente (`/site9/apostar`).

Fluxo resumido:

```
.htaccess (rewrite) → index.php → inc.config.php (config/DB/tema + APP::setConfig/setModules)
                   → APP::Initialize() → parseURI() → new SiteN() (view_vars) → Controller->actionAction()
                   → trait view::view() → Twig render → APP imprime string/JSON
```

Regra de ouro para o novo tema: **nada em `app/views/website9` é mágico**; o que existe são (a) controllers herdados do módulo base `app/modules/website` e (b) templates cujos caminhos são ou sobrescritos pela propriedade `$tpl`/`$view` do controller do tema, ou **hardcoded** no controller base (ver §12).

---

## 2. Stack e diretórios

- PHP 8.2 (Apache do BT panel), MySQL/MariaDB, site local em `http://banca.0um.online/`.
- Twig **2.4.8** (`vendor/twig/twig`, API legada `Twig_Environment` / `Twig_Loader_Filesystem` / `Twig_SimpleFunction`; as classes namespaced em `src/` são aliases). O autoload é `vendor/autoload.php`.
- Vue **2.5.16**, axios **0.18.0**, lodash **4.17.10**, jQuery **3.3.1**, Bootstrap **4.1.1**, sweetalert **2.1.0**, owl.carousel2 — via `node_modules/` (`package.json` na raiz).
- Arquivos públicos relevantes: `css/`, `js/site.js`, `cdn/js/{admin.js,mask.js,string.js,jquery.mask.js,jquery.form.js,jquery.serializeObject.js,...}`, `imagens/`, `node_modules/`.

Estrutura do `app/`:

```
app/
  APP.php                 # front controller / roteador (classe app\APP)
  boot.inc.php            # constantes (ABSPATH, IS_AJAX, IS_POST, IS_LOCAL...) + view_vars iniciais
  core/
    Controller.php        # abstract Controller (usa trait view)
    Model.php             # base dos models (lista, pdoRead, ...)
    ValueObject.php       # base dos VOs (get/set/toArray/imgCapa/save)
    crud/Conn.php         # conexão PDO
  functions/              # funções globais carregadas por autoload "files"
    core.php              # app_config, view_vars, pagination, json...
    twig.php              # funções Twig (retorna $functions)
    url.php               # url(), location(), url_parans(), source_images()...
    input.php             # inputPost/inputGet e decodificação de ajax_serialize
    user.php, form.php, ...
  helpers/                # Seo, Load, Number, Date, Pagination, TeamBadgeHelper, PixUp, VeoPag...
  models/                 # DadosModel, UsersModel, ApostasModel, JogosModel...
  modules/
    website/              # módulo base do site: Site.php + controllers "gordos"
    website2..website10/  # temas (SiteN + controllers finos)
    admin/ auth/ api/ cdn/ cron/ localizacoes/ notificacoes/
  traits/core/view.php    # renderização Twig usada por qualquer Controller
  views/
    website/              # views do tema base (contém includes compartilhados)
    website2..website10/  # views dos temas
    admin/ auth/ email/ ...
vendor/, node_modules/, cdn/, css/, js/, imagens/
inc.config.php            # configuração + módulos + DB + tema (EDIÇÃO PERMITIDA SÓ PELO LEAD/BUILDERS)
```

---

## 3. Ciclo de vida de uma requisição

### 3.1 `.htaccess` → `index.php`

`C:\wwwroot\banca\.htaccess` (linhas 14-23):

```apache
RewriteEngine on
# Rewrites do cassino Pragmatic...
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L]
```

Ou seja: tudo que não for arquivo/pasta existente cai em `index.php`. Arquivos estáticos (`css/`, `js/`, `node_modules/`, `imagens/`) são servidos direto pelo Apache.

`index.php` (6 linhas):

```php
include 'inc.config.php';
\app\APP::Initialize();
```

### 3.2 `inc.config.php`

Responsabilidades (ordem real do arquivo):

1. `include_once vendor/autoload.php` e `include_once app/boot.inc.php` (linhas 71-72).
2. Diretórios de cache/sessão em `_temp/cache` e `_temp/session` (linhas 84-94, `Cache::setDirectory`, `Session::setDirectory`).
3. Detecção da URL base (`$SITE_URL`) quando vazio (linhas 96-105) — vira `app_config('uri')`/`base_url()`.
4. Monta `$config['config']` com `title`, `dominio`, `email`, `uri`, `api`, `redes`, `upload` (linhas 107-124).
5. `$config['modules']` (linhas 126-145): `site => null` + `site1..site10` + módulos `entrar`, `cron`, `localizacao`, `admin`, `cdn`, `notificacoes`, `api`.
   - Cada tema é `['path' => 'app\\modules\\websiteN', 'class' => SiteN::class]`.
6. `$config['modules']['site'] = $config['modules'][$SITE_LAYOUT] ?? site1` (linha 148) — `$SITE_LAYOUT` é o **fallback** quando o banco não tem tema.
7. `Conn::setConfig($dbConfig)` (linha 174).
8. **Tema salvo no banco** (linhas 176-186):

```php
try {
    if ($tema = \app\models\DadosModel::getTemaAtivo()) {
        if (isset($config['modules'][$tema])) {
            $config['modules']['site'] = $config['modules'][$tema];
        }
    }
} catch (\Throwable $e) { /* mantém $SITE_LAYOUT */ }
```

9. `APP::setModules($config['modules']); APP::setConfig($config['config']);` (linhas 188-189).

> **Consequência**: `getDefaultModule()` é sempre `site` (primeira chave de `$config['modules']`), e o módulo `site` aponta para o tema ativo. As chaves `siteN` continuam acessíveis para URLs explícitas.

### 3.3 `APP::Initialize()` (`app/APP.php:119-229`)

1. Usa `APP_CONFIG['modules']` (`setModules`).
2. `parseURI()` → `$parse` (module/controller/action/parans).
3. Copia GET (`?a=b`) para `$_GET` e para `$parans` (linhas 158-166).
4. Instancia a classe do módulo, se houver (`new $class`, linhas 174-177) — é o construtor do `SiteN` que define `view_vars`.
5. `setController($Controller)` (linhas 179, 422-430): monta o FQCN `{path}\controllers\{controller}Controller` e chama `instance()` (singleton por classe).
6. Executa `"{$Action}Action"` se existir; senão `notfoundAction()` (linhas 182-186).
7. Saída conforme o tipo do retorno (linhas 198-228):
   - **array/objeto**: `Content-Type: application/json`, `echo json_encode(...)` — é assim que `apostar/jogos`, `apostar/apostar`, `perfil/salvar`, `financeiro/deposito/pix`, etc. respondem;
   - **string**: `text/html` + `echo` (páginas, e também `apostar/date`);
   - **null** (ex.: action que chama `location()`): nada é impresso, o redirect já ocorreu;
   - `Exception` capturada vira `['message' => ..., 'result' => code]`.

`Controller::instance()` (singleton) e `Controller::notfoundAction()` estão em `app/core/Controller.php:17-42` — ver a armadilha do `result => 1` no §12.

---

## 4. Resolução de rotas (módulo / controller / action)

Implementação: `APP::parseURI()` em `app/APP.php:245-331`.

Passos:

1. `$URI = REQUEST_URI` sem o diretório do script; se vazio → `'index/index'` (linha 252).
2. Remove query string; extrai extensão (`.html` default) (linhas 255-262).
3. `explode('/')` nos segmentos.
4. **Módulo**: se `$values[0]` for chave de `$config['modules']`, esse é o módulo e o segmento é consumido; senão usa o módulo padrão (`site`) (linhas 274-280).
5. **Controller** (linhas 287-304):
   - tenta `values[0]\values[1]` (subpasta, ex.: `financeiro/deposito` → classe `...\controllers\financeiro\depositoController`);
   - tenta `values[0]\index` (ex.: `financeiro`);
   - tenta `values[0]`;
   - senão `index`.
   - `controllerExists()` usa `strtolower($controller)` no `class_exists` (`APP.php:380-393`), por isso a URL é case-insensitive para o controller.
6. **Action** (linhas 308-314): se o próximo segmento tiver método `{segmento}Action` no controller → é a action; senão `index`.
   - Nomes de action podem ter camelCase na URL (ex.: `/apostar/imprimirTabela`), porque `method_exists` é case-insensitive em PHP.
7. Segmentos restantes viram `$parans` (numéricos por posição e pares nome/valor) — lidos por `url_parans($i)` (`app/functions/url.php:159-167`).

Exemplos reais:

| URL | módulo | controller (FQCN) | action | parans |
|---|---|---|---|---|
| `/` | `site` | `...\website9\controllers\indexController` | `index` | — |
| `/apostar` | `site` | `...\apostarController` | `index` | — |
| `/apostar/jogos` | `site` | `...\apostarController` | `jogos` | — |
| `/financeiro/deposito` | `site` | `...\financeiro\depositoController` | `index` | — |
| `/financeiro/deposito/pix` | `site` | `...\financeiro\depositoController` | `pix` | — |
| `/apostas/bilhete/{token}` | `site` | `...\apostasController` | `bilhete` | `[0 => token]` |
| `/site9/apostar` | `site9` | `...\website9\controllers\apostarController` | `index` | — |
| `/entrar/login/insert` | `entrar` | `...\auth\controllers\loginController` | `insert` | — |
| `/localizacao/cidades` | `localizacao` | `...\localizacao\controllers\cidadesController` | `index` | — |
| `/twig` | `site` | `...\indexController` (do tema) | `twig` | — |

**Fallbacks silenciosos** (importantes):

- Controller inexistente no módulo do tema → cai em `indexController::indexAction`, que (no tema) faz `location(url('apostar'))`. Resultado: a URL “desaparece” e redireciona para a home — sem erro visível.
- Action inexistente → `notfoundAction()`:
  - se `IS_AJAX` (header `X-Requested-With: XMLHttpRequest`) responde JSON `{"message":"Ação inválida.","html":"Página não encontrada.","result":1}`;
  - se não for AJAX, imprime o texto `Página inexistente`.
  - ⚠️ **`result => 1` faz o `$.adminPage` tratar como SUCESSO** (ver §12, armadilha 3).

---

## 5. O contrato de um tema

### 5.1 Classe `SiteN` (bootstrap de variáveis de view)

Base: `app/modules/website/Site.php` (29 linhas):

```php
class Site
{
    public function __construct()
    {
        $dados = DadosModel::get();
        Seo::setTitle($dados->getBanca());

        view_vars('responsive', true);
        view_vars('dados', $dados);
        view_vars('layout', 'website/layout.twig');
        view_vars('user', Admin::getLogged());
        view_vars('isMaster', Admin::isMaster());
        view_vars('isDev', Admin::isDeveloper());
        view_vars('controller', \app\APP::getControllerName());
    }
}
```

Tema (exemplo `app/modules/website9/Site9.php`):

```php
namespace app\modules\website9;

use app\modules\website\Site;

class Site9 extends Site
{
    public function __construct()
    {
        parent::__construct();
        view_vars('layout', 'website9/layout.twig');   // único override obrigatório
    }
}
```

Todos os `SiteN` (2..10) são idênticos: `extends Site`, `parent::__construct()`, trocam `layout`.
→ **Para o `site11`:** copiar `Site9.php` como `Site11.php`, mudar namespace/classe e o caminho do layout para `website11/layout.twig`.

### 5.2 Controllers finos: `$tpl` × `$view`

Os controllers “gordos” ficam no módulo base `app/modules/website/controllers/*` e usam uma **propriedade de instância** para o caminho do template. O tema só herda e sobrescreve a propriedade:

| Controller base | Propriedade lida pelo base | Exemplo do tema (site9) |
|---|---|---|
| `apostarController` | `protected $tpl` (`website/controllers/apostarController.php:28`) | `protected $tpl = 'website9/page/apostar';` |
| `cassinoController` | `protected $tpl` (`website/controllers/cassinoController.php:13`) | `protected $tpl = 'website9/page/cassino';` |
| `apostasController` | `protected $view` (`:17`) | `protected $view = 'website9/page/apostas';` |
| `bilheteController` | `protected $view` (`:15`) | idem |
| `cadastrarController` | `protected $view` (`:14`) | idem |
| `comunicadosController` | `protected $view` (`:12`) | idem |
| `financeiro/depositoController` | `protected $view` (`:18`) | idem |
| `financeiro/extratoController` | `protected $view` (`:13`) | idem |
| `financeiro/saqueController` | `protected $view` (`:13`) | idem |
| `financeiro/transferenciaController` | `protected $view` (`:17`) | idem |
| `financeiro/caixaController` | **`protected $tpl`** (`website/controllers/financeiro/caixaController.php:19-20` e `:35`) | ⚠️ site9/site10 declararam `$view` — ver §12 |

Controllers que o tema precisa **definir por completo** (o roteador resolve as classes no namespace do **módulo do tema**; não há herança automática entre módulos):

- `indexController` — existe no módulo base, mas o tema precisa da própria classe (o site9 copia e troca os caminhos de template); sem ela, `/`, `/logout`, `/twig` e `/cotacoes` caem no fallback;
- `perfilController` — **não existe** no módulo base; existe nos temas `website2`, `website4..website10` (não em site3). Sem ele, `/perfil` redireciona para a home.

Os `financeiro/*` existem no base e o tema só troca o template; os demais (apostar, apostas, bilhete, cadastrar, cassino, comunicados) seguem o padrão `extends` + propriedade.

Todos os controllers do tema têm o mesmo formato:

```php
namespace app\modules\website9\controllers;

class apostarController extends \app\modules\website\controllers\apostarController
{
    protected $tpl = 'website9/page/apostar';
}
```

Prefira **copiar** os controllers do site9 e trocar `website9` → `website11` (assim como o `Site11`), em vez de reinventar.

### 5.3 Views: layout + pages + includes

Convenção (site9):

```
app/views/website9/
  layout.twig                 # HTML completo (head/body/footer/scripts). Define blocos head/main/script.
  page/apostar.twig           # {% extends layout %} + {% block main %}...{% block script %}
  page/apostas.twig, bilhete.twig, cadastrar.twig, perfil.twig, comunicados.twig,
  page/cotacoes.twig, texto.twig, cassino.twig, cassino-player.twig (página standalone, sem layout)
  financeiro/deposito.twig, saque.twig, extrato.twig, transferencia.twig, caixa.twig
```

O site9 **não tem** pastas `inc/` nem `modals/`; ele inclui arquivos do tema base:

- `{% include 'website/inc/personalizacao-css.twig' %}` (layout do site9, linha 14);
- `{% include 'website/inc/modal-deposito-pix.twig' %}` (`website9/financeiro/deposito.twig:102`);
- `{% include 'admin/inc/modal-saque.twig' %}` (`website9/financeiro/saque.twig:61`).

O tema base `website/` tem `inc/header.twig`, `inc/slideshow.twig`, `inc/modal-deposito-pix.twig`, `inc/personalizacao-css.twig`, `modals/login.twig`, `inc/footer.twig` (arquivo vazio e não referenciado — morto).

### 5.4 Contratos que cada template de página deve cumprir

1. **`{% extends layout %}`** — `layout` é uma **variável** injetada por `view_vars` (não um nome literal).
2. Bloco principal: `{% block main %}` (o layout renderiza em `<main id="main">` do site9).
3. Scripts da página: `{% block script %}` (o layout imprime `<script>`/`{% block script %}` no fim do `<body>`).
4. Opcional: `{% block head %}` (CSS/estilos da página) — o site9 usa em `cassino.twig`.
5. Páginas que carregam listas via AJAX (`$.adminPage`) usam **dois** blocos:
   - `{% block main %}` = formulário de filtro + `<div class="admpage-container"></div>`;
   - `{% block list %}` = HTML da tabela (renderizado sozinho pelo controller via `renderBlock('list')`).
6. `bilhete`/`apostar` usam blocos extras: `bilhete` (`website/page/bilhete.twig`), `jogos`/`modalCotacoes`/`bilhete` (`website/page/apostar.twig` → ver apostar-vue.md).
7. Toda página do apostar precisa: `#website` (elemento raiz do Vue), containers com `v-cloak`, e **`{% set selector = '#website' %}`** antes de reutilizar o bloco `script` do template base.

---

## 6. Registro do tema (3 lugares + banco)

Para o tema existir de fato, é preciso tocar **exatamente** estes pontos:

### 6.1 `inc.config.php` — `use` + `$config['modules']`

Hoje (linhas 20-21): `use app\modules\website9\Site9; use app\modules\website10\Site10;`

Adicionar:

```php
use app\modules\website11\Site11;
```

E, dentro de `$config['modules']` (depois de `site10`):

```php
'site11' => ['path' => 'app\\modules\\website11', 'class' => Site11::class],
```

> `$config['modules']['site']` é reatribuído automaticamente (linha 148) e, se o banco tiver `tema = site11`, apontará para o novo módulo.

### 6.2 `app/models/DadosModel.php::getTemas()`

Hoje (linhas 44-58) retorna `['site1' => '...', ..., 'site10' => 'DP Sports (Azul Royal & Marinho)']`.
Adicionar:

```php
'site11' => 'Bet24 (bet24)',
```

Esse array é usado em **dois** lugares críticos:

- `getTemaAtivo()` (linhas 64-72): se o valor gravado em `sis_dados.tema` **não** estiver nas chaves de `getTemas()`, a função retorna `'site1'` — o site inteiro cai no tema base **sem erro**;
- `app/views/admin/configuracoes/personalizacao.twig:17` — `<select>` de temas do admin (`for chave, titulo in temas`).

### 6.3 Admin (personalização)

- Controller: `app/modules/admin/controllers/config/personalizacaoController.php`
  - `indexAction` passa `'temas' => DadosModel::getTemas()` (linha 24);
  - `insertAction` valida `tema` contra `array_keys(DadosModel::getTemas())` (linhas 47-52) e grava em `sis_dados` (linha 87-88 `$config->save($post)`).
- View: `app/views/admin/configuracoes/personalizacao.twig` (select de tema + campos de personalização).
- Nada precisa ser criado no admin para o tema aparecer: basta existir em `getTemas()`.

### 6.4 Banco

O tema ativo é o campo `tema` do registro de configuração em `sis_dados` (tabela única; `DadosModel::get()` pega o último registro por id).
Não é preciso migração: o valor é texto. **Não** altere o banco durante a construção; a ativação é um passo separado (admin → Personalização → Tema → Salvar; ou `UPDATE sis_dados SET tema='site11' WHERE id=<id>` fora do horário de uso).

### 6.5 `$SITE_LAYOUT` (fallback)

`inc.config.php:58` hoje `$SITE_LAYOUT = 'site1';` com comentário listando os layouts.
Pode ser atualizado para documentar o site11 (cosmético), mas **o banco tem prioridade**. Se o banco não tiver registro/valor válido, o site cai em `site1`.

---

## 7. Twig no projeto

### 7.1 Carregamento e renderização (trait `view`)

`app/traits/core/view.php` (usado por todo `Controller` via `use view;` em `app/core/Controller.php:11`):

```php
protected function view(string $view, array $data = null, string $block = null, bool $return = false)
{
    $this->load();
    $template = $this->twig->load("{$view}.twig");

    $data = (array)$data + view_vars();     // variáveis do controller têm PRIORIDADE
    $data['_post'] = inputPost();
    $data['_get']  = inputGet();

    if ($block) {
        return $return ? $template->renderBlock($block, $data)
                       : $template->displayBlock($block, $data);
    }
    return $return ? $template->render($data) : $template->display($data);
}
```

- `load()` → `twig()` + `functions()` (linhas 54-58).
- `twig()` (linhas 63-80): `new Twig_Loader_Filesystem(ABSPATH . '/app/views')` — **todos os caminhos de template são relativos a `app/views/`**, e `layout`/`include`/`extends` podem apontar para qualquer tema (é assim que o site9 inclui `website/...`).
- `functions()` (linhas 85-91): `Load::file('/app/functions/twig.php')` (retorna `$functions`) e `addFunction()` em cada uma.

Consequências práticas:

- **Nomes de template não têm `.twig`** na chamada do controller (`view('website9/page/apostar')`), mas **têm** em `{% extends %}`/`{% include %}` (`'website9/layout.twig'`).
- `view($template, $data, 'nomeDoBloco')` renderiza só um bloco — usado pelo `$.adminPage` para recarregar listas (bloco `list`/`bilhete`).
- `$data + view_vars()`: as chaves do controller **sobrescrevem** as globais (ex.: `perfilController` passa `user` de novo; `apostarController` passa `pageAposta`).

### 7.2 Herança e blocos

No site9, `layout.twig` define:

| Bloco | Linha | Onde é renderizado |
|---|---|---|
| `head` | `website9/layout.twig:11` | dentro do `<head>`, após os CSS fixos |
| `main` | `website9/layout.twig:144` | dentro de `<main id="main">` |
| `script` | `website9/layout.twig:311` | fim do `<body>`, após JS globais e antes de `dados.getHtmlFooter()` |

O tema base (`website/layout.twig`) define apenas `main` (linha 28) e `script` (72).

Blocos renderizados por AJAX (renderBlock), definidos **na própria página** (não no layout):

| Template | Bloco | Usado por |
|---|---|---|
| `website9/page/apostas.twig` | `list` | `apostasController::listAction` |
| `website9/page/bilhete.twig` | `bilhete` | `bilheteController::listAction` |
| `website9/financeiro/deposito.twig` | `list` | `depositoController::listAction` |
| `website9/financeiro/saque.twig` | `list` | `saqueController::listAction` |
| `website9/financeiro/extrato.twig` | `list` | `extratoController::listAction` |
| `website9/financeiro/caixa.twig` | `list` | `caixaController::listAction` |

**Blocos “cruzados” (outro template)**: `app/views/website9/page/apostar.twig:682` usa

```twig
{% block script %}
    {% set selector = '#website' %}
    {{ block('script', 'website/page/apostar.twig') }}
    ... JS do carrossel ...
{% endblock %}
```

O Twig 2.4.8 implementa `block(name, template)` como `Twig_Node_Expression_BlockReference` (`vendor/twig/twig/lib/Twig/Node/Expression/BlockReference.php`; parse em `lib/Twig/ExpressionParser.php:345`) — ou seja, **uma página pode renderizar o bloco de outra página**, compartilhando o app Vue sem duplicar 400 linhas. Isso é usado também no próprio base: `{{ block('jogos') }}` (`website/page/apostar.twig:326`), `{{ block('bilhete') }}` (`:331`), `{{ block('modalCotacoes') }}` (`:182`).
No site9: `{{ block('cupom') }}` (linhas 505 e 521), `{{ block('modalCotacoes') }}` (526).

> ATENÇÃO: `{{ block('x') }}` sem segundo argumento só funciona se o bloco `x` existir **no próprio template ou no template pai** (`layout`). Blocos definidos em outro arquivo exigem `block('x', 'caminho/arquivo.twig')`.

### 7.3 Cache: o que realmente acontece

No trait `view::twig()` (linhas 63-80):

```php
if (true or IS_LOCAL) {
    $config = ['debug' => true];            // observação: `true or ...` é SEMPRE true
} else {
    $config = ['cache' => $this->twigCachePath = ABSPATH . '/_temp/twig'];
}
```

- Como a condição é sempre verdadeira, o Twig roda em **modo debug, sem cache em disco**. `$this->twigCachePath` fica `null`.
- Portanto **editar qualquer `.twig` tem efeito imediato** (próximo request), não há cache de template para limpar.
- `const IS_LOCAL` é definido em `app/boot.inc.php:42` como `true` para `localhost`/`127.0.0.1`/CLI; com o site em `banca.0um.online` ele é `false` — irrelevante, pois o `true or` domina.
- O comando de view `viewCacheClean()` (linhas 97-126) apaga `_temp/twig` **somente se `twigCachePath` existir**; na prática retorna `total = 0` e não faz nada.
- `_temp/twig` não é criado na instalação atual (há `_temp/session` e `_temp/cache`). A rota **`/twig`** (action `twigAction` do `indexController` do tema) chama `viewCacheClean()` e faz `location(url_referer())` — é um no-op inofensivo; pode ser usada como “refresh” manual de template em produção caso alguém no futuro habilite cache.

### 7.4 Estrito/silencioso

O `Twig_Environment` é criado só com `debug => true`; **não** há `strict_variables`. Variável inexistente renderiza vazio (null). Isso explica “slots” em branco silenciosos (ex.: `{{ documento }}` no `website9/financeiro/saque.twig:29`, que não vem do controller) e é a razão para conferir nomes de variáveis com cuidado.

---

## 8. Variáveis globais disponíveis em todo template

### 8.1 Definidas pelo `Site` base (`app/modules/website/Site.php:19-25`)

| Variável | Tipo | Origem | Uso típico |
|---|---|---|---|
| `responsive` | bool (`true`) | `view_vars` | ativa o bloco `<html class="responsive">` e `<picture>` do slideshow do tema base |
| `dados` | `app\vo\DadosVO` | `DadosModel::get()` | configurações da banca/personalização — `dados.getBanca()`, `dados.getExibirCassino()`, `dados.imgCapa(true,'logo')`... |
| `layout` | string | `'website/layout.twig'` (tema troca para `website9/layout.twig`) | `{% extends layout %}` |
| `user` | `app\vo\UserVO` ou `null` | `Admin::getLogged()` | usuário logado — `user.getCredito(true)`, `user.getNome()` |
| `isMaster` | bool | `Admin::isMaster()` | admin/developer logado |
| `isDev` | bool | `Admin::isDeveloper()` | developer logado |
| `controller` | string | `APP::getControllerName()` | destaque de menu: `{% if controller == 'cassino' %}`; em controller aninhado vale `financeiro\deposito` (barra invertida) |

### 8.2 Definidas no boot (`app/boot.inc.php`)

| Variável | Tipo | Linha | Uso |
|---|---|---|---|
| `isAjax` | bool | 28 | request com `X-Requested-With: XMLHttpRequest` |
| `isPost` | bool | 38 | método POST |
| `isLocal` | bool | 43 | localhost/CLI |

### 8.3 Injetadas pelo trait `view` em **toda** renderização (`app/traits/core/view.php:33-34`)

| Variável | Tipo | Valor |
|---|---|---|
| `_post` | array | `inputPost()` (já decodificado de `ajax_serialize`, ver `app/functions/input.php:3-10`) |
| `_get` | array | `inputGet()` |

Uso típico: repopular formulário — `<input name="codigo" value="{{ _post.codigo }}">` (`website9/page/bilhete.twig:14`), `searchValues: {codigo: '{{ _post.codigo }}'}` (`:117`).

### 8.4 Variáveis de página passadas pelo controller (site9)

Cada página tem seu contrato próprio (tabela completa em `paginas.md`). Exemplo do apostar (`apostarController::indexAction`, `app/modules/website/controllers/apostarController.php:49-61`):

```php
$this->view($this->tpl, [
    'hoje' => date('Y-m-d'),
    'amanha' => date('Y-m-d', strtotime('+1day')),
    'cotacaoMaxima' => ..., 'cotacaoMinima' => ...,
    'apostaMinima' => ..., 'apostaMaxima' => ...,
    'retornoMaximo' => ..., 'condicaoCotacao' => $config->getCondicaoCotacao(true),
    'minJogos' => ..., 'premioRevenda' => $config->getRevendaPaga(),
    'pageAposta' => true,
]);
```

`pageAposta` só existe no apostar; nos outros templates `{% if pageAposta %}` resolve `null` (falso) — é o que controla o `v-cloak` do `#website` no layout (`website9/layout.twig:55`).

> Para completude: quando o módulo é `admin`, `Admin::__construct` (`app/modules/admin/Admin.php:54-63`) define `user`, `module`, `config`, `ativo`, `bloqueado`, `isDev`, `isMaster`, `isAdmin`, `isGerente` e `layout = 'admin/layout.twig'`. Essas variáveis **não** são definidas no site (módulo `site`) — no site só valem as da §8.1/§8.2.

---

## 9. Funções Twig disponíveis (`app/functions/twig.php`)

Todas registradas via `Twig_SimpleFunction`; o arquivo retorna `$functions` (`twig.php:212`) e é carregado por `Load::file`. Para criar funções novas, **edite apenas esse arquivo** (fora do escopo do tema; coordenar com o lead).

| Função | Assinatura | Retorno | Exemplo de uso | Linha |
|---|---|---|---|---|
| `base_url` | `base_url()` | string | `{{ base_url() }}/imagens/logo.png` | 3 |
| `domain` | `domain()` | **echo** (imprime) `HTTP_HOST` | `{{ domain() }}` | 7 |
| `seo_header` | `seo_header(bool $responsive = true)` | **echo** | `{{ seo_header() }}` no `<head>` | 11 |
| `url_referer` | `url_referer()` | **echo** (referer seguro ou home) | `location(url_referer())` no controller; `{{ url_referer() }}` | 15 |
| `dd` | `dd(...$args)` | **echo** `<pre>` | debug | 19 |
| `preg_replace` | `preg_replace($pattern,$replacement,$subject)` | string | `{{ preg_replace('/(.{3})/', '$1 ', aposta.getCodigoBilhete()) }}` | 23 |
| `json` | `json($data)` | **echo** JSON (`toJson(true)` para VO) | `.setValues({{ json(user) }})` (`website9/page/perfil.twig:85`) | 27 |
| `resumoJogos` | `resumoJogos()` | array (`total`, `hoje`, `3dias`) | contadores de jogos | 35 |
| `fncSinal` | `fncSinal($valor, $isReal = false)` | string (`+ R$ 1,00` / `- 1,00`) | `{{ fncSinal(v.getValor(), true) }}` | 39 |
| `config` | `config(string $key)` | `app_config($key)` | `{{ config('title') }}` | 46 |
| `url` | `url(string $controller = null, array $vars = null, $module = null, array $get = null)` | string | `{{ url('financeiro/deposito') }}`, `{{ url('apostas/bilhete', [v.getToken()]) }}`, `{{ url('cassino?category=POPOK') }}` | 50 |
| `banners` | `banners(string $ref, string $data = null, int $dia = null, int $limit = 5)` | `BannerVO[]` | `{% for b in banners('site-slideshow', null, null, 10) %}` | 54 |
| `destaquesJogosHome` | `destaquesJogosHome(int $limit = 6)` | array de jogos achatado (ver `JogosModel::getJogosDestaqueHome`) | hero do apostar | 58 |
| `destaquesCassinoHome` | `destaquesCassinoHome(int $limit = 6)` | array de jogos do cassino (vazio se `getExibirCassino()` = 0) | hero do apostar | 62 |
| `inputFile` | `inputFile($caption,$name,$accept=null,$class='btn-success',array $attr=null)` | **echo** botão de upload | `{{ inputFile('<i class="fa fa-upload"></i> Comprovante', 'upfile', 'pdf,jpg,jpeg,png') }}` | 82 |
| `pagina` | `pagina(string $ref, int $refId = 0, bool $autoCreate = true)` | `PaginaVO` | `{{ pagina('transferencia').getTexto()\|raw }}` | 86 |
| `paginas` | `paginas(string $ref, int $refId = 0)` | lista | listas de páginas institucionais | 90 |
| `seo_footer` | `seo_footer()` | **echo** (JS globais + CSS/JS de `Seo`) | `{{ seo_footer() }}` no fim do body | 94 |
| `resumoBancario` | `resumoBancario(UserVO $user = null)` | array | resumo de créditos | 98 |
| `difDays` | `difDays(string $dt1, string $dt2)` | int | diferença de dias | 102 |
| `comunicados` | `comunicados()` | `ComunicadoVO[]` | `{% set comunicados = comunicados() %}` (`website9/page/comunicados.twig:3`) | 108 |
| `admMenu` | `admMenu(string $module)` | **echo** (menu do admin) | só admin | 112 |
| `optMeses` | `optMeses()` | **echo** `<option>` 1..12 | filtros por mês | 169 |
| `optBancos` | `optBancos()` | **echo** `<option>` bancos | modal de saque | 175 |
| `options` | `options(string $ref, bool $orderby = null)` | **echo** `<option>` de `sis_options` | selects genéricos | 179 |
| `optUsers` | `optUsers(int $type = null)` | **echo** `<option>` usuários | admin/relatórios | 183 |
| `optEstados` | `optEstados(string $label = 'title')` | **echo** `<option>` UFs | `website9/page/cadastrar.twig:68` | 187 |
| `user` | `user(string $method = null, bool $format = false)` | mixed (`UserVO` se sem method) | `{{ user.getNome() }}` é a forma mais usada (variável); `user('nome')` também existe | 191 |
| `dados` | `dados(string $method = null, bool $format = false)` | mixed (`DadosVO` se sem method) | `{{ dados.getBanca() }}` (variável) / `dados('banca')` | 203 |

Observações:

- Funções que fazem `echo` devem ser usadas como `{{ fn() }}` (o retorno é `null`); as que retornam objeto/array podem ser encadeadas (`dados.getBanca()`).
- `url()` (Twig) é a versão PHP de `app/functions/url.php:53-99`; em **JavaScript** existe outra função `url()` (ver §11) — não confundir.
- `banners(ref, data, dia, limit)`: `ref` é a referência da tabela `sis_banners` (ex.: `site-slideshow`); `data` vazio = hoje; `dia` vazio = dia da semana atual (0-6); `limit` default 5; ordenação `rand()` (`BannersModel::get`, `app/models/helpers/BannersModel.php:28-40`). Os banners usam imagens de referência `sis_banners-desktop` e `sis_banners-mobile`.

Filtros Twig nativos mais usados nos temas: `raw`, `date`, `number_format(2, ',', '.')`, `split`, `reverse`, `join`, `slice`, `length`, `default`, `capitalize`, `json_encode`, `e('html_attr')`, `merge`, `lower`, `upper`, `first`/`last`.
Testes: `defined`, `empty`, `iterable`, `same as`, `null`, `odd`, `even`, `constant`.
Tags: `extends`, `block`, `include`, `for`, `if`, `set`, `with` (`{% include 'x' with {ref: 'y'} %}` — `website/page/apostar.twig:312`).

---

## 10. Objetos e helpers expostos aos templates

### 10.1 `dados` — `app\vo\DadosVO` (tabela `sis_dados`)

Getters mais usados nos temas (todos chamáveis no Twig como `dados.getX()`):

| Getter | Retorno | Uso em template |
|---|---|---|
| `getBanca()` | string | título/logo textual |
| `getHtmlHead()`, `getHtmlFooter()` | string HTML (admin) | `{{ dados.getHtmlHead()\|raw }}` no `<head>`, footer |
| `getExibirRegistro()` | bool | mostrar botão Cadastre-se |
| `getExibirLogin()` | bool | mostrar botão Entrar/modal |
| `getExibirCassino()` | bool | menu e atalhos de cassino |
| `getExibirBilhete()` | bool | link “Consultar Bilhete” |
| `getExibirRegras()`, `getExibirCotacoes()`, `getExibirComunicados()`, `getExibirContato()`, `getExibirApp()` | bool | links de menu/rodapé |
| `getLinkCadastro()` | string | `dados.getLinkCadastro()\|default(url('cadastrar'))` |
| `getApp()` | string | link do app Android |
| `getTipoSlidesHome()`, `getTipoSlidesCassino()` | `mesclado`/`banners`/`jogos`/`futebol_banners`/`cassino_banners`/`desativado` | montagem do hero |
| `getLogoWidth()`, `getLogoHeight()`, `getSlideHeight()`, `getSlideHeightMobile()` | int | CSS vars de `personalizacao-css.twig` |
| `getMinutosJogo()` | int | margem de fechamento de apostas (`+5`) |
| `getApostaMinima()`, `getApostaMaxima()`, `getCotacaoMinima()`, `getCotacaoMaxima()`, `getRetornoMaximo()`, `getMinJogos()` | numérico | defaults do bilhete |
| `getCondicaoCotacao(true)` | array | regras de cotação mínima por faixa de valor |
| `getRevendaPaga()` | numérico | `premioRevenda` |
| `getModoDeposito()` | `todos`/`pix`/`manual` | botões de depósito |
| `getGatewaypix()` | `pixup`/`veopag` | nome do gateway PIX |
| `getDepositoMinimo()` | numérico | mínimo de depósito |
| `getTaxaTransferencia()` | numérico | taxa de transferência |
| `getTema()` | string | chave do tema gravada (usada por `getTemaAtivo`) |
| `getLinkSaque()`, `getLinkComprar()`, `getRegrasAposta()`, `getInformacoes()`, `getRegulamento()` | string | páginas institucionais |

`dados` é um `ValueObject`: além dos getters, `dados.get('banca')` (genérico) e `dados.imgCapa(...)` funcionam.

### 10.2 `user` — `app\vo\UserVO`

| Getters/chamadas usados nos temas |
|---|
| `getId()`, `getNome()`, `getLogin()`, `getCredito(true)` (formatado), `getEmail()`, `getTelefone()`, `getCpf()`, `getUser()`, `getType()` |
| `imgCapa()` (foto do usuário; retorna `ImageVO`) → `user.imgCapa().redimensiona(36,36)` no header base |
| `get('acertos')` (getter genérico) |
| `voGraduacao()` (usado nos controllers de aposta, não no Twig) |

Outros VOs usados em templates:

- `aposta` (`ApostaVO`): `getId()`, `getCodigoBilhete()`, `getToken()`, `getData(true)`, `getHora()`, `getValor(true)`, `getRetornoValido(true)`, `getJogos()`, `getApostadorNome()`, `getApostadorTelefone()`, `getStatus()` (1 ativa, 2 aguardando pagamento, 0 cancelada, 3 não paga, 99 excluída — `ApostasModel` constantes), `getStatusTitle()`, `getVerificado()`, `getGanhou()`, `get('acertos')`, `voJogos()`, `voUser()`.
- `busca` (`app\helpers\Pagination`): `getCount()`, `getRegistros()`, `getPageDescription()`, e `{{ busca }}` (o `__toString` imprime a paginação com `data-page` usada pelo `$.adminPage`).
- `v` de histórico (`HistoricoBancarioVO`): `getId()`, `getInsert()`, `getDescricao()`, `getValor()`, `getSaldoCreditos()`.
- `grupos` (array `CotacoesModel::GRUPOS`), `cotacoes` (`CotacaoVO[]`): `getGrupo()`, `getTitulo()`, `getSigla()`, `getDescricao()`, `getCampo()`.
- `pagina` (`PaginaVO`): `getTitle()`, `getTexto()`.
- `comunicados` (`ComunicadoVO[]`): `getTitle()`, `getData(true)`, `getMensagem()`.

### 10.3 Imagens: `imgCapa()` e `ImageVO`

`ValueObject::imgCapa($Default = true, $ADDReference = null)` (`app/core/ValueObject.php:282-285`) busca a imagem de capa da tabela `sis_imagens` com `ref = {tabela}` (+ `-{ADDReference}`) e `refid = id`.

Referências em uso:

| Chamada | ref no banco | Upload no admin |
|---|---|---|
| `dados.imgCapa(true, 'logo')` | `sis_dados-logo` | `dadosController` (`imgAddCapa('logo','logo',true)`) |
| `dados.imgCapa(true, 'background')` | `sis_dados-background` | idem |
| `banner.imgCapa(true, 'desktop'/'mobile')` | `sis_banners-desktop` / `sis_banners-mobile` | bannersController |
| `user.imgCapa()` | `sis_usuarios` | usersController/perfil |
| `v.imgCapa()` (jogos/campeonatos/times) | `sis_times`, `sis_campeonatos`, `sis_options` | painel |

`ImageVO` (`app/vo/helpers/ImageVO.php`) oferece:

- `getSource($host = false)` → nome do arquivo (`getSource()`) ou URL completa (`getSource(true)`); se `$host=true` e o arquivo não existir em `imagens/`, devolve `default.jpg` (`:37-43`);
- `redimensiona($w, $h, $tipo = 'crop', $quality = 100, $bg = '#FFFFFF')` → URL do CDN interno `/cdn/img/{w}/{h}/{tipo}/{q}/{source}` (`:28-31`).

Padrão de uso no Twig: `{% if dados.imgCapa(true,'logo').getSource() %}...{% endif %}` ou `src="{{ ...getSource(true) }}"`.

Escudos/bandeiras nos dados de jogos são resolvidos no PHP por `app\helpers\TeamBadgeHelper` (`getTeamBadge`, `getCountryFlag`) — os arrays de jogo já trazem `imgCasa`, `imgFora`, `paisImg` prontos (URL completa ou `null` quando `dados.getExibirEscudos()` desliga os escudos; bandeiras continuam).

### 10.4 Outras funções PHP globais úteis em templates

- `app_config($key)` — config do `inc.config.php` (`base_url()` etc.).
- `source_images($arquivo)` / `source_files($arquivo)` / `source($caminho)` — URLs de upload (`app/functions/url.php:193-216`).
- `url($controllerAction, $vars, $module, $get)` e `url_parans($i)`.
- `location($url = null)` — redirect + exit (`app/functions/url.php:9-14`).
- `inputPost()/inputGet()` — decodificam `ajax_serialize` (`app/functions/input.php:3-10`).
- `json($values, $result = 0)` — response JSON + exit (`app/functions/core.php:578-596`).
- `pagination()` — HTML de paginação (`app/functions/core.php:372-420`); `Date`, `Number::real()`, `Number::float()`.

---

## 11. JavaScript global injetado pelo layout (`seo_footer`)

Todo layout deve chamar `{{ seo_footer() }}` (site9: linha 280). Ele imprime (`app/helpers/Seo.php:339-353`):

```html
<script type='text/javascript' >
    var URL_APP = 'http://banca.0um.online';
    var URL_MODULE = 'http://banca.0um.online';
    var CONTROLLER = 'apostar';
    var ACTION = 'index';
    var MODULE = 'site';
    var MODULE_DEFAULT = 'site';
    var PAGE_VARS = '[]';
    const IS_LOCAL = 0;
</script>
```

E depois todos os CSS/JS registrados via `Seo::addCss/addJs` (hoje vazio nos temas).

**Função JS `url(ControllerAction, Variaveis, Module)`** — definida em `cdn/js/admin.js:200-227` (não é a Twig!):

```js
function url(ControllerAction, Variaveis, Module) {
    var url = URL_APP;
    if (Module === undefined) Module = MODULE;
    if (Module != MODULE_DEFAULT) url += '/' + Module;
    if (ControllerAction) url += '/' + ControllerAction;
    if ($.isArray(Variaveis)) $.each(Variaveis, function(i, v){ url += '/' + v; });
    return url.toString().replace(/\\/g, '/');
}
```

Exemplos reais: `url('apostar/jogos')` → `http://host/apostar/jogos`; `url('financeiro/deposito/pix')`; se a página foi aberta por `/site9/apostar`, `MODULE=site9` e a função prefixa `/site9`.

Outros helpers JS globais disponíveis: `$.adminPage`/`$.fn.adminPage` (form/lista AJAX — `cdn/js/admin.js:771+`), `getValues/setValues/resetForm`, `apply_masks` (`:309`, aplica `mask-valor`, `mask-cpf`, `mask-cnpj`, `mask-cep`, `mask-telefone`, `mask-data`...), `estadosCidades($estado,$cidade)` (`:248`, carrega cidades de `/localizacao/cidades`), `swal` (sweetalert), `String.prototype.toReal/toFloat/number_format` (`cdn/js/string.js`), `mask/mask2/mask_full` (`cdn/js/mask.js`), `$.fn.ajaxSubmit` (`cdn/js/jquery.form.js`), `Clipboard` (`cdn/js/clipboard.min.js`).

`$.fn.adminPage` (resumo do contrato — detalhes em `paginas.md`):

- POSTa sempre com `data: {ajax_serialize: JSON.stringify(values), dtCache: <timestamp>}`;
- envia para `url(config.controller, null, config.module) + '/' + (insert|update)Action` e `controller + '/' + selectAction` para buscas;
- padrões: `controller = CONTROLLER` (controller atual), `module = MODULE`, `insertAction='insert'`, `updateAction='update'`, `selectAction='list'`, `deleteAction='excluir'`;
- resposta esperada: JSON `{result: 1|0, message: '...'}`; nas buscas: HTML (ou JSON `{html: '...'}`) para o `container`;
- opções usadas nos temas: `formSearch`, `container`, `autoSearch`, `alertSuccess`, `reloadSuccess` (`1` → reload; string `http...` → redirect), `insertAction`, `controller`, `module`, `autoReset`, `searchValues`.

---

## 12. Armadilhas estruturais (resumo)

Detalhamento completo e correções propostas em `checklist-tema.md`. Resumo:

1. **Registre o tema nos DOIS lugares** (`inc.config.php` **e** `DadosModel::getTemas()`). Se faltar `getTemas()`, `getTemaAtivo()` devolve `site1` e o site cai no tema base sem erro.
2. **Controller inexistente no módulo do tema = redirect silencioso para `/apostar`** (o `indexController` do tema é o fallback). O tema precisa do pacote completo de controllers.
3. **`notfoundAction()` retorna `result => 1` em AJAX** — um `<form>` com `insertAction` errado recebe “sucesso” falso (caso real: `website9/page/perfil.twig` envia para `perfil/atualizar`, mas `perfilController` só tem `salvarAction`; o perfil não salva e o JS mostra sucesso).
4. **`$tpl` × `$view`**: `financeiro/caixaController` do base lê `$this->tpl`; site9/site10 declaram `protected $view` e por isso **não renderizam nada** nessa página. No site11 use `$tpl` no `caixaController`.
5. **Caminhos fixos em controllers base** (não seguem o tema):
   - `apostarController::imprimirTabelaAction` → `website/page/apostar-imprimir-tabela`;
   - `apostarController::imprimirAction` → `website/page/pre-bilhete`;
   - `apostasController::imprimirAction`/`bilheteAction` → `admin/apostas/pdf` e `admin/apostas/bilhete`;
   - `cassinoController::jogarAction` → `website9/page/cassino-player` (hardcoded no base!).
6. **Includes cruzados**: o site9 inclui `website/inc/personalizacao-css.twig`, `website/inc/modal-deposito-pix.twig` e `admin/inc/modal-saque.twig`. Se o site11 for “auto-contido”, copie esses includes e ajuste os caminhos.
7. **`{{ block('script', 'website/page/apostar.twig') }}`** é o mecanismo de reuso do app Vue; exige `{% set selector = '#website' %}` antes e um `#website` no layout.
8. **Twig sem `strict_variables`**: erros de nome de variável são silenciosos.
9. **Cache Twig desligado** (`true or IS_LOCAL`): não há `_temp/twig` para limpar; `/twig` é no-op.
10. **Controllers/`SiteN` são carregados por `new` no `Initialize`** — se o arquivo estiver no namespace errado, o `class_exists` do parseURI falha e a rota cai no fallback (de novo: redirect silencioso).

---

## 13. Mapa de arquivos de referência do site9

Para criar o site11, copie/adapte:

```
app/modules/website9/
  Site9.php                                   → Site11.php
  controllers/indexController.php             → idem (converter templates para website11)
  controllers/perfilController.php            → idem
  controllers/apostarController.php           → idem ($tpl)
  controllers/apostasController.php           → idem ($view)
  controllers/bilheteController.php           → idem ($view)
  controllers/cadastrarController.php         → idem ($view)
  controllers/cassinoController.php           → idem ($tpl)
  controllers/comunicadosController.php       → idem ($view)
  controllers/financeiro/caixaController.php          → idem ($tpl! ver §12.4)
  controllers/financeiro/depositoController.php       → idem ($view)
  controllers/financeiro/extratoController.php        → idem ($view)
  controllers/financeiro/saqueController.php          → idem ($view)
  controllers/financeiro/transferenciaController.php  → idem ($view)

app/views/website9/
  layout.twig
  page/apostar.twig            (reusa o app Vue de website/page/apostar.twig)
  page/apostas.twig
  page/bilhete.twig
  page/cadastrar.twig
  page/perfil.twig
  page/comunicados.twig
  page/cotacoes.twig
  page/texto.twig
  page/cassino.twig            (idêntico ao website/page/cassino.twig — 743 linhas)
  page/cassino-player.twig     (idêntico ao website/page/cassino-player.twig — 234 linhas)
  financeiro/deposito.twig
  financeiro/saque.twig
  financeiro/extrato.twig
  financeiro/transferencia.twig
  financeiro/caixa.twig
```

Compartilhados (lidos do tema base, não precisam ser copiados):

```
app/views/website/page/apostar.twig            # app Vue completo (992 linhas) — reusado via block()
app/views/website/page/apostar-imprimir-tabela.twig   # rota apostar/imprimirTabela
app/views/website/page/pre-bilhete.twig        # rota apostar/imprimir (pré-bilhete impresso)
app/views/website/inc/personalizacao-css.twig  # CSS vars do admin (logo/slides) + regras leon
app/views/website/inc/modal-deposito-pix.twig  # modal PIX + polling
app/views/website/inc/slideshow.twig           # carrossel Bootstrap de banners
app/views/website/modals/login.twig            # modal de login do tema base
app/views/admin/inc/modal-saque.twig           # formulário de saque (usado pelo site9)
app/views/admin/apostas/bilhete.twig, pdf.twig # bilhete/impressão (rotas apostas/*)
```

Próximos documentos:

- `paginas.md` — contrato página por página (controller, template, variáveis, endpoints, JS);
- `apostar-vue.md` — o app Vue do apostar em detalhe (data/computed/methods/DOM/endpoints);
- `checklist-tema.md` — passo a passo de criação do `site11` + armadilhas e correções.
