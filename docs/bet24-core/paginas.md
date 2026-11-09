# Contrato das páginas — tema `site9` (referência para o `site11` / bet24)

> Complemento de `arquitetura.md`. Aqui está o contrato **página por página**: rota, controller, template, variáveis, objetos usados no Twig, endpoints AJAX (URLs exatas, método, payload, resposta) e o que o JS faz.
>
> Base analisada: `app/modules/website9` + `app/views/website9` (tema ativo) e `app/modules/website` + `app/views/website` (base herdada). Onde o tema do site11 deve divergir, está marcado.
>
> **Convenção de URLs**: como o módulo `site` aponta para o tema ativo, as URLs “limpas” são sem prefixo (`/apostar`). Acessando explicitamente por `/site9/...`, o prefixo aparece. Nos endpoints, `url(...)` no Twig e `url(...)` no JS geram URLs **relativas ao root** (`https://host/...`).

---

## 0. Índice de rotas e endpoints

| Rota (GET) | Controller (classe) | Action | Template | Tipo |
|---|---|---|---|---|
| `/` | tema `indexController` | `index` | `website9/page/texto` ou redirect | HTML |
| `/apostar` | `apostarController` | `index` | `website9/page/apostar` | HTML |
| `/apostar/jogos` | `apostarController` | `jogos` | — | **JSON** |
| `/apostar/apostar` | `apostarController` | `apostar` | — | **JSON (POST)** |
| `/apostar/imprimirTabela` | `apostarController` | `imprimirTabela` | `website/page/apostar-imprimir-tabela` | HTML standalone |
| `/apostar/imprimir/{token}` | `apostarController` | `imprimir` | `website/page/pre-bilhete` | HTML standalone |
| `/apostar/date` | `apostarController` | `date` | — | texto (`date('c')`) |
| `/apostas` | `apostasController` | `index` | `website9/page/apostas` | HTML |
| `/apostas/list` | `apostasController` | `list` | bloco `list` de `website9/page/apostas` | HTML (POST) |
| `/apostas/bilhete/{token}` | `apostasController` | `bilhete` | `admin/apostas/bilhete` | HTML standalone |
| `/apostas/imprimir/{token}` | `apostasController` | `imprimir` | `admin/apostas/pdf` → PDF | binário |
| `/bilhete` | `bilheteController` | `index` | `website9/page/bilhete` | HTML |
| `/bilhete/list` | `bilheteController` | `list` | bloco `bilhete` | HTML (POST) |
| `/bilhete/validar` | `bilheteController` | `validar` | — | **JSON (POST)** |
| `/cadastrar` | `cadastrarController` | `index` | `website9/page/cadastrar` | HTML |
| `/cadastrar/insert` | `cadastrarController` | `insert` | — | **JSON (POST)** |
| `/perfil` | `perfilController` (só no tema) | `index` | `website9/page/perfil` | HTML |
| `/perfil/salvar` | `perfilController` | `salvar` | — | **JSON (POST)** |
| `/comunicados` | `comunicadosController` | `index` | `website9/page/comunicados` | HTML |
| `/cotacoes` | `indexController` | `cotacoes` | `website9/page/cotacoes` | HTML |
| `/termos-uso`, `/regras`, `/perguntas-frequentes` | `indexController` | `index` | `website9/page/texto` | HTML |
| `/logout` | `indexController` | `logout` | — | redirect |
| `/twig` | `indexController` | `twig` | — | redirect (no-op de cache) |
| `/cassino` | `cassinoController` (do módulo base) | `index` | `website9/page/cassino` | HTML |
| `/cassino/jogar/{code}` | `cassinoController` | `jogar` | `website9/page/cassino-player` (HTML) ou **JSON** em AJAX | misto |
| `/financeiro/deposito` | `financeiro\depositoController` | `index` | `website9/financeiro/deposito` | HTML |
| `/financeiro/deposito/insert` | idem | `insert` | — | JSON (POST multipart) |
| `/financeiro/deposito/pix` | idem | `pix` | — | JSON (POST) |
| `/financeiro/deposito/status-pix` | idem | `statusPix` | — | JSON (POST) |
| `/financeiro/deposito/list` | idem | `list` | bloco `list` | HTML (POST) |
| `/financeiro/saque` | `financeiro\saqueController` | `index` | `website9/financeiro/saque` | HTML |
| `/financeiro/saque/insert` / `list` | idem | `insert`/`list` | — / bloco `list` | JSON / HTML |
| `/financeiro/extrato` | `financeiro\extratoController` | `index` | `website9/financeiro/extrato` | HTML |
| `/financeiro/extrato/list` | idem | `list` | bloco `list` | HTML (POST) |
| `/financeiro/transferencia` | `financeiro\transferenciaController` | `index` | `website9/financeiro/transferencia` | HTML |
| `/financeiro/transferencia/insert` | idem | `insert` | — | JSON (POST) |
| `/financeiro/caixa` | `financeiro\caixaController` | `index` | `website9/financeiro/caixa` (⚠️ em branco no site9 — bug do `$tpl`, §13) | HTML |
| `/financeiro/caixa/list` | idem | `list` | bloco `list` | HTML (POST) |
| `/entrar/login/insert` | `entrar\loginController` | `insert` | — | JSON (POST) |
| `/localizacao/cidades` | módulo localizacao | `index` | HTML `<option>` | HTML (POST) |

**Contrato do `$.adminPage` (todas as listas/formulários AJAX):** o JS envia
`POST` com `Content-Type` de formulário e dois campos (`cdn/js/admin.js:874-880, 1020-1024`):

```js
{ ajax_serialize: JSON.stringify(values), dtCache: <timestamp> }
```

No servidor, `app/functions/input.php:3-10` decodifica `ajax_serialize` para `$_POST` **antes** de qualquer código → os controllers enxergam `inputPost('campo')` normalmente. Respostas:

