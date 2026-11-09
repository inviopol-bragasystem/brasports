# Checklist — criação do tema `site11` (bet24)

> Guia executável para criar o tema **bet24** no sistema `banca`, espelhando o `site9`.
> Referências: `arquitetura.md` (contrato de temas/Twig), `paginas.md` (página por página), `apostar-vue.md` (app Vue).
>
> **Quem faz o quê**: este checklist descreve as edições de código (`app/`, `inc.config.php`) que os **construtores/lead** executam. A análise que gerou estes docs foi somente leitura.
> **Não ativar o tema** até que a fase de teste do lead permita; a ativação é reversível e está no Passo 6.

---

## 0. As 3 armadilhas mais importantes (leia antes de começar)

1. **Registro em DOIS lugares** — `inc.config.php` **e** `app/models/DadosModel.php::getTemas()`. Se faltar o segundo, `DadosModel::getTemaAtivo()` invalida a escolha e devolve `site1`: o site inteiro abre no tema base **sem nenhum erro**. Se faltar o primeiro (`$config['modules']['site11']`), o `inc.config.php` não troca o módulo `site` e de novo cai no fallback.
2. **O módulo do tema precisa dos controllers no caminho/namespace corretos** — o roteador resolve o controller **dentro do módulo do tema**; controller ausente ⇒ `indexController::indexAction` ⇒ `location(url('apostar'))` (redirect silencioso). E os controllers herdam dos “gordos” do módulo base, que leem **`$tpl`** ou **`$view`** conforme o arquivo:
   - `apostarController`/`cassinoController`/`financeiro/caixaController` ⇒ **`protected $tpl`** (⚠️ `caixa` no site9/site10 usou `$view` e por isso a página não renderiza nada);
   - os demais (`apostas`, `bilhete`, `cadastrar`, `comunicados`, `financeiro/{deposito,extrato,saque,transferencia}`) ⇒ **`protected $view`**.
3. **Nem tudo no tema é template do tema** — há caminhos fixos nos controllers base e includes cruzados:
   - `apostar/imprimirTabela` → `website/page/apostar-imprimir-tabela.twig`; `apostar/imprimir` → `website/page/pre-bilhete.twig`;
   - `apostas/bilhete` → `admin/apostas/bilhete.twig`; `apostas/imprimir` → `admin/apostas/pdf.twig`;
   - `cassino/jogar` → **`website9/page/cassino-player.twig`** (hardcoded no base);
   - o site9 inclui de outros temas: `website/inc/personalizacao-css.twig`, `website/inc/modal-deposito-pix.twig`, `admin/inc/modal-saque.twig`.
   Além disso: **`Controller::notfoundAction()` responde `result => 1` em AJAX** ⇒ qualquer `<form>` com `insertAction` inexistente “parece salvar” (caso real: `perfil.twig` envia para `perfil/atualizar`, mas só existe `salvarAction`).

---

## 1. Inventário completo de arquivos a criar

### 1.1 PHP — `app/modules/website11/` (14 arquivos)

| # | Arquivo | Base/origem | Propriedade obrigatória |
|---|---|---|---|
| 1 | `Site11.php` | copiar `website9/Site9.php` | `view_vars('layout', 'website11/layout.twig')` |
| 2 | `controllers/indexController.php` | copiar do site9 e trocar `website9` → `website11` | ações `index`, `logout`, `cotacoes`, `twig` |
| 3 | `controllers/perfilController.php` | copiar do site9 e trocar paths | `indexAction`, `salvarAction` (+ corrigir o template, §5.5) |
| 4 | `controllers/apostarController.php` | copiar do site9 | `protected $tpl = 'website11/page/apostar';` |
| 5 | `controllers/apostasController.php` | copiar do site9 | `protected $view = 'website11/page/apostas';` |
| 6 | `controllers/bilheteController.php` | idem | `protected $view = 'website11/page/bilhete';` |
| 7 | `controllers/cadastrarController.php` | idem | `protected $view = 'website11/page/cadastrar';` |
| 8 | `controllers/cassinoController.php` | idem | `protected $tpl = 'website11/page/cassino';` |
| 9 | `controllers/comunicadosController.php` | idem | `protected $view = 'website11/page/comunicados';` |
| 10 | `controllers/financeiro/caixaController.php` | idem | **`protected $tpl = 'website11/financeiro/caixa';`** (não `$view`!) |
| 11 | `controllers/financeiro/depositoController.php` | idem | `protected $view = 'website11/financeiro/deposito';` |
| 12 | `controllers/financeiro/extratoController.php` | idem | `protected $view = 'website11/financeiro/extrato';` |
| 13 | `controllers/financeiro/saqueController.php` | idem | `protected $view = 'website11/financeiro/saque';` |
| 14 | `controllers/financeiro/transferenciaController.php` | idem | `protected $view = 'website11/financeiro/transferencia';` |

