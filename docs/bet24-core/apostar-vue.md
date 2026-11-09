# App Vue da página `apostar` — contrato completo

> Documento minucioso do app Vue usado na página principal de apostas, para que o tema `bet24` (`site11`) possa **reescrever o markup mantendo a mesma lógica Vue**.
>
> **Fonte única da lógica**: `app/views/website/page/apostar.twig` → bloco `script` (linhas 340-991, ~650 linhas de JS; o arquivo tem 992 linhas).
> **Quem usa**:
> - tema base `site1`: usa o template inteiro (`website/page/apostar.twig`);
> - tema ativo `site9`: `app/views/website9/page/apostar.twig` (773 linhas) tem markup próprio e **reutiliza o bloco `script` do tema base** via `{{ block('script', 'website/page/apostar.twig') }}` (linha 682), adicionando só o carrossel e o badge do cupom mobile.
> - `site11` deve seguir o mesmo padrão do site9.

Índice:

1. [Como o app é carregado e instanciado](#1-como-o-app-é-carregado-e-instanciado)
2. [Dependências JS e ordem de carregamento](#2-dependências-js-e-ordem-de-carregamento)
3. [Contrato da resposta `GET /apostar/jogos`](#3-contrato-da-resposta-get-apostarjogos)
4. [Contrato do `POST /apostar/apostar`](#4-contrato-do-post-apostarapostar)
5. [`data()` completo](#5-data-completo)
6. [`filters` e `computed`](#6-filters-e-computed)
7. [`methods` (todos)](#7-methods-todos)
8. [`created()`: timers e carga inicial](#8-created-timers-e-carga-inicial)
9. [Fluxo de seleção de odd (`apostar`, `mJogo`, `tempo`)](#9-fluxo-de-seleção-de-odd-apostar-mjogo-tempo)
10. [Como o bilhete é montado](#10-como-o-bilhete-é-montado)
11. [`finalizarAposta` e respostas](#11-finalizaraposta-e-respostas)
12. [Contrato de DOM: o que o JS exige no markup](#12-contrato-de-dom-o-que-o-js-exige-no-markup)
13. [Constantes/valores injetados pelo Twig](#13-constantesvalores-injetados-pelo-twig)
14. [Diferenças entre base, site9 e o que o site11 precisa](#14-diferenças-entre-base-site9-e-o-que-o-site11-precisa)
15. [Armadilhas ao reescrever o markup](#15-armadilhas-ao-reescrever-o-markup)

---

## 1. Como o app é carregado e instanciado

### 1.1 No tema base (`website/page/apostar.twig`)

O template define os blocos `bilhete`, `jogos`, `modalCotacoes`, `main` e `script`. No `block main`:

```twig
<div id="pg-aposta" v-cloak>
   ...
   {{ block('jogos') }}        {# coluna central #}
   {{ block('bilhete') }}      {# coluna direita (cupom) #}
</div>
```

E o app em si é instanciado **no bloco `script`** (linhas 340-991):

```twig
{% block script %}
    <script src="node_modules/vue/dist/vue.min.js"></script>
    <script src="node_modules/axios/dist/axios.js"></script>
    <script src="node_modules/lodash/lodash.min.js"></script>

    {% set _sliderBanners = [] %}
    {% for _b in banners('site-slideshow', null, null, 10) %}
        {% set _sliderBanners = _sliderBanners|merge([{ id: _b.getId(), title: ..., link: ..., target: ...,
            imgDesktop: ..., imgMobile: ... }]) %}
    {% endfor %}

    <script type="text/javascript">
        var app = new Vue({
            el: '{{ selector|default("#website") }}',
            delimiters: ['${', '}'],
            created() { ... },
            data() { ... },
            filters: { maskReal(value) { ... } },
            computed: { ... },
            methods: { ... }
        });
    </script>
{% endblock %}
```

Pontos-chave:

- `el: '{{ selector|default("#website") }}'` → o elemento raiz é `#website` (definido no layout). O `{% set selector = '#website' %}` pode ser feito na página para deixar explícito.
- `delimiters: ['${', '}']` → **Twig usa `{{ }}`; o Vue usa `${ }`**. Todo binding reativo no markup é `${...}` (interpolação) ou atributos `v-model`, `v-bind:`, `v-on:`/`@`, `v-if`, `v-for`, `:class`, etc.
- `var app` é **global** (`window.app`), usado por `onclick` inline (tema site9) e por scripts de página.
- O layout também usa `{% if pageAposta %}v-cloak{% endif %}` no `#website` (site9: linha 55; base: linha 22) — `pageAposta` é variável do `apostarController::indexAction`.

### 1.2 No tema site9 (`website9/page/apostar.twig`)

O markup é reescrito, mas a lógica é herdada:

```twig
{% block script %}
    {% set selector = '#website' %}
    {{ block('script', 'website/page/apostar.twig') }}   {# injeta o <script> do Vue + var app #}

    <script>
        if (typeof app !== 'undefined') {
            app.$watch('aposta.jogos.length', function (newVal) { /* badge #leonMobileBadge */ });
        }
        /* carrossel #homeHeroCarousel / #homeDots (jQuery, 5s, pause no hover, swipe) */
    </script>
{% endblock %}
```

Isso funciona porque o Twig 2.4.8 suporta `block(name, template)` (ver `arquitetura.md` §7.2). O `{% set selector = '#website' %}` **precisa** vir antes do `block(...)` para que o script herdado use `el: '#website'` (o `default` cobre o caso de esquecer, mas melhor não depender).

> **Para o site11**: mantenha exatamente esse padrão (ou copie o bloco `script` inteiro para dentro do tema, se preferir independência — mas aí você “congela” uma cópia de ~650 linhas).

---

## 2. Dependências JS e ordem de carregamento

Dentro do bloco `script` do apostar (base, `website/page/apostar.twig:342-344`):

```html
<script src="node_modules/vue/dist/vue.min.js"></script>     <!-- Vue 2.5.16 -->
<script src="node_modules/axios/dist/axios.js"></script>     <!-- axios 0.18.0 -->
<script src="node_modules/lodash/lodash.min.js"></script>    <!-- lodash 4.17.10 (carregado, NÃO usado no app) -->
```

Caminhos **relativos** — funcionam porque `seo_header()` imprime `<base href="{base_url()}/">` (`app/helpers/Seo.php:239-240`). Não use caminhos absolutos de sistema.

O layout (site9) já carregou, **antes** do bloco `script` da página:
`jquery` (via `seo_header`), `popper`, `bootstrap 4.1.1`, `sweetalert 2.1.0`, `jquery.mask`, `jquery.form`, `jquery.serializeObject`, `fastclick`, `modernizr`, `mask.js`, `string.js` (`String.prototype.toReal`), `owl.carousel2`, `clipboard`, `admin.js` (fornece `url()`, `$.adminPage`), `js/site.js`.
Portanto: `$`, `swal`, `url()`, `toReal()` e `$.adminPage` já existem quando o app Vue monta.

---

## 3. Contrato da resposta `GET /apostar/jogos`

Chamada (base, `website/page/apostar.twig:396-410`):

```js
axios.get(url('apostar/jogos'))
    .then((response) => {
        this.grupos  = response.data.grupos;   // objeto {id: titulo}
        this.cotacoes = response.data.cotacoes; // array de cotações (mercados)
        this.paises  = response.data.paises;    // array de países → campeonatos → jogos
        this.datas   = response.data.datas;     // array de datas disponíveis
    });
```

Servidor: `apostarController::jogosAction` (`app/modules/website/controllers/apostarController.php:64-76`):

```php
return [
    'cotacoes' => $this->cotacoes(),        // SELECT em sis_cotacoes (status=1)
    'grupos'   => CotacoesModel::GRUPOS,    // const [1..9]
    'paises'   => $jogos,                   // $this->jogos($datas)
    'datas'    => is_array($datas) ? array_values($datas) : [],
];
```

`APP::Initialize` serializa esse array em JSON (`Content-Type: application/json`).

### 3.1 Exemplo real (capturado do site em 2026-10-30, recortado)

```json
{
  "cotacoes": [
    {"id":1,"sigla":"CASA","title":"Casa","campo":"casa","cor":"#008040","grupo":1,"principal":1},
    {"id":3,"sigla":"EMP","title":"Empate","campo":"empate","cor":"#ff8000","grupo":1,"principal":1},
    {"id":2,"sigla":"FORA","title":"Fora","campo":"fora","cor":"#000080","grupo":1,"principal":1}
  ],
  "grupos": {"1":"Resultado da partida","2":"Duplas","3":"Gol e Meio","4":"Placar exato",
             "5":"Marcam","6":"Gols da partida","7":"Paridade","8":"Condições","9":"Intervalo / Final"},
  "datas": [
    {"data":"2026-10-31","title":"Jogo de amanhã 31/10"}
  ],
  "paises": [
    {
      "id": 49,
      "title": "Inglaterra",
      "img": "http://host/imagens/20190425_5cc22a28702a9.png",
      "campeonatos": [
        {
          "id": 7435,
          "title": "Premier League",
          "pais": 49,
          "paisImg": "http://host/imagens/20190425_5cc22a28702a9.png",
          "jogos": [
            {
              "id": 3506,
              "campeonatoId": "7435",
              "pais": 49,
              "campeonato": "Premier League",
              "timecasaId": 5820,
              "timeforaId": 16874,
              "casa": "Chelsea",
              "fora": "Man United",
              "data": "2026-10-31",
              "hora": "09:30:00",
              "cotacoes": {
                "90": {"casa":1.818,"empate":3.667,"fora":3.6,"dplcasa":1.25,"cof":1.25,
                       "dplfora":1.786,"gmais4":2.2,"gmenos4":1.572,"amb":1.5,"ambn":2.429},
                "pt": [],
                "st": []
              },
              "dateTime": "2026-10-31T09:30:00-03:00",
              "imgCasa": "http://host/imagens/times/chelsea.png",
              "imgFora": "http://host/imagens/times/manchester-united.png",
              "paisImg": "http://host/imagens/20190425_5cc22a28702a9.png"
            }
          ]
        }
      ]
    }
  ]
}
```

### 3.2 Semântica dos campos

**`cotacoes[]`** (um item por mercado — `CotacaoVO`/linha de `sis_cotacoes`):

| Campo | Tipo | Observação |
|---|---|---|
| `id` | int | id da cotação em `sis_cotacoes` |
| `sigla` | string | ex.: `CASA`, `EMP`, `FORA`, `DPL CASA` |
| `title` | string | rótulo exibido (ex.: “Casa”, “Dupla chance 1X”) |
| `campo` | string lower | **chave usada em `jogo.cotacoes[tempo][campo]`** — é o que vai no payload (`cotacao`) |
| `cor` | string hex | cor do admin |
| `grupo` | int | 1..9 → agrupa no modal de mais cotações |
| `principal` | int `1` (ou 0) | marca os mercados exibidos na linha (1 X 2). Comparação no JS é `c.principal == '1'` (loose equality: `1 == '1'`) |

**`grupos`**: objeto `{ "1": "Resultado da partida", ... }` (const `CotacoesModel::GRUPOS`, `app/models/CotacoesModel.php:11-21`). O `computed cotacoesGrupos` converte para `[{id, title}]`.

**`datas[]`**: `{data: 'Y-m-d', title: 'Jogos de hoje dd/mm' | 'Jogo de amanhã dd/mm' | 'Jogos de dd/mm'}` — construído no `apostarController::jogos()` (`:148-154`), ordenado por data (`ksort`), inclui **apenas datas que têm jogo**.

**`paises[]`** (3 níveis):

- `pais`: `{id, title, img, campeonatos[]}`. `id=0` é o balde “Outros” (campeonatos sem país cadastrado); `img` = bandeira (`source_images('default.jpg')` no caso “Outros”).
- `campeonato`: `{id, title, pais (int), paisImg, jogos[]}`.
- `jogo`: `{id, campeonatoId, pais, campeonato, timecasaId, timeforaId, casa, fora, data, hora, cotacoes, dateTime, imgCasa, imgFora, paisImg}`.
  - `data` = `Y-m-d`; `hora` = `H:i:s` (o JS exibe `hora.replace(/:00$/, '')`);
  - `dateTime` = ISO 8601 com timezone (`date('c', strtotime(...))`) — usado por `jogoAberto`/`getMatchCountdown`; **preferido**; se ausente o JS faz fallback montando `new Date(ano, mês-1, dia, h, m, s)`;
  - `cotacoes` = objeto por **tempo** (`"90"`, `"pt"`, `"st"`) → `{campo: odd}`; **arrays vazios** em `pt`/`st` quando a API não manda mercados de 1º/2º tempo;
  - `imgCasa`, `imgFora` = URL ou `null` (nulo quando a personalização desliga escudos — `TeamBadgeHelper::getTeamBadge` respeita `dados.getExibirEscudos()`);
  - `paisImg` = bandeira do campeonato/pais (sempre string).

**Filtro de jogos no servidor** (`apostarController::jogos()`, `:94-243`): só `sis_jogos.status=1`, times e campeonatos ativos, e `data/hora > agora + (minutosJogo + 5 min)`. Países/campeonatos ordenados com Brasil primeiro. **A lista chega “achatada” por campeonato** — o front é que filtra por data (`findData`) e por janela de fechamento (`jogoAberto`).

---

## 4. Contrato do `POST /apostar/apostar`

Origem: `finalizarAposta()` (`website/page/apostar.twig:905-988`).

### 4.1 Request (JSON via axios, `Content-Type: application/json`)

```js
axios.post(url('apostar/apostar'), {
    valor: this.valorAposta,          // string/number do input
    cliente: this.cliente,            // '', opcional (nome do cliente p/ pré-bilhete)
    telefone: this.telefone,          // '' (nunca preenchido no markup base/site9)
    revendedor: this.revendedor,      // '' (idem)
    jogos: [
        { jogo: v.jogo.id, tempo: v.tempo, cotacao: v.cotacao.campo, cotacaoId: v.cotacao.id || null }
    ]
});
```

> `input.php:5-10` lê `php://input` quando `$_POST` está vazio e o body é JSON → funciona sem `ajax_serialize`.

### 4.2 Validação no servidor (`apostarController::apostarAction`, `:291-403`)

1. `Conn::startTransaction()`.
2. Para cada item: exige `jogo`, `cotacao`, `tempo`; busca `JogosModel::getByLabel('id', jogo)` e `CotacoesModel::getAtivaBySelecao($cotacao, $cotacaoId ?? null)` (`CotacoesModel.php:71-105`: resolve por **campo**, depois por **sigla**, depois por **id** se `cotacaoId` válido; só cotações ativas).
3. Erros → `Exception` (rollback). Mensagens:
   - “Seleção inválida. Atualize a página e escolha as cotações novamente.”
   - “Jogo inválido” / “Cotação inválida”
   - “Jogo `Casa x Fora` não está mais recebendo apostas”
   - “A cotação selecionada não está mais disponível. Atualize a página e escolha outra opção.” (odd re-lida do `jogo.cotacoes[tempo][campo]` e precisa ser **> 1**)
4. Cria `ApostaJogoVO` com `user, jogo, valor, tempo, tipo (campo), cotacaovalor (odd atual), cotacaotitle, cotacaogrupo, cotacaosigla, jogos (qtd), cotacaocampo`.
5. Cria `ApostaVO`: `valor` (`Number::float`), `data`, e:
   - **logado**: `user`, `gerente`, `apostadorNome` (POST `cliente` ou nome do user), `apostadorTelefone` (POST `telefone` ou do user), `apostadorRevendedor` (POST `revendedor` ou do user), `status = STATUS_ATIVA (1)`;
   - **não logado**: `apostadorNome` (POST `cliente` ou `'Cliente'`), telefone/revendedor do POST, `status = STATUS_AGUARDANDO_PAGAMENTO (2)`.
6. `ApostasModel::apostar($aposta, $apostaJogos)` (`ApostasModel.php:449+`) — valida mín/máx de valor, `minJogos`, saldo (logado), jogo repetido, `aceitaApostas`, limites do jogo/limite de apostas, cotação mínima variável; gera token/código.
7. Se logado: `HistoricoBancarioModel::add($user, -$valor, "Pagamento de aposta #id", ...)` e `ApostasModel::pagarComissoes($aposta)`.
8. `Conn::commit()`.

### 4.3 Response (JSON)

```jsonc
// logado (result=1)
{
  "message": "Aposta confirmada e saldo atualizado.",
  "logged": true,
  "result": 1,
  "saldo": 123.45,                          // getCredito() numérico
  "historico": "http://host/apostas"        // url('apostas')
}

// não logado (pré-bilhete, result=1)
{
  "message": "Pré-bilhete gerado com sucesso. Procure um cambista para validar a aposta.",
  "logged": false,
  "result": 1,
  "codigo": "ABCD123XYZ",                   // getCodigoBilhete()
  "share": "https://api.whatsapp.com/send?text=<urlBilhete>",
  "link": "http://host/apostar/imprimir/<token>"
}

// erro (Exception → APP::Initialize converte)
{ "message": "Saldo insuficiente para concluír a aposta", "result": <code do Exception> }
```

O `result` de erro é o **código da Exception** (normalmente `0`), por isso o JS testa `e.result == 1` e no `else` mostra `swal('Aviso!', e.message, 'warning')`.

---

## 5. `data()`

Definido em `website/page/apostar.twig:413-451`.

| Chave | Valor inicial | Origem/Twig | Usada por |
|---|---|---|---|
| `valorAposta` | `{{ apostaMinima }}` (int do controller) | Twig inline | input do cupom, `getPremio`, `getCotacaoMinima` |
| `agora` | `Date.now()` | JS | `jogoAberto`, `getMatchCountdown` |
| `findCampeonato` | `'*'` | JS | filtros (`setCampeonato`, `filterCampeonato`) |
| `search` | `''` | JS | busca (`filterJogo`), input |
| `findData` | `'{{ hoje }}'` | Twig inline | filtro por data (`filterJogo`) |
| `findPais` | `'*'` | JS | filtro por país (`filterPais`, `setPais`) |
| `tempos` | `{90:'Jogo completo', pt:'Primeiro tempo', st:'Segundo tempo'}` | JS | abas do modal de cotações |
| `cliente` | `''` | JS | input opcional → payload |
| `telefone` | `''` | JS | payload |
| `revendedor` | `''` | JS | payload |
| `grupos` | `{}` | substituído por `GET /apostar/jogos` | `cotacoesGrupos` |
| `auth` | `{username:'', password:''}` | JS | `onAuth()` (não usado no site9; login é via layout) |
| `datas` | `{}` | `GET /apostar/jogos` | chips de data / tabs |
| `mJogo` | `{jogo: null, tempo: 90}` | JS | modal de mais cotações + `apostar` |
| `cotacoes` | `[]` | `GET /apostar/jogos` | `getCotacoesPrincipais`, `getCotacoesByGrupo` |
| `paises` | `[]` | `GET /apostar/jogos` | árvore de jogos |
| `aposta` | `{jogos: []}` | JS | cupom (única propriedade hoje: `jogos`) |
| `cotacaoMaxima` | `{{ cotacaoMaxima ?: 99999999 }}` | Twig | `getCotacao` (cap) e aviso em `apostar` |
| `cotacaoMinima` | `{{ cotacaoMinima ?: 1 }}` | Twig | `getCotacaoMinima` |
| `condicaoCotacao` | `{{ condicaoCotacao\|json_encode\|raw }}` | Twig (array) | `getCotacaoMinima` |
| `apostaMinima` | `{{ apostaMinima ?: 1 }}` | Twig | `:min` do input, reset após sucesso |
| `apostaMaxima` | `{{ apostaMaxima ?: 999999 }}` | Twig | `:max` do input |
| `retornoMaximo` | `{{ retornoMaximo ?: 99999999 }}` | Twig | `getPremio` (cap) |
| `minJogos` | `{{ minJogos ?: 1 }}` | Twig | `apostaValida`, aviso no cupom |
| `heroIndex` | `0` | JS | `heroSlides` (usado só no tema base) |
| `heroPaused` | `false` | JS | idem |
| `adminBanners` | `{{ _sliderBanners\|json_encode\|raw }}` | Twig (`banners('site-slideshow')` montado no bloco script) | idem |

`watch:` **não existe** no app base. O site9 adiciona `app.$watch('aposta.jogos.length', ...)` por fora (badge mobile).

---

## 6. `filters` e `computed`

### 6.1 `filters`

```js
filters: {
    maskReal(value) {           // 1.818 -> "1,82"
        if (!value) return '0,00';
        var n = parseFloat(value);
        if (isNaN(n) || n <= 0) return '0,00';
        return n.toFixed(2).replace('.', ',');
    }
}
```

Usado no markup como `|maskReal` (interpolação Vue): `${ getCotacao()|maskReal }`, `${mJogo.jogo.cotacoes[mJogo.tempo][c.campo]|maskReal}`, `${jogo.cotacoes['90'][c.campo] ? maskReal(jogo.cotacoes['90'][c.campo]) : '-'}`.
> Existe **também** um método `maskReal(value)` idêntico (`methods`), porque dentro de expressões `v-bind:title="'...' + maskReal(...)"` é mais explícito. Os dois convivem.

### 6.2 `computed`

| Computed | O que retorna | Usado no markup? |
|---|---|---|
| `cotacoesGrupos` | `[{id, title}]` derivado de `this.grupos` | site9: `v-for="grupo in cotacoesGrupos"` no modal |
| `cotacao` | `this.getCotacao()` | base (não no site9) |
| `premio` | `this.getPremio().toString().toReal()` — **string BR** (`'1.234,56'`, via `String.prototype.toReal` de `cdn/js/string.js`) | cupom |
| `premioRevenda` | `(getPremio() * (100 - {{ premioRevenda }}) / 100).toString().toReal()` | base (não no site9) |
| `valorCotacao` | `getCotacao().toString().toReal()` | cupom (“Cotação Total”) |
| `apostaValida` | `aposta.jogos.length >= minJogos && getCotacao() >= getCotacaoMinima()` | botão “Fazer Aposta” (`:disabled` / `v-bind:disabled`) |
| `featuredHeroGames` | até 4 `{jogo, campeonato}` a partir de `getCampeonatos()` | base (hero) |
| `heroSlides` | mistura jogos + `adminBanners` em slides `{type:'game'\|'banner', ...}` | base (hero) |

Atenção: `premio`/`valorCotacao` usam `String.prototype.toReal` — se alguém remover `cdn/js/string.js` do layout, esses dois quebram (`.toReal is not a function`).

---

## 7. `methods` (todos)

### 7.1 Hero (usados apenas pelo markup do tema base; site11 pode descartar se não usar)

| Método | Assinatura | Comportamento |
|---|---|---|
| `prevHeroSlide()` | — | `heroIndex = (heroIndex - 1 + total) % total` |
| `nextHeroSlide()` | — | `heroIndex = (heroIndex + 1) % total` |
| `setHeroSlide(idx)` | idx:int | `heroIndex = idx` |
| `pauseHero(val)` | val:boolean | `heroPaused = val` |

### 7.2 Utilidades

| Método | Assinatura | Comportamento |
|---|---|---|
| `maskReal(value)` | value | idêntico ao filter (para uso em expressões) |
| `getMatchCountdown(jogo)` | jogo | retorna `{val1,lbl1,val2,lbl2,val3,lbl3}` (dias/horas/min) com base em `jogo.dateTime` e `this.agora`; se já começou devolve `AO VIVO / EM JOGO / 90'`; nunca lança (try/catch). **Não é usado no markup do site9** |
| `onAuth()` | — | `axios.post('entrar/login/insert', this.auth)`; se `result==1` → `location.reload()`; senão alert. **Não usado no site9** (login é do layout) |

### 7.3 Cálculo financeiro/cotação

| Método | Retorna | Regra |
|---|---|---|
| `getCotacao()` | float | Produto das odds **atuais** de todas as seleções: `∏ parseFloat(v.jogo.cotacoes[v.tempo][v.cotacao.campo])`, com `Math.min(produto, cotacaoMaxima)`; ignora seleções sem cotação válida |
| `getCotacaoMinima()` | float | Começa em `cotacaoMinima`; percorre `condicaoCotacao` (`[{min,max,cotacao}]`) e, se `valorAposta` está em `[min,max]` (e `cotacao>0`), usa `value.cotacao` |
| `getPremio()` | float | `cotacao > 1 ? Math.min(valorAposta * cotacao, retornoMaximo) : 0` |

### 7.4 Cotações/mercados

| Método | Retorna | Regra |
|---|---|---|
| `getCotacoesPrincipais()` | array | `cotacoes.filter(c => c.principal == '1')` (hoje 3: casa/empate/fora) |
| `totalCotacoes(cotacoes)` | int | Conta odds `> 1` em **todos** os tempos (`Object.values` de `cotacoes` → de cada tempo) |
| `getCotacoesByGrupo(grupo, tempo)` | array | `grupo` pode ser `{id,title}` ou id; filtra `cotacoes` com `c.grupo == grupoId` **e** `mJogo.jogo.cotacoes[tempo][c.campo] > 1`; usa `mJogo.tempo` se `tempo` omitido |

### 7.5 Árvore de jogos/filtros

| Método | Retorna | Regra |
|---|---|---|
| `getPaises()` | array | `paises.filter(filterPais)` |
| `getCampeonatos(pais, enableFilter)` | array | Se `pais`: `pais.campeonatos` filtrados por `filterCampeonato` quando `enableFilter !== false`. Se `pais` nulo: percorre `(enableFilter === false ? this.paises : this.getPaises())`. **Obs.:** o markup do site9 chama `getCampeonatos(null, false)` (todos, sem filtros) e `getCampeonatos(null, true)` (lista filtrada) |
| `getJogos(campeonato)` | array | `campeonato.jogos.filter(filterJogo)` |
| `checkFiltros()` | bool | `false` quando `!findData \|\| findData == hoje` **e** `findPais == '*'` **e** `findCampeonato == '*'`; `true` caso contrário (usado p/ `:class="{active: !checkFiltros()}"`) |
| `filterPais(pais)` | bool | `findPais == '*'` ou igual a `pais.id`, **e** `getCampeonatos(pais, true).length > 0` |
| `filterCampeonato(campeonato)` | bool | `findCampeonato == '*'` ou igual a `campeonato.id`, **e** `getJogos(campeonato).length > 0` |
| `filterJogo(jogo)` | bool | `jogoAberto(jogo)` **e** `jogo.data == findData` (se `findData`) **e** (`search` vazio ou `casa`/`fora` casa com RegExp `i`) |
| `jogoAberto(jogo)` | bool | `false` se `dateTime` inválido; limite = `agora + ((minutosJogo + 5) * 60000)`; retorna `dtJogo > dtLimite`. `minutosJogo` é injetado pelo Twig: `parseInt({{ dados.getMinutosJogo() }}) \|\| 0` |
| `setPais(pais)` | — | `findPais = pais.id` (ou `'*'`), `findCampeonato = '*'`, `findData = hoje` |
| `setCampeonato(campeonato)` | — | remove classes `show-menu show-filtros` do `<html>`; `findCampeonato = campeonato.id` (ou `'*'`); se `findPais` não for `'*'` nem `campeonato.pais` → `findPais='*'`; `findData = hoje` |
| `setData(data)` | — | remove `show-menu show-filtros`; zera `findCampeonato`/`findPais`; `findData = data \|\| hoje` |

### 7.6 Modal / seleção / cupom

| Método | Comportamento |
|---|---|
| `maisCotacoes(jogo)` | `mJogo.jogo = jogo; mJogo.tempo = 90; $('#modal-cotacoes').modal('show')` |
| `apostar(jogo, tempo, cotacao)` | seleção de odd — detalhado na §9 |
| `rmAposta(index)` | remove `aposta.jogos[index]`, zera `jogo.apostaEm` e `jogo.outras` |
| `limpaAposta()` | zera flags de todos os jogos, `aposta.jogos = []` e `$(".bilhete").removeClass('show')` |
| `imprimir(campeonato)` | `window.open(url('apostar/imprimirTabela') + '?campeonato=' + id)` ou com `?campeonato=&data=&search=` |
| `finalizarAposta()` | monta payload, POST, trata respostas — §11 |

---

## 8. `created()`: timers e carga inicial

`website/page/apostar.twig:363-412`:

1. `$('html').addClass('loading')` — overlay de `css/loading.css` (`html.loading:before`).
2. **Timer 15s** — atualiza `this.agora` e varre o cupom de trás para frente removendo seleções de jogos que já fecharam (`!this.jogoAberto(item.jogo)`), limpando `apostaEm`/`outras`.
3. **Timer 5s** — só no tema base (`heroSlides`/`heroPaused`); no site9 o carrossel é jQuery no script extra.
4. Varredura inicial do mesmo tipo (cupom pode vir vazio — não há persistência em `localStorage`, apesar do `removeItem('apostaJogos')` no sucesso).
5. `axios.get(url('apostar/jogos'))` → popula `grupos`, `cotacoes`, `paises`, `datas`; no `.finally` (encadeado no `.then`) remove `loading` e chama `$(window).resize()`.

Se a página for reescrita sem `#website` ou sem o layout do tema, o `el` do Vue não existiria e o carregamento dos jogos nunca apareceria (a tela mostraria só o overlay/spinner).

---

## 9. Fluxo de seleção de odd (`apostar`, `mJogo`, `tempo`)

Assinatura: `apostar(jogo, tempo, cotacao)` — `website/page/apostar.twig:824-881`.

`tempo`: `'90'` (jogo completo) | `'pt'` (1º tempo) | `'st'` (2º tempo) — **string**; o `data()` de `mJogo.tempo` começa `90` (number) e o markup alterna entre `90` e `'pt'/'st'` (o JS concatena tudo em string: `tempo + cotacao.campo`).
`cotacao`: objeto de `this.cotacoes` (`{id, sigla, title, campo, grupo, principal}`).

Algoritmo:

```
1. guarda: sem jogo/campo → return
2. jogoAberto(jogo)? senão swal('Aviso!','Este jogo não está mais recebendo apostas.','warning'); return
3. existe odd para jogo.cotacoes[tempo][cotacao.campo] > 1? senão return (clique feito em mercado vazio)
4. apostaEm = tempo + cotacao.campo            // ex.: '90casa', 'ptempate'
5. SE o jogo já tem seleção (jogo.apostaEm):
     a. remove TODAS as entradas do cupom daquele jogo (splice por jogo.id)
     b. se clicou na MESMA seleção → jogo.apostaEm = null (toggle off) e para
     c. se clicou em OUTRA seleção → apostaEm=null e chama apostar(...) de novo (re-seleciona)
6. SENÃO:
     a. se getCotacao() == cotacaoMaxima → swal('Aviso!','Sua aposta já atingiu a cotação máxima.','warning') e para
     b. limpa do cupom entradas antigas do mesmo jogo ou de jogos que fecharam (para os outros jogos que fecharam, zera flags)
     c. jogo.apostaEm = apostaEm
     d. jogo.outras = !(cotacao.principal == '1' && tempo == '90')   // marca “mercado alternativo” selecionado
     e. aposta.jogos.push({ jogo, tempo, cotacao })
     f. $('#modal-cotacoes').modal('hide')
```

Regras de negócio visíveis no front:

- **1 seleção por jogo** (o servidor também rejeita jogo repetido);
- odd tem que ser **> 1**;
- `cotacaoMaxima` limita o **produto** das odds do cupom (não a odd individual);
- ao selecionar, guarda-se a **referência ao objeto `jogo`** (não uma cópia) — o cupom reflete odds atualizadas porque lê `v.jogo.cotacoes[...]` na renderização;
- `jogo.apostaEm` / `jogo.outras` são **propriedades novas** adicionadas ao objeto do jogo depois que o Vue já observou `paises` (em Vue 2 a atribuição é **não-reativa**; o certo seria `this.$set(jogo, 'apostaEm', valor)`). Na prática o `:class="{ active: jogo.apostaEm == ... }"` atualiza porque **toda** mutação de `apostaEm`/`outras` acontece junto de um `push`/`splice` no array reativo `aposta.jogos` (ou de um `mJogo` reativo), o que dispara o re-render e reavalia as expressões do template. **Não “conserte” isso sem testar** e, se o markup novo depender de uma flag reativa isolada, prefira `this.$set(...)` ou um estado em `data()`.

### 9.1 `mJogo` (modal “Mais Cotações”)

- Estrutura: `{ jogo: <objeto jogo>, tempo: 90|'pt'|'st' }`.
- Aberto por `maisCotacoes(jogo)` (clique no nome do jogo/linha) ou pelo badge “+N”.
- Markup do modal precisa: `id="modal-cotacoes"` (Bootstrap modal), tabs que fazem `mJogo.tempo = 90|'pt'|'st'`, `v-for="grupo in cotacoesGrupos"`, `v-for="c in getCotacoesByGrupo(grupo, mJogo.tempo)"`, clique `apostar(mJogo.jogo, mJogo.tempo, c)` e `active` comparando `mJogo.jogo.apostaEm == mJogo.tempo + c.campo`.
- Ao selecionar qualquer odd no modal, `apostar()` fecha o modal (`$('#modal-cotacoes').modal('hide')`).
- O site9 manteve o **mesmo `id`** (`website9/page/apostar.twig:610`), mas reescreveu o conteúdo com classes `leon-*`.

---

## 10. Como o bilhete é montado

`this.aposta = { jogos: [] }`; cada item é:

```js
{
  jogo: <referência ao objeto do jogo vindo de /apostar/jogos>,
  tempo: '90' | 'pt' | 'st',
  cotacao: <item de this.cotacoes>
}
```

O markup do cupom lê:

| Expressão | De onde |
|---|---|
| `${v.jogo.casa} x ${v.jogo.fora}` | jogo |
| `${v.cotacao.sigla \|\| v.cotacao.title}` | cotação (sigla tem prioridade no site9; base usa `title \|\| sigla`) |
| `${v.tempo == 'pt' ? '1ºT' : '2ºT'}` quando `v.tempo != '90'` | tempo |
| `${ maskReal(v.jogo.cotacoes[v.tempo][v.cotacao.campo]) }` | odd atual |
| `${aposta.jogos.length}` | contador |
| `${valorCotacao}` / `${premio}` | computeds |
| `v-model="valorAposta"` | input de valor |
| `v-bind:disabled="!apostaValida"` | botão finalizar |
| botão `{{ user ? 'Fazer Aposta' : 'Criar Pré-bilhete' }}` | Twig (server) — o texto muda conforme login |
| `v-on:click="rmAposta(i)"` | remover seleção |
| `v-on:click="finalizarAposta"` (ou `limpaAposta` se houver) | ações |

Regras: **não remova** `aposta.jogos.length` (usado por `v-if`/badge/watch), nem as chaves `jogo`/`tempo`/`cotacao` dos itens (o payload depende delas).

---

## 11. `finalizarAposta` e respostas

`website/page/apostar.twig:905-988`.

```
1. Monta payload (ver §4.1) — jogos via this.aposta.jogos.map
2. $('html').addClass('loading').removeClass('show-bilhete')
3. axios.post(url('apostar/apostar'), payload)
4. SUCESSO (e.result == 1):
     this.limpaAposta(); this.valorAposta = this.apostaMinima;
     window.localStorage.removeItem('apostaJogos');
     SE e.logged:
        swal({title:'Aposta confirmada!', text: e.message + '\nSaldo disponível: R$ ' + Number(e.saldo).toFixed(2).replace('.',','),
              icon:'success', buttons:{cancel:'Continuar apostando', confirm:'Ver minhas apostas'}})
          .then(verHistorico => { if (verHistorico && e.historico) window.location.href = e.historico; })
     SENÃO SE e.codigo && e.share:
        swal({title: $.trim(e.codigo.replace(/(.{3})/g, '$1 ')), text: e.message, icon:'success',
              button:{text:'Compartilhar whatsapp', closeModal:true, value:{link:e.link, share:e.share}}})
          .then(e => { if (e && e.share) window.open(e.share, '_blank'); })
     SENÃO: swal('Sucesso', 'Aposta realizada com sucesso!', 'success')
5. ERRO (e.result != 1):
     this.agora = Date.now();
     remove do cupom apenas jogos que fecharam (limpa apostaEm/outras);
     swal('Aviso!', e.message, 'warning')
6. .catch(err => console.log(err)); .then(() => $('html').removeClass('loading'))
```

Observações:

- `swal` é **sweetalert2 2.1.0** (global): aceita `(title, text, icon)` e objeto de config; `buttons` (v1) é aceito por compatibilidade no 2.x.
- `Number(e.saldo)` é o `Number` nativo (o `string.js` só estende `String`).
- `$('html').removeClass('show-bilhete')` / `.bilhete{...}` são **classes do tema base** (`site.css`/`bonanza-theme.css`); no site9 não existe `.bilhete` — a chamada é inofensiva.
- O `html.loading` controla o spinner global (`css/loading.css`), o mesmo usado no `created()`.
- Após o sucesso, o cupom é limpo e o valor volta ao mínimo; o saldo exibido no header só atualiza no recarregamento/navegação (o header é Twig, não Vue).

---

## 12. Contrato de DOM: o que o JS exige no markup

### 12.1 Obrigatório (quebra a página se faltar)

| Seletor/atributo | Onde é usado | Observação |
|---|---|---|
| `#website` | `el` do `new Vue` | vem do **layout**, não da página: site9 `layout.twig:55` (`<div id="website" {% if pageAposta %}v-cloak{% endif %}>`), base `layout.twig:22`. Sem ele o Vue não monta |
| `v-cloak` | `css/loading.css:7` e `darkmodern.css` têm `[v-cloak]{display:none!important}` | **todos** os blocos dinâmicos precisam de `v-cloak`, senão aparece `${...}` cru antes do Vue montar. Se o Vue quebrar (erro JS), o conteúdo com v-cloak fica **invisível** |
| `pageAposta` (Twig) | layout: adiciona `v-cloak` no `#website` | se renomear a variável no controller, o layout não coloca v-cloak |
| `#modal-cotacoes` | `maisCotacoes()` abre; `apostar()` fecha | id fixo; se renomear, atualize `maisCotacoes`/`apostar` (estão no script herdado) |
| `{{ block('script', 'website/page/apostar.twig') }}` + `{% set selector = '#website' %}` | carrega o app | se copiar o script, dispensa o block |
| `[v-cloak]` em `#website` | — | `v-cloak` no elemento raiz do Vue é o padrão dos 2 temas |

### 12.2 Seletores tocados por scripts do site9 (fora do app Vue)

| Seletor | Script | Efeito |
|---|---|---|
| `#leonMobileBadge` | `app.$watch('aposta.jogos.length')` (site9 `apostar.twig:686-693`) | mostra/oculta o badge do bottom nav (`#leonMobileBetslipModal` abre por `onclick` no layout) |
| `#leonMobileBetslipModal` | layout site9 (linha 258): `onclick="$('#leonMobileBetslipModal').addClass('open')"` | bottom sheet do cupom; CSS em `darkmodern.css:1613` |
| `#homeHeroCarousel`, `.leon-hero-slide`, `#homeDots .leon-carousel-dot` | JS do carrossel (site9 `:695-772`) | funções globais `homeNextSlide/homePrevSlide/homeGoToSlide/pauseHomeHero/startHomeCarousel` chamadas por `onclick`/`onmouseenter` no markup |
| `#dpSearchInput` | layout site9 (linha 88: `onclick="$('#dpSearchInput').focus();"`, “Ao Vivo”) | o input de busca do site9 tem esse id (`apostar.twig:425`) |
| `#modal-login` | layout site9 (`:298-309`) | modal Bootstrap + `adminPage` |
| `.admpage-*`, `.adminpage-container` | não presentes no apostar | — |

### 12.3 Classes que o JS referencia

| Classe | Onde | Efeito |
|---|---|---|
| `.bilhete` | base: `limpaAposta()` → `$(".bilhete").removeClass('show')`; header base toggle `$('.bilhete').toggleClass('show')` | no site9 **não existe** (usa `.leon-betslip-card` e o modal mobile) → chamada é no-op; se o site11 usar um painel `.bilhete` próprio, ganha o toggle de graça |
| `show-menu` / `show-filtros` | `setCampeonato`/`setData` fazem `$('html').removeClass(...)` | layout/menu mobile do site9 usa `html.show-menu` |
| `loading` / `page-loading` / `admpage-loading` | `created`, `finalizarAposta`, `adminPage` | overlay de `css/loading.css` |
| `show-bilhete` | `finalizarAposta` remove | classe do tema base |

### 12.4 Atributos Vue usados no markup (formas aceitas)

O Vue 2.5 aceita tanto a forma longa quanto o shorthand. O **markup do site9 mistura**:

```html
<!-- site9 (apostar) -->
<div class="leon-quick-strip" v-cloak>
  <div class="leon-quick-card active" v-on:click="setCampeonato()">
  <a class="leon-side-item" v-for="c in getCampeonatos(null, false)" :key="'side-' + c.id"
     v-on:click="setCampeonato(c)" v-bind:class="{ active: findCampeonato == c.id }">
  <button v-for="c in getCotacoesPrincipais()" :key="c.campo" class="leon-odd-btn"
          v-bind:class="{ active: jogo.apostaEm == '90' + c.campo }"
          v-on:click="apostar(jogo, '90', c)">
     <span class="leon-odd-val">${(jogo.cotacoes && jogo.cotacoes['90'] && jogo.cotacoes['90'][c.campo]) ? maskReal(jogo.cotacoes['90'][c.campo]) : '-'}</span>
  </button>
  <input type="number" v-model="valorAposta" v-bind:min="apostaMinima" v-bind:max="apostaMaxima" step="1.00" ...>
  <button v-bind:disabled="!apostaValida" v-on:click="finalizarAposta">{{ user ? 'Fazer Aposta' : 'Criar Pré-bilhete' }}</button>
</div>
```

O que **não pode mudar**:

- os nomes de propriedades/métodos referenciados (`aposta`, `aposta.jogos`, `valorAposta`, `findData`, `findCampeonato`, `findPais`, `search`, `mJogo`, `mJogo.jogo`, `mJogo.tempo`, `cotacoesGrupos`, `apostaValida`, `premio`, `valorCotacao`, `minJogos`, `hoje`…);
- os argumentos dos handlers: `setData('{{ hoje }}')`, `setCampeonato()`, `setCampeonato(c)`, `apostar(jogo,'90',c)`, `apostar(mJogo.jogo, mJogo.tempo, c)`, `rmAposta(i)`, `maisCotacoes(jogo)`, `finalizarAposta`, `limpaAposta`;
- a interpolação com delimitadores `${ ... }`;
- `:class="{active: ...}"` só afeta CSS; a **lógica** depende de `jogo.apostaEm`/`mJogo.tempo + c.campo` estarem corretos nas expressões;
- `v-model` do input de valor (string); o tipo `number` do input é opcional.

### 12.5 `onclick` inline (site9 hero) — `window.app`

O hero do site9 chama o Vue por **fora**:

```twig
<div class="leon-hero-teams-box"
     onclick="window.app && app.maisCotacoes ? app.maisCotacoes({{ g|json_encode|e('html_attr') }}) : null">
...
<button onclick="if(window.app && app.apostar){ app.apostar({{ g|json_encode|e('html_attr') }}, '90', {campo: 'casa', principal: '1', sigla: '1'}); }">
```

Notas:
- `g` vem de `destaquesJogosHome(3)` e tem **outra forma** que o jogo de `/apostar/jogos` (campos: `id, campeonatoId, campeonato, timecasaId, timeforaId, casa, fora, data, hora, dateTime, imgCasa, imgFora, cotacoes, oddCasa, oddEmpate, oddFora` — `JogosModel::getJogosDestaqueHome`, `app/models/JogosModel.php:491-545`);
- `app.apostar` aceita esse objeto porque só usa `id`, `cotacoes`, `dateTime`, `data`, `hora`;
- `json_encode` + `e('html_attr')` é obrigatório para embutir JSON em atributo HTML (aspas duplas escapadas);
- se o markup do site11 não tiver esses botões, o hero pode usar outro caminho (ex.: link para `/apostar`), mas o app continua funcionando.

---

## 13. Constantes/valores injetados pelo Twig

O bloco `script` herdado **depende de variáveis Twig renderizadas no momento da página**:

| Trecho Twig | Onde | Variável do controller |
|---|---|---|
| `el: '{{ selector\|default("#website") }}'` | `:361` | `selector` (set local) |
| `valorAposta: {{ apostaMinima }}` | `:415` | `apostaMinima` |
| `findData: '{{ hoje }}'` | `:419` | `hoje` |
| `cotacaoMaxima: {{ cotacaoMaxima ?: 99999999 }}` | `:440` | `cotacaoMaxima` |
| `cotacaoMinima: {{ cotacaoMinima ?: 1 }}` | `:441` | `cotacaoMinima` |
| `condicaoCotacao: {{ condicaoCotacao\|json_encode\|raw }}` | `:442` | `condicaoCotacao` (array) |
| `apostaMinima: {{ apostaMinima ?: 1 }}` | `:443` | `apostaMinima` |
| `apostaMaxima: {{ apostaMaxima ?: 999999 }}` | `:444` | `apostaMaxima` |
| `retornoMaximo: {{ retornoMaximo ?: 99999999 }}` | `:445` | `retornoMaximo` |
| `minJogos: {{ minJogos ?: 1 }}` | `:446` | `minJogos` |
| `adminBanners: {{ _sliderBanners\|json_encode\|raw }}` | `:449` | montado no próprio bloco a partir de `banners('site-slideshow')` |
| `(parseInt({{ dados.getMinutosJogo() }}) \|\| 0)` | `:759` (`jogoAberto`) | função Twig `dados()`/global `dados` |
| `setData('{{ hoje }}')`, `setData('{{ amanha }}')` | markup `main` | `hoje`, `amanha` |
| `{{ premioRevenda\|default(0) }}` | `:477` (`premioRevenda`) | `premioRevenda` |
| `{{ user ? '...' : '...' }}` | cupom | `user` |

**Consequência para o site11**: se o template do site11 copiar/reutilizar o script, ele **precisa ser renderizado pela `indexAction` do `apostarController`** (que fornece todas essas variáveis). Não mova o app para uma página sem essas variáveis, e não remova essas chaves do `$data` do controller base.

---

## 14. Diferenças entre base, site9 e o que o site11 precisa

| Item | Tema base (`website/page/apostar.twig`) | Tema site9 (`website9/page/apostar.twig`) | O que o site11 deve fazer |
|---|---|---|---|
| App Vue | definido no bloco `script` | reutiliza via `block('script', 'website/page/apostar.twig')` | **reutilizar** (recomendado) ou copiar |
| Elemento raiz | `#website` (layout base) | `#website` (layout site9) | manter `#website` no `layout.twig` do site11 |
| Hero | Vue (`heroSlides`, `adminBanners`) | Twig puro (carrossel jQuery próprio) + `app.apostar` no onclick | livre; se usar jQuery, mantenha as funções globais que o `onclick` referencia |
| Cupom | bloco `bilhete` (`.bilhete`) | bloco `cupom` (`.leon-betslip-card`) + modal mobile `#leonMobileBetslipModal` | escolher; **manter as expressões/bindings** listados na §12.4 |
| Modal cotações | bloco `modalCotacoes` (`#modal-cotacoes`, grid Bootstrap) | bloco `modalCotacoes` (`#modal-cotacoes`, `leon-*`) | manter o `id` ou ajustar as chamadas |
| Botões 1X2 da linha | `v-for="c in getCotacoesPrincipais()"` | idem | manter |
| “+N cotações” | `totalCotacoes(jogo.cotacoes) - getCotacoesPrincipais().length` | idem | manter |
| Login | `website/modals/login.twig` (adminPage) | modal inline no layout (adminPage) | livre |
| Extras site9 | — | `app.$watch` badge + carrossel jQuery | opcional |

**Requisito mínimo para “reescrever o markup mantendo a lógica”**:

1. layout com `<div id="website" {% if pageAposta %}v-cloak{% endif %}>`;
2. incluir o bloco `script` herdado com `{% set selector = '#website' %}`;
3. markup com os bindings das §12.4 (nomes de dados/métodos e argumentos);
4. `[v-cloak]` nos containers dinâmicos;
5. não remover `#modal-cotacoes` (ou atualizar os dois pontos do JS que o manipulam).

---

## 15. Armadilhas ao reescrever o markup

1. **Vue × Twig**: `${ }` é Vue, `{{ }}` é Twig. Se o markup novo tiver interpolação Vue com `{{ }}`, ela será consumida pelo Twig (vira vazio/erro de variável). Use sempre `${ }`.
2. **`v-cloak`**: se um container dinâmico não tiver `v-cloak`, o usuário vê `${...}`; se o app não montar, containers com `v-cloak` ficam invisíveis (`[v-cloak]{display:none!important}` em `loading.css`/`darkmodern.css`). Sintoma clássico: “a página abre só o header e nada mais” → erro de JS antes do `new Vue`.
3. **Não renomeie `#modal-cotacoes` sem atualizar o JS**: `maisCotacoes()` abre e `apostar()` fecha por esse id.
4. **Não remova `:disabled="!apostaValida"`** do botão finalizar: sem isso o usuário consegue POSTar com minJogos/cotação mínima inválidos (o servidor recusa, mas a UX fica ruim).
5. **`v-model="valorAposta"` é string** (`type=number` no DOM, mas sem `.number`): `getPremio`/`getCotacaoMinima` fazem `parseFloat`, então funciona; não troque para `v-model.number` sem revisar `Number::float`/`valorAposta` no reset (`this.valorAposta = this.apostaMinima`, number).
6. **Não remova `aposta.jogos`** nem a chave `jogos` do `data()` — o `v-if`, o badge, o watch do site9 e o payload dependem dela.
7. **`v-for` sem `:key`** funciona, mas o time de markup deve manter `:key` (site9 usa `:key="i"` no cupom, `:key="c.campo"` nas odds, `:key="jogo.id"` na linha) para evitar reuso errado de DOM quando odds/estado mudam.
8. **Objetos do hero** (`destaquesJogosHome`) e da lista (`/apostar/jogos`) têm formatos diferentes. Se o markup passar o objeto errado para `app.apostar(...)`, aparecem erros do tipo “jogo.cotacoes[...] undefined” (o método tem guardas e simplesmente não faz nada).
9. **Não coloque markup Vue dentro de `<form class="admpage-form">`** sem querer: o `$.adminPage` serializa todos os `[name]` do form; um `<input name="...">` decorativo dentro dele entra no `ajax_serialize` de outra tela. O cupom do apostar **não** é form (não usa adminPage).
10. **`swal` é global**: não redeclare `swal` nos scripts de página.
11. **Timers não são limpos** (15s/5s): navegar via SPA não existe (cada página é request novo), então não é problema; mas se o tema novo montar/desmontar o Vue, os `setInterval` continuam.
12. **`$('html').addClass('loading')`** exige `css/loading.css` no layout (o site9 carrega). Se o site11 não carregar, o overlay não aparece (mas os `finally` removem a classe de qualquer forma).
13. **`String.prototype.toReal`** (`premio`, `valorCotacao`) exige `cdn/js/string.js` no layout antes do app. O layout do site9 o carrega (linha 291).
14. **`axios` sem `baseURL`**: todas as URLs vêm de `url()` (JS) → se o tema for acessado por um caminho com prefixo de módulo (`/site11/apostar`), as chamadas saem com o mesmo prefixo automaticamente. Não hardcode `/apostar/...` no JS.
15. **Não remova o `block('modalCotacoes')`** (`website9/page/apostar.twig:526` / base `:182`): o modal é renderizado no fim do `main`; sem ele, `maisCotacoes` abre um modal inexistente (Bootstrap não faz nada) e o “+N cotações” fica sem efeito.
16. **Teste com jogo de `pt`/`st`**: `cotacoes['pt']`/`['st']` podem vir `[]` (sem mercados) — o modal deve mostrar “vazio” (o `getCotacoesByGrupo` retorna `[]` e o `v-if` esconde o grupo). Não force `mJogo.jogo.cotacoes[mJogo.tempo][campo]` sem checar `undefined` (o site9 usa `? :` em algumas expressões; em outras assume que existe — se o jogo tiver `pt` vazio, `${mJogo.jogo.cotacoes[mJogo.tempo][c.campo]}` não é renderizado porque o `v-for` interno já filtrou).