| Tipo de chamada | Action padrão | Resposta esperada |
|---|---|---|
| salvar formulário | `insert` (ou `insertAction` configurado) | JSON `{result: 1\|0, message: '...'}` (e opcionalmente `saldo`, `url`, `historico`, `link`, `share`, `codigo`) |
| atualizar registro (com `id`) | `update` | JSON idem |
| listar | `list` | HTML do bloco (ou JSON `{html: '...'}`) injetado no `container` |
| excluir | `excluir` | JSON |
| status/visible | `status`/`visible` | JSON |

---

## 1. `apostar` — home + bilhete (página principal)

**Controller**: `app/modules/website9/controllers/apostarController.php` (7 linhas) — herda de `app\modules\website\controllers\apostarController` e só troca o template:

```php
class apostarController extends \app\modules\website\controllers\apostarController
{
    protected $tpl = 'website9/page/apostar';
}
```

**Base**: `app/modules/website/controllers/apostarController.php` (412 linhas).

### 1.1 `GET /apostar` → `indexAction` (`:30-62`)

Variáveis passadas ao template:

| Variável | Tipo | Cálculo |
|---|---|---|
| `hoje` | string `Y-m-d` | `date('Y-m-d')` |
| `amanha` | string `Y-m-d` | `date('Y-m-d', strtotime('+1day'))` |
| `cotacaoMaxima` | float (fallback `999`) | graduação > config |
| `cotacaoMinima` | float | graduação > config |
| `apostaMinima` | float | usuário > graduação > config |
| `apostaMaxima` | float (fallback `9999`) | idem |
| `retornoMaximo` | float (fallback `9999`) | config |
| `condicaoCotacao` | array | `$config->getCondicaoCotacao(true)` — usado no JS (`getCotacaoMinima`) |
| `minJogos` | int | usuário > graduação > config |
| `premioRevenda` | numérico | `$config->getRevendaPaga()` |
| `pageAposta` | `true` | liga `v-cloak` no `#website` e o cupom mobile |

**Template**: `app/views/website9/page/apostar.twig` (773 linhas). Estrutura:

1. `{% extends layout %}`
2. `{% block main %}` (linha 3) — hero carousel (banners/destaques/jogos) + strip de categorias + grid de 3 colunas:
   - hero: montado 100% em Twig a partir de `banners('site-slideshow', null, null, 10)`, `destaquesJogosHome(3)`, `destaquesCassinoHome(3)` e `dados.getTipoSlidesHome()` (modos `mesclado`, `banners`, `jogos`, `futebol_banners`, `cassino_banners`, `desativado`). Nos slides de jogo o HTML chama o Vue **inline**: `onclick="app.apostar({{ g|json_encode|e('html_attr') }}, '90', {campo:'casa', principal:'1', sigla:'1'})"` (linhas 156-170) e `app.maisCotacoes(...)` (linha 115).
   - sidebar de competições: `v-for="c in getCampeonatos(null, false)"`, `setCampeonato`, contador `${getCampeonatos(null,false).length}`.
   - coluna central: chips de data (`setData`), busca (`v-model="search"` + `#dpSearchInput`), lista `v-for="campeonato in getCampeonatos(null, true)"` → `v-for="jogo in getJogos(campeonato)"`, odds `getCotacoesPrincipais()` + `apostar(jogo,'90',c)`, badge “+N” via `totalCotacoes`.
   - coluna direita desktop: `{{ block('cupom') }}` (linha 505).
3. `{% block cupom %}` (linha 529) — **BOLETIM** (bilhete): lista de `aposta.jogos`, `rmAposta(i)`, `valorCotacao`, input `v-model="valorAposta"`, `premio`, botão `v-on:click="finalizarAposta"` `v-bind:disabled="!apostaValida"`.
4. `{% block modalCotacoes %}` (linha 608) — modal `#modal-cotacoes` com tabs de tempo (`mJogo.tempo = 90|'pt'|'st'`), grupos `cotacoesGrupos`, odds `getCotacoesByGrupo(grupo, mJogo.tempo)` e clique `apostar(mJogo.jogo, mJogo.tempo, c)`.
5. `{% block script %}` (linha 680):

```twig
{% set selector = '#website' %}
{{ block('script', 'website/page/apostar.twig') }}   {# ← app Vue completo do tema base #}
<script>
    // badge do cupom mobile + carrossel do hero (5s, pause no hover, swipe)
</script>
```

O app Vue em si está em **`app/views/website/page/apostar.twig`** (992 linhas, bloco `script` linhas 340-991) — contrato completo em `apostar-vue.md`.

**Endpoints usados pela página**:

| Método | URL | Payload | Resposta |
|---|---|---|---|
| GET | `url('apostar/jogos')` → `/apostar/jogos` | — | JSON `{cotacoes, grupos, paises, datas}` (estrutura na §2 de `apostar-vue.md`) |
| POST | `url('apostar/apostar')` → `/apostar/apostar` | `{valor, cliente, telefone, revendedor, jogos: [{jogo, tempo, cotacao, cotacaoId}]}` | JSON `{result, message, logged, saldo?, historico?, codigo?, share?, link?}` |
| GET | `url('apostar/imprimirTabela')` + `?campeonato=&data=&search=` | querystring | HTML para impressão (`website/page/apostar-imprimir-tabela.twig`, standalone, `window.print()` no onload) |
| GET | `url('apostar/imprimir', [token])` → `/apostar/imprimir/{token}` | — | HTML do pré-bilhete (`website/page/pre-bilhete.twig`) |