### 1.2 Twig — `app/views/website11/` (16 arquivos + includes opcionais)

| # | Arquivo | Obrigatório? | Observação |
|---|---|---|---|
| 1 | `layout.twig` | **sim** | blocos `head`, `main`, `script`; `#website` com `{% if pageAposta %}v-cloak{% endif %}`; `seo_header()/seo_footer()`; CSS/JS globais |
| 2 | `page/apostar.twig` | **sim** | bloco `main` (markup) + `script` (reusa o app Vue do base) |
| 3 | `page/apostas.twig` | **sim** | blocos `main`, `list`, `script` |
| 4 | `page/bilhete.twig` | **sim** | blocos `main`, `bilhete`, `script` |
| 5 | `page/cadastrar.twig` | **sim** | `main`, `script` |
| 6 | `page/perfil.twig` | **sim** | `main`, `script` (corrigir para `salvar`) |
| 7 | `page/comunicados.twig` | **sim** | `main` |
| 8 | `page/cotacoes.twig` | **sim** | `main` |
| 9 | `page/texto.twig` | **sim** | `main` |
| 10 | `page/cassino.twig` | **sim** | `head`, `main`, `script` (pode copiar do site9: é idêntico ao base) |
| 11 | `page/cassino-player.twig` | recomendado | página standalone; se ausente, o controller base entrega a do site9 |
| 12 | `financeiro/deposito.twig` | **sim** | `list`, `main`, `script` + include do modal PIX |
| 13 | `financeiro/saque.twig` | **sim** | `list`, `main`, `script` + include do modal de saque |
| 14 | `financeiro/extrato.twig` | **sim** | `list`, `main`, `script` |
| 15 | `financeiro/transferencia.twig` | **sim** | `main`, `script` |
| 16 | `financeiro/caixa.twig` | **sim** | `list`, `main`, `script` |
| 17 | `inc/personalizacao-css.twig` | opcional | se copiar, troque `{% include 'website/inc/personalizacao-css.twig' %}` no layout por `website11/inc/...`; senão reutilize o do `website/` |
| 18 | `inc/modal-deposito-pix.twig` | opcional | idem (`website/inc/...` hoje) |
| 19 | `inc/header.twig`, `inc/slideshow.twig`, `inc/footer.twig`, `modals/login.twig` | **não** | o site9 não tem; não são obrigatórios |

### 1.3 Arquivos que **precisam ser editados** (2 arquivos, 3 pontos)

| Arquivo | Ponto | Conteúdo |
|---|---|---|
| `inc.config.php` | `use` (~linha 21) | `use app\modules\website11\Site11;` |
| `inc.config.php` | `$config['modules']` (~linha 138) | `'site11' => ['path' => 'app\\modules\\website11', 'class' => Site11::class],` |
| `app/models/DadosModel.php` | `getTemas()` (~linha 57) | `'site11' => 'Bet24 (bet24)',` |

Opcional/cosmético: atualizar a lista de comentários de `$SITE_LAYOUT` (`inc.config.php:47-58`) e definir `$SITE_LAYOUT = 'site11'` (só vale como fallback se o banco não tiver tema).

⚠️ Antes de editar `inc.config.php`/`DadosModel.php`, guarde uma cópia (não há git na raiz do projeto):

```powershell
Copy-Item C:\wwwroot\banca\inc.config.php      C:\wwwroot\banca\docs\bet24-core\_backup_inc.config.php
Copy-Item C:\wwwroot\banca\app\models\DadosModel.php C:\wwwroot\banca\docs\bet24-core\_backup_DadosModel.php
```

(Os backups ficam no diretório de docs, que é o único que os analistas podem escrever; remova-os ao final se o lead pedir.)

---

## 2. Passo 1 — criar o módulo PHP

### 2.1 Diretórios

```
app/modules/website11/
app/modules/website11/controllers/
app/modules/website11/controllers/financeiro/
app/views/website11/
app/views/website11/page/
app/views/website11/financeiro/
```

### 2.2 `Site11.php`

```php
<?php

namespace app\modules\website11;

use app\modules\website\Site;

class Site11 extends Site
{
    public function __construct()
    {
        parent::__construct();
        view_vars('layout', 'website11/layout.twig');
    }
}
```