**JS da página**: `created()` do Vue faz `$('html').addClass('loading')`, agenda timer de 15s que remove do cupom jogos que fecharam (`jogoAberto`), timer de 5s do hero (no template base) e o `axios.get(url('apostar/jogos'))`; ao final remove `loading` e dispara `$(window).resize()`. O `block('script')` do site9 adiciona o relógio do badge `#leonMobileBadge` (`app.$watch('aposta.jogos.length')`) e o carrossel jQuery `#homeHeroCarousel`/`#homeDots`.

> **site11**: copie o `page/apostar.twig` do site9 e reescreva o markup mantendo os mesmos handlers (`@click`/`v-on:click`, `v-model`, `:class`, `v-if`, `v-for`) e as **classes/ids que o JS compartilhado toca** (ver §DOM de `apostar-vue.md`). Os dados vindos do controller (`hoje`, `amanha`, `cotacaoMaxima`...) precisam continuar disponíveis no template porque o bloco `script` reutilizado os injeta como literais JS.

---

## 2. `apostas` — minhas apostas

**Controller**: `app/modules/website9/controllers/apostasController.php` → herda `website\controllers\apostasController` (`$view = 'website9/page/apostas'`).
**Base**: `app/modules/website/controllers/apostasController.php` (65 linhas).

| Action | Rota | O que faz | Template / variáveis |
|---|---|---|---|
| `indexAction` | `GET /apostas` | renderiza a página | `website9/page/apostas`, sem variáveis |
| `listAction` | `POST /apostas/list` | exige login (AJAX) ou `isDeveloper`; força `user = id logado`; `page=1, forpage=30`; `ApostasModel::busca($parans,...)` | renderiza **bloco `list`** com `busca` (Pagination) ou `message` |
| `imprimirAction` | `GET /apostas/imprimir/{token}[/html]` | busca por token; se `status != 99` gera PDF (`displayPdf`) ou HTML | `admin/apostas/pdf` com `aposta`, `jogos` |
| `bilheteAction` | `GET /apostas/bilhete/{token}` | busca por token; imprime bilhete em página standalone | `admin/apostas/bilhete` com `aposta` |

**Template**: `app/views/website9/page/apostas.twig` (136 linhas)

- `block main`: filtros `dataInicial`, `horaInicial`, `dataFinal`, `horaFinal`, `orderby` (data/valor/retorno asc|desc), `apostadornome` → form `.admpage-form-search` + `<div class="admpage-container"></div>`.
- `block list`: tabela `#`, Código (link `url('apostas/bilhete', [v.getToken()])`), Cliente, Jogos, Valor, Prêmio, Data, Usuário; rodapé com `busca.getPageDescription()` e `{{ busca|raw }}` (paginação); legendas de estado.
- `block script`: `$.adminPage({ formSearch:'.admpage-form-search', container:'.admpage-container' })` → primeira busca automática no `list`.

**Endpoints**: `POST /apostas/list` (payload = campos do formulário) → HTML do bloco `list`.

---

## 3. `bilhete` — consultar/validar bilhete

**Controller**: `app/modules/website9/controllers/bilheteController.php` → base `website\controllers\bilheteController` (`$view = 'website9/page/bilhete'`, 113 linhas).

| Action | Rota | O que faz |
|---|---|---|
| `indexAction` | `GET /bilhete` | renderiza; variável `title = 'Consultar bilhete'` |
| `listAction` | `POST /bilhete/list` | recebe `codigo`, normaliza (`strtoupper`, remove espaço/hífen) e busca `ApostasModel::getByLabel('codigobilhete', ...)`; renderiza **bloco `bilhete`** com `aposta`, `user` (dono da aposta); em erro faz `echo` de um `<div class="alert alert-info">` |
| `validarAction` | `POST /bilhete/validar` | exige login; valida `status == AGUARDANDO_PAGAMENTO (2)`, saldo, jogo não começou/aceita apostas; grava `status=1`, débito e comissões; retorna JSON `{message, saldo, result}` |

**Template**: `app/views/website9/page/bilhete.twig` (120 linhas)

- `block main`: form `.admpage-form` com input `codigo` (`maxlength=12`, `value="{{ _post.codigo }}"`) + botão; `<div class="admpage-container"></div>`.
- `block bilhete`: tabela de detalhes (`getCodigoBilhete`, `getData(true)`, `getApostadorNome`, `user.getNome`, `getValor(true)`, `getJogos`, `getRetornoValido(true)`, `get('acertos')`, mapa de status) e, se `status == 2`, form `.form-bilhete` com botão “Validar Aposta”.
- `block script`: `$.adminPage({ formSearch:'.admpage-form', container:'.admpage-container', searchValues:{codigo:'{{ _post.codigo }}'} })`; o form de validação usa `adminPage({ autoSearch:false, controller:'bilhete', insertAction:'validar', alertSuccess:true })` e no `success` chama `page.reloadSearch()`.

**Endpoints**: `POST /bilhete/list` (codigo) → HTML; `POST /bilhete/validar` (token) → JSON.

---

## 4. `cadastrar` — registro de conta

**Controller**: `app/modules/website9/controllers/cadastrarController.php` → base `website\controllers\cadastrarController` (97 linhas, `$view = 'website9/page/cadastrar'`).

| Action | Rota | O que faz |
|---|---|---|
| `indexAction` | `GET /cadastrar` | se `dados.getExibirRegistro()` = 0 → `location(url())`; seta `Seo::setTitle('Cadastre-se')`; variável `bancos = SaquesModel::optionsBancos()` (HTML de `<option>`) |
| `insertAction` | `POST /cadastrar/insert` | valida `senha`/`confirmar-senha`; cria `UsersModel::newValueObject()`, encadeia `.input('nome'\|'cpf'\|'sexo'\|'nascimento'\|'cep'\|'logradouro'\|'numero'\|'bairro'\|'cidade'\|'telefone'\|'celular'\|'whatsapp'\|'email'\|'login'\|'senha')`, `->save()`, loga (`UsersModel::setLogged`) e retorna JSON `{message, result}` |

**Template**: `app/views/website9/page/cadastrar.twig` (146 linhas) — form `.admpage-form` com 4 blocos (dados pessoais, endereço, contato, acesso). Campos/names obrigatórios: `nome, cpf, sexo, nascimento, cep, logradouro, numero, complemento, bairro, estado, cidade, telefone, celular, whatsapp, email, login, senha, confirmar-senha`. `{{ optEstados() }}` popula o select de estado (`cadastrar.twig:68`).
**JS**: `block script` com `adminPage({ alertSuccess:true, autoSearch:false, autoReset:false, reloadSuccess: url() })` → **POST `/cadastrar/insert`** (valores via `ajax_serialize`); o `reloadSuccess` recarrega a home logada. O `select[name=estado]` é transformado em select de cidades pelo `admin.js` (`estadosCidades`, POST `/localizacao/cidades`).

> Observações: `bancos` não é usado pelo template do site9 (existe para temas que oferecem banco no cadastro); `Complemento` não é enviado pelo controller base (só campo visual).

---

## 5. `perfil` — editar perfil (SÓ existe no tema)

**Controller**: `app/modules/website9/controllers/perfilController.php`. **Não existe no módulo base** (`app/modules/website/controllers/` não tem `perfilController`); os temas 2 e 4..10 têm a sua própria versão. Se o tema novo não tiver, `/perfil` cai no fallback e redireciona para a home.

| Action | Rota | O que faz |
|---|---|---|
| `indexAction` | `GET /perfil` | exige login (senão `location(url())`); renderiza `website9/page/perfil` com `user = Admin::getLogged()` |
| `salvarAction` | `POST /perfil/salvar` | exige login; `$user->save(inputPost())`; retorna JSON `{message:'Perfil atualizado com sucesso.', result:1}` |

**Template**: `app/views/website9/page/perfil.twig` (99 linhas) — form `.admpage-form` (`nome`, `login` disabled, `telefone`, `celular`, `whatsapp`, `email`, `nsenha`, `nsenhar`, `senha` atual); `block script`:

```twig
$('.admpage-form')
    .setValues({{ json(user) }})          {# todos os getters do UserVO viram chaves minúsculas #}
    .adminPage({
        autoSearch: false,
        insertAction: 'atualizar',        {# ⚠️ NÃO EXISTE: o controller só tem salvarAction #}
        controller: 'perfil',
        alertSuccess: true,
        autoReset: false,
    })
```

⚠️ **Armadilha confirmada**: `insertAction: 'atualizar'` → POST `/perfil/atualizar` → action inexistente → `Controller::notfoundAction()` em AJAX devolve `{"message":"Ação inválida.","html":"Página não encontrada.","result":1}` → o `adminPage` interpreta `result=1` como **sucesso** (limpa as senhas) e **o perfil não é salvo**.
**Correção recomendada para o site11**: usar `insertAction: 'salvar'` **ou** implementar `atualizarAction()` no `perfilController` (chamando `salvarAction`). Ver `checklist-tema.md`.

---

## 6. `comunicados`

**Controller**: `app/modules/website9/controllers/comunicadosController.php` → base `website\controllers\comunicadosController` (`$view = 'website9/page/comunicados'`).
`indexAction`: `ComunicadosModel::getComunicados(Admin::getLogged())` → variável `comunicados`.

**Template**: `app/views/website9/page/comunicados.twig` (31 linhas) — ignora a variável do controller e usa a **função Twig** `comunicados()` (linha 3: `{% set comunicados = comunicados() %}`), então o template funciona mesmo sem o controller passar nada. Lista cards com `getTitle()`, `getData(true)`, `getMensagem()|raw`.

---

## 7. `cotacoes` — tabela explicativa

**Controller**: `app/modules/website9/controllers/indexController.php::cotacoesAction` (o base `website\controllers\indexController` também tem `cotacoesAction`, mas renderiza `website/page/cotacoes`).

```php
$this->view('website9/page/cotacoes', [
    'grupos' => CotacoesModel::GRUPOS,                  // [1 => 'Resultado da partida', ... 9]
    'cotacoes' => CotacoesModel::getCotacoesAtivas(),   // CotacaoVO[]
]);
```

**Template**: `app/views/website9/page/cotacoes.twig` (36 linhas) — `for id, g in grupos` e `for c in cotacoes` com `{% if id == c.getGrupo() %}`; usa `c.getTitulo()`, `c.getSigla()`, `c.getDescricao()|raw`.

---

## 8. `texto` — páginas institucionais (termos, regras, FAQ)

**Controller**: `app/modules/website9/controllers/indexController.php::indexAction`:

```php
switch ($ref = url_parans(0)) {
    case 'termos-uso': case 'regras': case 'perguntas-frequentes':
        $this->view('website9/page/texto', ['pagina' => PaginasModel::getByRef('site-' . $ref)]);
        break;
    default: location(url("apostar"));
}
```

**Template**: `app/views/website9/page/texto.twig` (15 linhas) — `pagina.getTitle()|raw` e `pagina.getTexto()|raw`.

**Outras actions do `indexController` do tema**:

| Action | Rota | Efeito |
|---|---|---|
| `logoutAction` | `/logout` | `UsersModel::logOut(); location();` |
| `twigAction` | `/twig` | `viewCacheClean(); location(url_referer());` (no-op — cache Twig desligado) |

> O `in texController` do tema **precisa existir** porque o controller é resolvido no módulo do tema; se faltar, `/` cai no fallback do módulo (que também é o `index`) e `default: location(url("apostar"))` deixa de existir.

---

## 9. `financeiro/deposito` — depósito PIX/manual