> Pode importar `use app\models\DadosModel;` como os outros temas (não é usado e não faz mal). O que **não pode** faltar: `extends Site`, `parent::__construct()` e o `view_vars('layout', ...)`.

### 2.3 Controllers (conteúdo exato)

Copie os 13 arquivos de `app/modules/website9/controllers/**` e troque **somente** o namespace/caminho do template. Modelo:

```php
<?php

namespace app\modules\website11\controllers;

class apostarController extends \app\modules\website\controllers\apostarController
{
    protected $tpl = 'website11/page/apostar';
}
```

```php
<?php

namespace app\modules\website11\controllers;

class apostasController extends \app\modules\website\controllers\apostasController
{
    protected $view = 'website11/page/apostas';
}
```

**Lista de propriedades corretas** (conferir uma a uma):

| Controller | Propriedade |
|---|---|
| `apostarController` | `$tpl` |
| `cassinoController` | `$tpl` |
| `financeiro/caixaController` | `$tpl` ← **corrige o bug do site9** |
| `apostasController` | `$view` |
| `bilheteController` | `$view` |
| `cadastrarController` | `$view` |
| `comunicadosController` | `$view` |
| `financeiro/depositoController` | `$view` |
| `financeiro/extratoController` | `$view` |
| `financeiro/saqueController` | `$view` |
| `financeiro/transferenciaController` | `$view` |

`indexController` e `perfilController` são **classes completas** (copiar do site9 e trocar `website9/page/...` → `website11/page/...`):

```php
<?php

namespace app\modules\website11\controllers;

use app\core\Controller;
use app\models\CotacoesModel;
use app\models\helpers\PaginasModel;
use app\models\UsersModel;

class indexController extends Controller
{
    function indexAction()
    {
        switch ($ref = url_parans(0)) {
            case 'termos-uso':
            case 'regras':
            case 'perguntas-frequentes':
                $this->view('website11/page/texto', [
                    'pagina' => PaginasModel::getByRef('site-' . $ref),
                ]);
                break;
            default:
                location(url("apostar"));
        }
    }

    function logoutAction()
    {
        UsersModel::logOut();
        location();
    }

    function cotacoesAction()
    {
        $this->view('website11/page/cotacoes', [
            'grupos' => CotacoesModel::GRUPOS,
            'cotacoes' => CotacoesModel::getCotacoesAtivas(),
        ]);
    }

    function twigAction()
    {
        $this->viewCacheClean();
        location(url_referer());
    }
}
```

`perfilController` (versão corrigida — ver §5.5):

```php
<?php

namespace app\modules\website11\controllers;

use app\core\Controller;
use app\models\UsersModel;
use app\modules\admin\Admin;
use Exception;

class perfilController extends Controller
{
    function indexAction()
    {
        if (!Admin::getLogged()) {
            location(url());
        }

        $this->view('website11/page/perfil', [
            'user' => Admin::getLogged(),
        ]);
    }

    function salvarAction()
    {
        try {
            if (!Admin::getLogged()) {
                throw new Exception('Acesso restrito');
            }

            $user = Admin::getLogged();
            $user->save(inputPost());

            return [
                'message' => 'Perfil atualizado com sucesso.',
                'result' => 1,
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }

    // Alias para o template que usa insertAction: 'atualizar'
    function atualizarAction()
    {
        return $this->salvarAction();
    }
}
```

> Alternativa mais simples: **não** criar `atualizarAction` e mudar o template para `insertAction: 'salvar'`. Use uma das duas — nunca as duas ao mesmo tempo sem testar (a rota `atualizar` cai no `notfoundAction`, que responde `result=1` sem salvar).

### 2.4 Lint imediato

```powershell
$php = 'C:\BtSoft\php\82\php.exe'
Get-ChildItem C:\wwwroot\banca\app\modules\website11 -Recurse -Filter *.php |
    ForEach-Object { & $php -l $_.FullName }
```

Saída esperada: `No syntax errors detected in ...` (PHP 8.2.35 CLI disponível em `C:\BtSoft\php\82\php.exe`).

---

## 3. Passo 2 — criar as views

### 3.1 Estratégia

1. Copie **todo** `app/views/website9/` → `app/views/website11/` (16 arquivos).
2. Nos controllers do tema, os caminhos já apontam para `website11/...`.
3. Nos templates, **não é preciso trocar `{% extends layout %}`** (a variável `layout` resolve para `website11/layout.twig` pelo `Site11`).
4. Nos `include` cruzados, decida:
   - **reutilizar** (`website/inc/...`, `admin/inc/modal-saque.twig`) → nada a fazer; é o que o site9 faz;
   - **copiar** para `website11/inc/...` e atualizar o `{% include %}` correspondente no template.