**Controller**: `app/modules/website9/controllers/financeiro/depositoController.php` → base `website\controllers\financeiro\depositoController` (`$view = 'website9/financeiro/deposito'`, 224 linhas), que **estende `seguranca`** (obriga login; em POST sem login imprime `É necessário que esteja logado` e encerra; em GET redireciona para a home — `website/controllers/financeiro/seguranca.php:14-24`).

| Action | Rota | Payload | Resposta |
|---|---|---|---|
| `indexAction` | `GET /financeiro/deposito` | — | variáveis `modoDeposito`, `gatewayPix`, `pixConfigurado`, `depositoMinimo` |
| `insertAction` | `POST /financeiro/deposito/insert` | multipart: `valor` (formato BR `1.234,56`), arquivo `upfile` | JSON `{message, result}`; cria `DepositosModel` tipo `manual`, status `AGUARDANDO`, e anexa via `ArquivosModel::addArquivo` |
| `pixAction` | `POST /financeiro/deposito/pix` | `{valor}` | JSON `{result:1, message, token, depositoId, valor (formatado), qrcode (copia-e-cola), transacaoId}`; cria depósito tipo `pixup`/`veopag`, monta postback `url('<gw>/webhook', null, 'api')` e chama `PixUp::createQrCode` ou `VeoPag::createDeposit` |
| `statusPixAction` | `POST /financeiro/deposito/status-pix` | `{token}` | JSON `{result, status (int), statusTitle, aprovado (bool), saldo (formatado)}` |
| `listAction` | `POST /financeiro/deposito/list` | `dataInicial`, `dataFinal`, paginação | HTML do bloco `list` com `busca` |

**Template**: `app/views/website9/financeiro/deposito.twig` (156 linhas)

- `block list` (linha 3): tabela de depósitos (`getTipo()` pixup/veopag vira badge PIX; `voUser()`; `getValor(true)`; `getStatusTitle()`), com `{{ busca }}` para paginação.
- `block main` (linha 68): modal manual `form.modal.admpage-form` com `input name="valor" class="mask-valor"` + `{{ inputFile(...,'upfile','pdf,jpg,jpeg,png') }}`; `{% include 'website/inc/modal-deposito-pix.twig' %}` (linha 102); cabeçalho com botões condicionais `modoDeposito == 'todos'|'pix'` (PIX) e `'todos'|'manual'` (comprovante); form de filtro `.admpage-form-search` (`dataInicial`, `dataFinal`); `<div class="admpage-container"></div>`.
- `block script` (linha 147): `$(".admpage-form").adminPage({ formSearch:'.admpage-form-search', container:'.admpage-container', autoSearch:true })` → **POST `/financeiro/deposito/insert`** (upload) e **`/financeiro/deposito/list`**.

**JS do modal PIX** (`website/inc/modal-deposito-pix.twig`, 192 linhas) — detalhes na §16.

---

## 10. `financeiro/saque`

**Controller**: `app/modules/website9/controllers/financeiro/saqueController.php` → base `website\controllers\financeiro\saqueController` (`$view = 'website9/financeiro/saque'`, 87 linhas, estende `seguranca`).

| Action | Rota | Payload | Resposta |
|---|---|---|---|
| `indexAction` | `GET /financeiro/saque` | — | renderiza |
| `insertAction` | `POST /financeiro/saque/insert` | campos do `SaquesModel::newValueObject(inputPost())`: `valor`, `tipo` (`banco`/`picpay`), `banco`, `agencia`, `conta`, `contatipo`, `variacao`, `nomecompleto`, `cpf`, `cnpj`, `picpay`, `principal` | JSON `{message:'Saque solicitado com sucesso.', result:1}`; se `principal=1` grava os dados bancários no `UserVO` |
| `listAction` | `POST /financeiro/saque/list` | `dataInicial`, `dataFinal`, paginação | HTML do bloco `list` com `busca` (paginado 20) |

**Template**: `app/views/website9/financeiro/saque.twig` (104 linhas)

- `block main` (linha 59): `{% include 'admin/inc/modal-saque.twig' %}` (form `.modal.modal-saque` com campos acima; botão abre via `$('.modal-saque').setValues({})` → `data-toggle`/manipulação Bootstrap), filtros `.admpage-form-search`, `<div class="admpage-container"></div>`.
- `block list` (linha 3): tabela de saques (`getData(true)`, `voUser()`, `getAgencia`, `getConta`, `getBanco`, `getNomeCompleto`, `fncSinal(v.getValor(), true)`, `fncSinal(v.getSaldoCreditos(), true)`, `getStatusTitle`) e `{{ busca }}` para paginação.
- `block script` (linha 96): `$('.modal-saque').adminPage({ formSearch:'.admpage-form-search', container:'.admpage-container' })`.

**Endpoints**: `POST /financeiro/saque/insert`, `POST /financeiro/saque/list`.

> ⚠️ O template usa `{{ documento }}` no bloco `list` (linha 29) sem que o controller envie essa variável → renderiza vazio (Twig não-estrito).

---

## 11. `financeiro/extrato`

**Controller**: `app/modules/website9/controllers/financeiro/extratoController.php` → base `website\controllers\financeiro\extratoController` (`$view = 'website9/financeiro/extrato'`, 62 linhas, `seguranca`).

| Action | Rota | O que faz |
|---|---|---|
| `indexAction` | `GET /financeiro/extrato` | renderiza |
| `listAction` | `POST /financeiro/extrato/list` | exige login; parâmetros `dataInicial` (default `Y-01-01`), `dataFinal` (default `Y-m-t`), `type='credito'`, `page=1`, `forpage=9999`, `status=1`, `user=logado`; calcula `resumoAnterior = HistoricoBancarioModel::getSaldo($user, $dataInicial)` e `busca`; renderiza **bloco `list`** |

**Template**: `app/views/website9/financeiro/extrato.twig` (95 linhas)

- `block main`: filtros `dataInicial`/`dataFinal` já preenchidos (`'now'|date('Y-m-01')` e `'now'|date('Y-m-t')`) + `<div class="admpage-container"></div>`.
- `block list`: tabela (`getInsert()|date('d/m/Y \à\s H\hi')`, `getDescricao()|raw`, `fncSinal(v.getValor(), true)`, `fncSinal(v.getSaldoCreditos(), true)`) e linha “Saldo anterior” (usa `v.credito` — variável fora do laço; hoje renderiza vazio/0). O controller envia `resumoAnterior`, que é um **array** `{pontos, creditos}` (`HistoricoBancarioModel::getSaldo`, `app/models/HistoricoBancarioModel.php:250-275`); a linha correta seria `{{ resumoAnterior.creditos|default(0)|number_format(2, ',', '.') }}`.
- `block script`: `adminPage({ formSearch:'.admpage-form-search', container:'.admpage-container', autoSearch:false })` + `$('.admpage-form-search').submit()` (dispara a primeira busca).

**Endpoints**: `POST /financeiro/extrato/list`.

---

## 12. `financeiro/transferencia`

**Controller**: `app/modules/website9/controllers/financeiro/transferenciaController.php` → base `website\controllers\financeiro\transferenciaController` (`$view = 'website9/financeiro/transferencia'`, 94 linhas, `seguranca`).

| Action | Rota | Regras / payload | Resposta |
|---|---|---|---|
| `indexAction` | `GET /financeiro/transferencia` | renderiza | — |
| `insertAction` | `POST /financeiro/transferencia/insert` | `{destinatario (login), valor}`; destinatário precisa existir/ativo; valor mínimo **R$ 5**; proíbe transferir para si (exceto developer); taxa `dados.getTaxaTransferencia()` (%); valida saldo `valor+taxa`; grava `HistoricoBancarioModel::add` (crédito destinatário, débito remetente, tarifa) e `HistoricoModel` | JSON `{message:'Tranferência realizada com sucesso', result:1}` ou Exception com mensagem |

**Template**: `app/views/website9/financeiro/transferencia.twig` (60 linhas) — form `.admpage-form` com `remetente` (readonly, `user.getLogin()`), `destinatario`, `valor` (`mask-valor`), checkbox `autorizo` (não é enviado no ajax_serialize, só validação HTML) e, opcionalmente, texto de `pagina('transferencia').getTexto()`. `block script`: `adminPage({ autoSearch:false, alertSuccess:true, reloadSuccess:true })` → POST `/financeiro/transferencia/insert` e recarrega a página no sucesso.

---

## 13. `financeiro/caixa`

**Controller**: `app/modules/website9/controllers/financeiro/caixaController.php` → base `website\controllers\financeiro\caixaController` (**65 linhas**).

⚠️ **BUG de contrato**: o base lê `$this->tpl` nos dois actions (linhas 19-20 e 35), mas o site9/site10 declararam `protected $view = 'websiteN/financeiro/caixa'`. Como `$this->tpl` não existe, `empty($this->tpl)` é `true` e o `indexAction` **não renderiza nada** (página em branco). O único tema que acertou foi o site2 (`protected $tpl = 'website2/financeiro/caixa'`).

| Action | Rota | O que faz |
|---|---|---|
| `indexAction` | `GET /financeiro/caixa` | `$user = Admin::getLogged()`; `meses` = `api\caixaController::mesesAction($user)['meses']`; **só renderiza se `$this->tpl` estiver definido** |
| `listAction` | `POST /financeiro/caixa/list` | `relatorio = api\caixaController::indexAction($user)`; renderiza **bloco `list`** com `relatorio` |

**Template**: `app/views/website9/financeiro/caixa.twig` (74 linhas)

- `block list` (linha 3): `{% if relatorio.result == 1 %}` → cards `for v in relatorio.results` (`v.label`, `v.valor|number_format(2, ',', '.')`); senão alerta com `relatorio.message`.
- `block main`: form `.form-search` com `dataInicial`/`dataFinal` (defaults do mês) + `<div class="admpage-container pt-3"></div>`.
- `block script`: `$.adminPage({ autoSearch:false, formSearch:'.form-search', container:'.admpage-container' })` + `$('.form-search').submit()`.

**Endpoints**: `POST /financeiro/caixa/list`.

> **site11**: o controller do tema **deve** declarar `protected $tpl = 'website11/financeiro/caixa';` (não `$view`).

---

## 14. `cassino` — lobby

**Controller**: `app/modules/website9/controllers/cassinoController.php` — herda `app\modules\website\controllers\cassinoController` e só sobrescreve o template (`$tpl = 'website9/page/cassino'`).
**Base**: `app/modules/website/controllers/cassinoController.php` (99 linhas).

| Action | Rota | O que faz |
|---|---|---|
| `indexAction` | `GET /cassino` (opcional `?category=PGSOFT\|POPOK\|WG`, `?search=`) | se `dados.getExibirCassino()` = 0 → `location('')`; `jogos = CassinoModel::getGames(true, $category, $search)`; variáveis `jogos`, `category`, `search`, `user`, `pageTitle='Cassino & Slots'` |
| `jogarAction` | `GET /cassino/jogar/{gameCode}` | se não logado `isDemo=true`; busca game, chama `CassinoModel::launchGame($user, $game)`; **em AJAX** retorna JSON `{result, url, game, isDemo, saldo}`; **fora de AJAX** renderiza a página standalone `website9/page/cassino-player` (`game`, `launchUrl`, `user`, `isDemo`) |

⚠️ O caminho do player está **hardcoded** como `website9/page/cassino-player` no controller base (`website/controllers/cassinoController.php:88`). No site11 há 2 opções: deixar como está (o player do site9 é renderizado — funciona, mas não pertence ao tema) ou sobrescrever `jogarAction` no `cassinoController` do tema usando o player do site11.