### 3.2 `layout.twig` — esqueleto contratual mínimo

```twig
<!doctype html>
<html lang="pt-br">
<head>
    {{ seo_header() }}

    <link rel="stylesheet" href="css/bootstrap4.css"/>
    <link rel="stylesheet" href="css/SEU-TEMA.css?v=1.0.0"/>   {# ou darkmodern.css enquanto não houver CSS novo #}
    <link rel="stylesheet" href="css/loading.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>

    {% block head %}{% endblock %}

    {{ dados.getHtmlHead()|raw }}
    {% include 'website/inc/personalizacao-css.twig' %}   {# ou website11/inc/... se copiar #}
</head>
<body>

<div id="website" {% if pageAposta %}v-cloak{% endif %}>

    <header> ... markup do tema ... </header>

    <main id="main">
        {% block main %}{% endblock %}
    </main>

    <footer> ... </footer>

</div>

{{ seo_footer() }}

<script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="node_modules/sweetalert/dist/sweetalert.min.js"></script>
<script src="cdn/js/jquery.mask.js"></script>
<script src="cdn/js/jquery.form.js"></script>
<script src="cdn/js/jquery.serializeObject.js"></script>
<script src="cdn/js/mask.js"></script>
<script src="cdn/js/string.js"></script>      {# String.prototype.toReal — usado pelo cupom #}
<script src="cdn/js/admin.js"></script>      {# url(), $.adminPage #}
<script src="js/site.js"></script>

<script>
    $('#modal-login').adminPage({
        autoSearch: false,
        controller: 'login',
        module: 'entrar',
    }).on("success", function (event, e) {
        if (e.result == 1) window.location.href = e.url;
    });
</script>

{% block script %}{% endblock %}

{{ dados.getHtmlFooter()|raw }}

</body>
</html>
```

Checklist do layout (o que **não** pode faltar):

- `{{ seo_header() }}` e `{{ seo_footer() }}` (o segundo injeta `URL_APP`, `MODULE`, `CONTROLLER`… e é pré-requisito da função JS `url()`);
- `css/bootstrap4.css` + `css/loading.css` (`[v-cloak]`, overlay `html.loading`);
- `cdn/js/string.js` (`toReal`), `cdn/js/admin.js` (`url`, `$.adminPage`), `cdn/js/mask.js` (`mask-*` dos formulários), `jquery.form.js` (`ajaxSubmit` do adminPage);
- `<div id="website" {% if pageAposta %}v-cloak{% endif %}>` — raiz do Vue;
- `{% block head %}`, `{% block main %}`, `{% block script %}`;
- `#modal-login` (ou qualquer modal de login) com o `adminPage({controller:'login', module:'entrar'})` — é o login de todo o site;
- `{{ dados.getHtmlHead()|raw }}` e `{{ dados.getHtmlFooter()|raw }}` (HTML customizado do admin).

### 3.3 Templates de página — conteúdo mínimo por arquivo

| Template | `extends` | Blocos | Variáveis usadas | JS/endpoints |
|---|---|---|---|---|
| `page/apostar.twig` | layout | `main`, `script` (+ opcional `cupom`/`modalCotacoes` como no site9) | `hoje`, `amanha`, `apostaMinima`, `apostaMaxima`, `cotacaoMinima`, `cotacaoMaxima`, `retornoMaximo`, `minJogos`, `condicaoCotacao`, `premioRevenda`, `pageAposta`, `dados`, `user`, `banners()`, `destaquesJogosHome()`, `destaquesCassinoHome()` | `{{ block('script', 'website/page/apostar.twig') }}` com `{% set selector = '#website' %}`; ver `apostar-vue.md` |
| `page/apostas.twig` | layout | `main`, `list`, `script` | `busca`, `message` | `POST /apostas/list` |
| `page/bilhete.twig` | layout | `main`, `bilhete`, `script` | `title`, `_post.codigo`, `aposta`, `user` | `POST /bilhete/list`, `POST /bilhete/validar` |
| `page/cadastrar.twig` | layout | `main`, `script` | `bancos`, `optEstados()`, `dados.getExibirRegistro()` | `POST /cadastrar/insert`; cidades via `admin.js` |
| `page/perfil.twig` | layout | `main`, `script` | `user` | `POST /perfil/salvar` (ou `atualizar`), `json(user)` |
| `page/comunicados.twig` | layout | `main` | `comunicados()` (função Twig) | — |
| `page/cotacoes.twig` | layout | `main` | `grupos`, `cotacoes` | — |
| `page/texto.twig` | layout | `main` | `pagina` | — |
| `page/cassino.twig` | layout | `head`, `main`, `script` | `jogos`, `category`, `search`, `user`, `dados.getTipoSlidesCassino()`, `banners()` | `GET /cassino/jogar/{code}` (AJAX) |
| `page/cassino-player.twig` | **nenhum** (standalone) | — | `game`, `launchUrl`, `user`, `isDemo` | iframe + JS fullscreen |
| `financeiro/deposito.twig` | layout | `list`, `main`, `script` | `modoDeposito`, `gatewayPix`, `pixConfigurado`, `depositoMinimo`, `busca` | `POST /financeiro/deposito/{insert,pix,status-pix,list}` |
| `financeiro/saque.twig` | layout | `list`, `main`, `script` | `busca` | `POST /financeiro/saque/{insert,list}` |
| `financeiro/extrato.twig` | layout | `list`, `main`, `script` | `resumoAnterior`, `busca`, `message` | `POST /financeiro/extrato/list` |
| `financeiro/transferencia.twig` | layout | `main`, `script` | `user`, `pagina('transferencia')` | `POST /financeiro/transferencia/insert` |
| `financeiro/caixa.twig` | layout | `list`, `main`, `script` | `meses`, `relatorio` | `POST /financeiro/caixa/list` |