**Template**: `app/views/website9/page/cassino.twig` (743 linhas) — **byte-idêntico** a `app/views/website/page/cassino.twig`. Contrato:

- `block head`: CSS próprio do cassino (`.casino-hero`, `.casino-nav-bar`, `.casino-cats-strip`, `.casino-search-input`, `.casino-grid`, `.casino-card`, `.casino-modal-*`).
- `block main`: hero carousel (`dados.getTipoSlidesCassino()`, `banners('site-slideshow',null,null,10)`, jogos `featured`/`fortune-*`), chips de categoria (`url('cassino?category=POPOK')`), busca instantânea (`#casinoSearch` → `filtrarJogosNaTela()`), grade `for j in jogos` com `data-code/data-title/data-id` e `onclick="iniciarJogoCard(this)"`, modal `#casinoGameModal` com `iframe#casinoIframe`.
- `block script`: `iniciarJogo(gameCode, gameName, gameId)` → `GET {base}/cassino/jogar/{code}` via `$.ajax` (dataType json); se `res.result && res.url` usa a URL lançada; se `res.auth === false` → fecha e abre `#modal-login`; senão fallback local `{base}/games/pgv4/src/public/{id}/index.html?...`; `fecharJogo`, `recarregarJogo`, carrossel (5s) e ESC.

**Endpoints**: `GET /cassino/jogar/{game_code}` (AJAX) → JSON; `GET /cassino?category=...` (navegação).

---

## 15. `cassino-player` — tela imersiva do jogo

**Template**: `app/views/website9/page/cassino-player.twig` (234 linhas) — **página standalone** (`<!DOCTYPE html>`, não usa `layout`; mesmas 234+8053 bytes em `website/page/cassino-player.twig`).

- Variáveis do controller: `game` (array com `name` etc.), `launchUrl` (string), `user`, `isDemo`.
- Estrutura: header com botão “Sair” (`url('cassino')`), título do jogo, badge DEMO/AO VIVO, saldo (`user.getCredito(true)` ou `'1.000,00'`), botões Atualizar/Tela cheia, `<iframe src="{{ launchUrl }}" id="gameIframe">`.
- JS: `originalLaunchUrl = {{ launchUrl|json_encode|raw }}`; `recarregarJogo()` recarrega o iframe; `toggleFullscreen()` usa `requestFullscreen`.

---

## 16. Includes compartilhados

### 16.1 `website/modals/login.twig` (44 linhas) — modal de login do tema base

Usado **apenas** pelo layout do tema base (`website/layout.twig:68`). O site9 tem o próprio modal inline no layout (ver 16.6).

- `<form id="modal-login" class="modal fade">` com `name="username"`, `name="password"`.
- JS: `$('#modal-login').adminPage({ autoSearch:false, controller:'login', module:'entrar' })` e no `success` (`e.result == 1`) faz `window.location.href = e.url`.
- Endpoint: **`POST /entrar/login/insert`** (`app/modules/auth/controllers/loginController.php::insertAction`).
- Resposta real do login: `['message' => "{$this->tratamento()} {$user->getNome()}", 'url' => url(null, null, (Admin::isMaster() or Admin::isGerente()) ? 'admin' : 'site'), 'result' => 1]` e cookie `user` com token (30 dias, httponly, samesite=Lax).
- Variáveis usadas: `class` (opcional para cor do botão), `dados.getExibirRegistro()`, `dados.getLinkCadastro()`.

### 16.2 `website/inc/header.twig` (270 linhas) — header do tema base

Usado só pelo `website/layout.twig:24`. Não é usado pelo site9 (que tem header próprio no layout).

Contém 4 blocos: header mobile (`d-none d-lg-none`), menu off-canvas `.menu-mobile` (`html.show-menu`), navbar desktop `.bonanza-navbar` (links com `{% if dados.getExibirBilhete() %}` etc. e `controller == 'x'` para ativo) e bottom nav mobile `.sports-mobile-bottom-nav`.
Helpers usados: `url()`, `dados.imgCapa(true,'logo').getSource(true)`, `user.getCredito(true)`, `user.imgCapa().redimensiona(70,70)`, `user.imgCapa().redimensiona(36,36)`, `dados.getLinkCadastro()`.
Detalhe: no item “Cupom” do bottom nav há `v-if="aposta && aposta.jogos && aposta.jogos.length"` + `${aposta.jogos.length}` — markup Vue que só faz sentido na página do apostar (`pageAposta`); nas outras páginas o `v-if` não é processado por Vue e o `<span v-cloak>` fica escondido por CSS.

### 16.3 `website/inc/slideshow.twig` (35 linhas) — carrossel de banners

- Uso: `{% include 'website/inc/slideshow.twig' with {ref: 'site-slideshow'} %}` (site base, `website/page/apostar.twig:312`).
- `{% set banners = banners(ref) %}` → `BannerVO[]`; renderiza Bootstrap carousel (`#carouselExampleIndicators`) com `<picture><source srcset=mobile><img src=desktop>` quando `responsive` é true.
- Não é usado pelo site9 (hero próprio).

### 16.4 `website/inc/modal-deposito-pix.twig` (192 linhas) — modal PIX automático

Incluído por `website9/financeiro/deposito.twig:102` (e pelo base `website/financeiro/deposito.twig` tem uma cópia inline equivalente).