> Para o `apostar`, o **markup** é livre, mas os bindings Vue e os seletores de §12 do `apostar-vue.md` são contrato.

---

## 4. Passo 3 — registrar o tema (3 edições)

### 4.1 `inc.config.php` — import

Perto das linhas 12-21 (bloco de `use`), adicione depois de `use app\modules\website10\Site10;`:

```php
use app\modules\website11\Site11;
```

### 4.2 `inc.config.php` — `$config['modules']`

No bloco `$config['modules']` (linhas 126-145), após a linha do `site10`:

```php
    'site10' => ['path' => 'app\\modules\\website10', 'class' => Site10::class],
    'site11' => ['path' => 'app\\modules\\website11', 'class' => Site11::class],
```

### 4.3 `app/models/DadosModel.php` — `getTemas()`

```php
    public static function getTemas()
    {
        return [
            'site1' => 'Bonanza Sport (Padrão)',
            // ...
            'site10' => 'DP Sports (Azul Royal & Marinho)',
            'site11' => 'Bet24 (bet24)',        // ← adicionar
        ];
    }
```

### 4.4 Verificação

```powershell
$php = 'C:\BtSoft\php\82\php.exe'
& $php -l C:\wwwroot\banca\inc.config.php
& $php -l C:\wwwroot\banca\app\models\DadosModel.php
& $php -r "define('ABSPATH','C:/wwwroot/banca');" # (não executar o app via CLI; apenas lint)
```

Confira também que **não há vírgula sobrando/faltando** no array (é o erro mais comum em `getTemas()`).

---

## 5. Passo 4 — correções que o site11 deve trazer em relação ao site9

### 5.1 `financeiro/caixaController` — usar `$tpl`

```php
class caixaController extends \app\modules\website\controllers\financeiro\caixaController
{
    protected $tpl = 'website11/financeiro/caixa';   // NÃO $view
}
```

Sem isso, `caixaController::indexAction` (`app/modules/website/controllers/financeiro/caixaController.php:17`) testa `empty($this->tpl)` e **não renderiza nada** (página em branco em `/financeiro/caixa`).

### 5.2 `perfil` — endpoint do formulário

No template `website11/page/perfil.twig`, no `block script`:

```twig
$('.admpage-form')
    .setValues({{ json(user) }})
    .adminPage({
        autoSearch: false,
        insertAction: 'salvar',   // ← corrigido (site9 usa 'atualizar', que não existe)
        controller: 'perfil',
        alertSuccess: true,
        autoReset: false,
    })
    .on('success', function (event, e) {
        if (e.result == 1) $('.admpage-form [type=password]').val('');
    });
```

(ou mantenha `'atualizar'` **e** crie `atualizarAction()` no `perfilController`, §2.3 — os dois lados precisam concordar).

### 5.3 `cassino-player` do tema

O controller base `website/controllers/cassinoController.php:88` renderiza fixo `website9/page/cassino-player` (não há propriedade de template para o player). Duas opções:

- **A) Aceitar o player do site9** (recomendado se o visual for igual): não fazer nada. O arquivo `website9/page/cassino-player.twig` é byte-idêntico ao `website/page/cassino-player.twig`; só o “dono” do arquivo não é o site11.
- **B) Ter o player do site11**: **copiar o método `jogarAction` inteiro** (`app/modules/website/controllers/cassinoController.php:46-96`, ~50 linhas) para o `cassinoController` do tema e trocar **apenas** a linha:

```php
// no método copiado:
$this->view('website9/page/cassino-player', [   // ← trocar por:
$this->view('website11/page/cassino-player', [  // ← caminho do tema
    'game' => $game, 'launchUrl' => $launch['url'], 'user' => $user, 'isDemo' => $isDemo,
]);
```

  O resto do método deve ficar idêntico (checagem de `dados.getExibirCassino()`, validação do `game_code`, `CassinoModel::launchGame`, respostas JSON quando `IS_AJAX`). **Não** tente `parent::jogarAction()` para trocar o player: o caminho é literal dentro do método do base.

### 5.4 Includes cruzados (decidir e padronizar)

| Include no site9 | Opções para o site11 |
|---|---|
| `website/inc/personalizacao-css.twig` (layout) | manter (recomendado; CSS vars do admin continuam valendo) **ou** copiar para `website11/inc/` |
| `website/inc/modal-deposito-pix.twig` (depósito) | manter **ou** copiar (se copiar, o modal continua com endpoints `financeiro/deposito/pix` via JS `url()` — nada a mudar) |
| `admin/inc/modal-saque.twig` (saque) | manter (recomendado — o modal usa `optBancos()` e nomes de campos ligados ao `saqueController`) |
| `website/page/apostar.twig` (bloco script) | manter o `block('script', ...)` (recomendado — é a lógica compartilhada) **ou** copiar o bloco inteiro para o tema |
| `website/page/apostar-imprimir-tabela.twig`, `website/page/pre-bilhete.twig` | vêm de graça (controllers base); não há como trocar sem sobrescrever actions |

### 5.5 Variáveis que o template do site9 usa sem o controller fornecer (herdadas do bug)

- `website9/financeiro/saque.twig:29` → `{{ documento }}` (não existe) — no site11, remova a linha ou passe `'documento' => ...` no `saqueController` do tema (sobrescrevendo `listAction`).
- `website9/financeiro/extrato.twig:24` → `v.credito` fora do `for`. O controller envia `resumoAnterior` = **array** `{pontos, creditos}` (`HistoricoBancarioModel::getSaldo`, `app/models/HistoricoBancarioModel.php:250-275`). No site11, use na linha “Saldo anterior”:
  `{{ resumoAnterior.creditos|default(0)|number_format(2, ',', '.') }}`.

---

## 6. Passo 5 — ativar o tema (somente quando o lead liberar)

### 6.1 Pelo admin (recomendado)

1. Login como master/developer em `/entrar`.
2. Acesse o painel (`/admin`) → **Configurações → Personalização** (`app/modules/admin/controllers/config/personalizacaoController.php`).
3. No select **Tema**, escolha **Bet24 (bet24)** e salve.
4. O `inc.config.php` passa a apontar `$config['modules']['site']` para `website11` no próximo request.

### 6.2 Por SQL (alternativa, usar com cautela)

O tema é o campo `tema` do registro de configuração em `sis_dados` (`DadosModel::getTemaAtivo()` lê o **último** registro por id):

```sql
-- leia antes:
SELECT id, tema FROM sis_dados ORDER BY id DESC LIMIT 1;
-- troque (id do registro retornado):
UPDATE sis_dados SET tema = 'site11' WHERE id = <ID>;
```

Regras: não alterar nenhuma outra coluna; manter o valor antigo (`site9`) anotado para reverter:
`UPDATE sis_dados SET tema = 'site9' WHERE id = <ID>;`

### 6.3 Reverter

Repita 6.1 escolhendo o tema anterior (site9) ou o UPDATE de reversão. O tema anterior não é perdido: os módulos/views continuam no lugar.

---

## 7. Passo 6 — smoke test (depois de ativar)

Rode a partir do host que serve o site (`http://banca.0um.online/`), sessão anônima e depois logado (master/client). Objetivo: pegar **redirect silencioso** e **página em branco**.

```powershell
$base = 'http://banca.0um.online'
$rotas = @(
  '/', '/apostar', '/apostar/jogos', '/apostas', '/bilhete', '/cadastrar',
  '/comunicados', '/cotacoes', '/termos-uso', '/regras', '/perguntas-frequentes',
  '/cassino', '/financeiro/deposito', '/financeiro/saque',
  '/financeiro/extrato', '/financeiro/transferencia', '/financeiro/caixa'
)
foreach ($r in $rotas) {
  try {
    $resp = Invoke-WebRequest -Uri "$base$r" -UseBasicParsing -MaximumRedirection 0 -TimeoutSec 20 -ErrorAction Stop
    "{0,-32} {1} {2} bytes" -f $r, $resp.StatusCode, $resp.Content.Length
  } catch {
    $status = $_.Exception.Response.StatusCode.value__
    $loc = $_.Exception.Response.Headers['Location']
    "{0,-32} {1} redirect->{2}" -f $r, $status, $loc
  }
}
```