- DOM: `#modal-deposito-pix`, `#pix-step-form`, `#pix-input-valor` (`mask-valor`, default `20,00`), botões rápidos 10/20/50/100/200, `#btn-gerar-pix`, `#pix-step-qrcode`, `#pix-display-valor`, `#pix-qr-img`, `#pix-copiacola`.
- JS: `resetPix()`, `gerarPix()` → `$.ajax POST url('financeiro/deposito/pix')` `{valor}` (resposta: `result`, `token`, `valor` formatado, `qrcode` copia-e-cola; QR é gerado via `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=<qrcode>`), `iniciarPollingPix(token)` a cada **3s** em `POST url('financeiro/deposito/status-pix')` `{token}` até `res.aprovado` (então `swal` + reload), `copiarPix()`.
- Variáveis Twig usadas (precisam existir na página): `gatewayPix`, `depositoMinimo`.

### 16.5 `website/inc/personalizacao-css.twig` (374 linhas) — CSS de personalização

Incluído separadamente no `<head>` pelos layouts (`website9/layout.twig:14`, `website/layout.twig:17`), **depois** de `{{ dados.getHtmlHead()|raw }}`.

- Gera `<style>` com CSS vars: `--site-logo-width` (`dados.getLogoWidth()|default(160)`), `--site-logo-height` (45), `--site-slide-height` (320), `--site-slide-height-mobile` (160).
- Regras globais de logo (`max-width/height`), slides (`max-height`), “times sem quebra” por prefixo de tema (`b365-`, `bca-`, `btn-`, `pri-`, `sb-`, `dm-`, `dp-`, `leon-`), modal de cotações e um grande `@media (max-width: 768px)` de compactação das tabelas/odds dos temas 4..10.
- Termina com `{% if dados.getExibirEscudos() == 0 %}` escondendo `.b365-team-flag, .bca-team-flag, ...` (não inclui prefixo `leon-`, então no site9 os escudos do hero continuam via PHP-null).
- **Para o site11**: se o novo tema usar prefixos de classe novos (ex.: `bt24-`), essas regras não se aplicam; ou reutilize as classes/prefixos existentes (ex.: `leon-`) ou adicione o CSS no layout/tema.

### 16.6 Modal de login do site9 (inline no layout, linhas 21-53)

- `<form id="modal-login" class="modal fade" method="post">` com `name="username"`, `name="password"`, botão submit, link `dados.getLinkCadastro()` se `dados.getExibirRegistro()`.
- JS no fim do layout (linhas 297-309): `$('#modal-login').adminPage({ autoSearch:false, controller:'login', module:'entrar' }).on("success", ... window.location.href = e.url)`.
- O mesmo `id="modal-login"` é aberto por `data-toggle="modal"` no header, drawer e bottom nav.

### 16.7 `website/inc/footer.twig` — **arquivo vazio** (0 bytes), não referenciado por nenhum template. Ignorar.

---

## 17. Diferenças base × site9 (mapa de templates)

Hash comparado entre `app/views/website/` e `app/views/website9/`:

| Template | Relação |
|---|---|
| `layout.twig` | diferente (site9 reescrito: Leon Bet) |
| `page/apostar.twig` | diferente (site9 773 linhas × base 992; site9 reusa o `script` do base) |
| `page/apostas.twig`, `page/bilhete.twig`, `page/cadastrar.twig`, `page/comunicados.twig`, `page/cotacoes.twig`, `page/texto.twig` | diferentes (semântica de blocos idêntica) |
| `page/cassino.twig` | **IGUAL** (byte a byte) |
| `page/cassino-player.twig` | **IGUAL** (byte a byte) |
| `page/perfil.twig` | só existe no site9/site10 |
| `financeiro/deposito.twig`, `saque.twig`, `extrato.twig`, `transferencia.twig` | diferentes |
| `financeiro/caixa.twig` | existe em `website2` e `website4..website10` (não existe em `website`/site1 nem `website3`); no site9/site10 fica inacessível por causa do bug do `$tpl` (§13) |
| `inc/header.twig`, `inc/slideshow.twig`, `modals/login.twig` | só no base (site9 não tem `inc/` nem `modals/`) |
| `inc/personalizacao-css.twig`, `inc/modal-deposito-pix.twig` | só no base — **usados pelo site9 via include cruzado** |
| `page/apostar-imprimir-tabela.twig`, `page/pre-bilhete.twig` | só no base — **usados por controllers base em qualquer tema** |

---

## 18. Checklist rápido por página (o que o site11 obrigatoriamente precisa ter)

| Página | Controller no tema? | Template no tema? | Observações |
|---|---|---|---|
| apostar | sim (`$tpl`) | sim (+ bloco script reusando base) | precisa de `#website` no layout e `pageAposta` |
| apostas | sim (`$view`) | sim (blocos `main`,`list`,`script`) | |
| bilhete | sim (`$view`) | sim (`main`,`bilhete`,`script`) | |
| cadastrar | sim (`$view`) | sim | select estado/cidade via admin.js |
| perfil | sim (novo, independente) | sim | corrigir `insertAction` |
| comunicados | sim (`$view`) | sim | pode usar função `comunicados()` |
| cotacoes | via `indexController` do tema | sim | |
| texto | via `indexController` do tema | sim | |
| financeiro/deposito | sim (`$view`) | sim (`list`,`main`,`script`) | inclui modal PIX |
| financeiro/saque | sim (`$view`) | sim (`list`,`main`,`script`) | inclui `admin/inc/modal-saque.twig` |
| financeiro/extrato | sim (`$view`) | sim (`list`,`main`,`script`) | |
| financeiro/transferencia | sim (`$view`) | sim (`main`,`script`) | |
| financeiro/caixa | sim (**`$tpl`**) | sim (`list`,`main`,`script`) | corrigir bug do `$tpl` |
| cassino | sim (`$tpl`) | sim (ou copiar do base: é igual) | `jogar` hardcoded p/ `website9/page/cassino-player` |
| cassino-player | (não tem controller próprio) | sim (standalone) | |
| home (`/`) | `indexController` do tema | usa `page/texto` | `default: location(url('apostar'))` |