Critérios de aceite:

| Rota | Esperado (anônimo) |
|---|---|
| `/` | 302 para `/apostar` |
| `/apostar` | 200, HTML com o markup do novo tema (checar uma classe/`<title>` do site11), blocos do Vue renderizados, sem `${...}` visível |
| `/apostar/jogos` | 200, `Content-Type: application/json`, chaves `cotacoes, grupos, paises, datas` |
| `/apostas` | 200 (lista vazia/anônima) |
| `/bilhete` | 200 |
| `/cadastrar` | 200 se `exibirRegistro`; senão 302 para `/` |
| `/comunicados` | 200 |
| `/cotacoes` | 200 |
| `/termos-uso` | 200 (se a página existir no banco; senão 200 com conteúdo vazio) |
| `/cassino` | 200 se `exibirCassino`; senão 302 |
| `/financeiro/*` | 302 para `/entrar` (anônimo) — **não** 200 em branco |

Logado como cliente: `/financeiro/deposito`, `/financeiro/saque`, `/financeiro/extrato`, `/financeiro/transferencia`, `/financeiro/caixa`, `/perfil`, `/apostas` devem responder **200 com conteúdo** (e `/financeiro/caixa` é o teste que pega o bug do `$tpl`).

Também vale testar as ações AJAX:

```powershell
# catálogo de jogos (JSON)
Invoke-WebRequest "$base/apostar/jogos" -UseBasicParsing -Headers @{'X-Requested-With'='XMLHttpRequest'} | Select-Object -ExpandProperty Content | Select-Object -First 1
# lista de apostas (HTML do bloco list; exige sessão logada no cookie)
# via navegador: /apostas deve carregar a tabela sem erro no console
```

Checklist de console do navegador (F12) nas páginas testadas:

- sem `Uncaught TypeError` (principalmente em `apostar` — Vue não montando deixa `[v-cloak]` invisível);
- aba Network: `apostar/jogos` 200; nenhuma chamada para `/website9/...` (o tema novo não deve gerar URLs do tema antigo);
- `POST` do `adminPage` retornando JSON de verdade (não o `{"message":"Ação inválida.","result":1}` do `notfoundAction`).

---

## 8. Passo 7 — riscos de cache (o que pode te enganar)

| Cache | Situação | Ação |
|---|---|---|
| Twig | **desligado** (`true or IS_LOCAL` em `app/traits/core/view.php:66`); `_temp/twig` não é criado; `/twig` é no-op | editar `.twig` reflete no próximo F5; se “não refletir”, confira se está editando o arquivo certo (`website11` × `website9`) |
| OpCache PHP | o painel pode ter opcache; o `php -l` não invalida o cache do Apache | se uma alteração em `.php` não refletir, aguarde o `validate_timestamps` ou reinicie o PHP-FPM (somente o lead, conforme regras) |
| Navegador (CSS/JS) | <code>css/*.css?v=x</code>, `js/site.js?v=1.0.0` | ao criar CSS/JS novos, inclua `?v=1.0.0` (ou versão nova) nos `<link>/<script>` |
| HTML | `cache_get/cache_set` existem (`app/functions/core.php:71-108`), mas **não são usados** pelo site (apenas o módulo `api`/outros módulos podem usar) | nada a fazer |
| CSS do admin (`personalizacao-css.twig`) | impresso a cada request | reflete imediatamente |

---

## 9. Definition of Done (checklist final)

- [ ] `app/modules/website11/Site11.php` existe, `extends Site`, seta `layout` para `website11/layout.twig`.
- [ ] 13 controllers criados (8 em `controllers/`, 5 em `controllers/financeiro/`), cada um com `$tpl`/`$view` **correto** (tabela §1.1).
- [ ] `php -l` limpo em todos os arquivos novos + `inc.config.php` + `DadosModel.php`.
- [ ] `inc.config.php`: `use` + `$config['modules']['site11']` adicionados.
- [ ] `DadosModel::getTemas()` contém `'site11'`.
- [ ] `app/views/website11/layout.twig` com `#website` (v-cloak condicional), `seo_header/seo_footer`, blocos `head/main/script`, CSS/JS obrigatórios.
- [ ] 16 templates de página criados com os blocos da tabela §3.3.
- [ ] `apostar.twig`: `{% set selector = '#website' %}` + `{{ block('script', 'website/page/apostar.twig') }}` + DOM com os seletores/bindings de `apostar-vue.md` §12.
- [ ] `perfil.twig`/`perfilController` concordam (`salvar` ou `atualizar`).
- [ ] `financeiro/caixaController` usa `$tpl`.
- [ ] Tema ativado pelo admin (ou SQL), smoke test §7 verde nas rotas anônimas e logadas, console limpo.
- [ ] Tema anterior (`site9`) sabe ser restaurado (anotado).
- [ ] Nenhum arquivo fora de `app/modules/website11/`, `app/views/website11/`, `inc.config.php`, `app/models/DadosModel.php` foi modificado (conferir com o lead; não há git no projeto).

---

## 10. Anexo — armadilhas completas (referência rápida)

| # | Armadilha | Sintoma | Correção |
|---|---|---|---|
| 1 | Faltou `'site11'` em `DadosModel::getTemas()` | Tema novo nunca ativa; site abre no layout antigo/base | adicionar a chave |
| 2 | Faltou `$config['modules']['site11']` | idem / redirect estranho | adicionar a entrada + `use` |
| 3 | Controller do tema ausente | URL redireciona para `/apostar` sem erro | criar todos os 13 controllers |
| 4 | Namespace/arquivo do controller errado | rota cai no fallback (igual ao 3) | conferir `namespace app\modules\website11\controllers` (e `...\financeiro`) |
| 5 | `$view` usado onde o base lê `$tpl` (`caixa`) | página em branco | `protected $tpl` |
| 6 | `$tpl` usado onde o base lê `$view` | página em branco/erro “view não existe” | conferir tabela §1.1 |
| 7 | `insertAction` sem action no controller | “sucesso” que não salva (`result=1` do `notfoundAction`) | corrigir o template ou criar a action |
| 8 | Action inexistente em form não-AJAX | texto “Página inexistente” | idem |
| 9 | Template `website11/...` inexistente | `Twig_Error_Loader` (tela de erro 500 se debug, branco se não) | criar o arquivo ou ajustar caminho |
| 10 | `{% extends layout %}` esquecido | página renderiza só o bloco, sem layout | adicionar |
| 11 | Falta `#website` no layout | Vue não monta; `[v-cloak]` esconde tudo | adicionar o wrapper |
| 12 | Falta `pageAposta` no controller/template | `#website` sem `v-cloak` (flash de `${...}`) | manter a variável no `indexAction` |
| 13 | Falta `css/loading.css` | overlay/v-cloak sem efeito; conteúdo “pisca” | incluir no `<head>` |
| 14 | Falta `cdn/js/string.js` | cupom quebra (`toReal is not a function`) | incluir antes do bloco `script` |
| 15 | Falta `cdn/js/admin.js` | `$.adminPage`/`url()` undefined; listas e formulários não funcionam | incluir |
| 16 | Falta `{{ seo_footer() }}` | `URL_APP`/`MODULE` undefined; `url()` JS quebra | incluir antes dos `<script>` de página |
| 17 | Include apontando para `website9/...` | o tema novo “puxa” markup do antigo (as vezes funciona, mas não é seu) | trocar por `website11/...` quando copiar |
| 18 | Include `website/...` removido do repositório | erro de loader | manter os includes base (não apagar `app/views/website/`) |
| 19 | CSS do site11 não cobre as classes do markup | layout quebrado/feio | criar CSS próprio e linkar com `?v=` |
| 20 | CSS de compactação mobile (`personalizacao-css.twig`) não cobre os prefixos novos | tabelas estouram no mobile | reutilizar prefixos (`leon-`/`dm-`) ou adicionar regras no CSS do tema |
| 21 | `color: var(--...)` sem definir as variáveis | cores pretas/brancas inesperadas | definir `:root` no CSS do tema |
| 22 | Editar `app/views/website9/...` “só para testar” | quebra o tema em produção (`site9` é o ativo) | nunca editar fora de `website11` |
| 23 | Editar `app/modules/website/...` (base) | afeta **todos** os temas | não fazer; se precisar, coordenar com o lead |
| 24 | Esquecer `use app\modules\website11\Site11;` | `Class "Site11" not found` no boot (500 em todo o site) | adicionar o `use` |
| 25 | Ativar o tema antes de todos os templates existirem | site “quebrado” ao meio (algumas rotas brancas) | ativar só após o Passo 7 |
