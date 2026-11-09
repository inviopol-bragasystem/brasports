# Especificação visual do tema v4 (bet24hs.site/v4) → tema "bet24" (site11)

> **Para quem vai construir o tema:** este documento descreve o visual do site antigo
> (`https://bet24hs.site/v4/`) do ponto de vista de front-end, para ser **reimplementado em Twig**
> no sistema atual (`C:\wwwroot\banca`). O backend do v4 **não** é reaproveitável — só a
> aparência, as medidas, o markup/classes e os assets.
>
> Todo o material de referência está em:
>
> | O quê | Onde |
> |---|---|
> | Páginas HTML capturadas do site antigo | `C:\wwwroot\banca\_temp\v4ref\pages\*.html` |
> | CSS/JS originais do tema | `C:\wwwroot\banca\_temp\v4ref\css_v4_*.css`, `js_v4*.js` |
> | Assets baixados (fontes, logo, banners, JS, CSS) | `C:\wwwroot\banca\_temp\v4ref\assets\` |
> | Manifesto dos downloads | `_temp\v4ref\assets\assets-manifest.json` |
> | Recortes de markup prontos | `docs\bet24-ref\markup\` |
> | Mapa dos CSS/JS | [`assets-css.md`](assets-css.md) |
> | O que não dá para reaproveitar | [`gaps.md`](gaps.md) |

Índice

1. [Como o v4 carrega as folhas de estilo](#1-como-o-v4-carrega-as-folhas-de-estilo)
2. [Tokens de design](#2-tokens-de-design)
3. [Estrutura da página](#3-estrutura-da-página)
4. [Componentes](#4-componentes)
5. [Comportamentos de JavaScript](#5-comportamentos-de-javascript)
6. [Inventário de imagens](#6-inventário-de-imagens)
7. [Inventário de ícones](#7-inventário-de-ícones)
8. [Dados que o Twig precisa fornecer](#8-dados-que-o-twig-precisa-fornecer)
9. [Checklist de fidelidade](#9-checklist-de-fidelidade)
10. [Como usar os assets baixados](#10-como-usar-os-assets-baixados)

---

## 1. Como o v4 carrega as folhas de estilo

O `<head>` do v4 (reproduzido da home) carrega, **nesta ordem**:

```html
<link rel="stylesheet" href="/assets/css/all.min.css" />              <!-- Font Awesome Pro 5.11.2 + reset -->
<link rel="stylesheet" href="/assets/css/v4/fonts.css" />             <!-- @font-face do Google Fonts -->
<link rel="stylesheet" href="/assets/css/v4/app.css" />               <!-- layout e componentes base (minificado) -->

<style>
  /* Cores do painel. O v4.css liga estas variáveis ao restante do tema. */
  :root {
    --primary-color: #263e0f;
    --text-primary-color: #d4ec00;
    --secondary-color: #068a00;
    --secondary-text: #ffffff;
    --hover-color: #606060;
  }
</style>

<link rel="stylesheet" href="/assets/css/v4/v4.css" />                <!-- tema v4 por cima do app.css -->
```

**Por que a ordem importa:** `app.css` é o "layout" (tokens neutros, componentes genéricos).
`v4.css` é a **camada de marca**: troca `--brand` por `--primary-color`, aplica as cores do painel
no topo, no bilhete, nas abas, e redefine grids/medidas dos componentes
(ex.: `.match-row`). Se o tema novo copiar só o `app.css`, ele fica com as cores do layout padrão
(laranja `#e8542b`), não com o verde-limão do bet24.

Todos os `<style>` inline das páginas ficam **depois** do `app.css` e **antes** do `v4.css`
(exceto pequenos blocos vazios). As variáveis `--primary-color` e `--text-primary-color` são
geradas pelo PHP a partir das cores do painel (`cor_principal`, `cor_texto_primario`) — no tema
novo elas devem ser emitidas do mesmo jeito (ou fixadas no `:root` do tema novo).

Ordem dos scripts (fim do `<body>`):

```html
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="/assets/js/v4.js"></script>          <!-- comportamento geral do tema -->
<script src="/assets/js/tema-view.js"></script>   <!-- troca v4<->v5 (não usar) -->
<script src="/assets/js/v4-cupom.js"></script>    <!-- cupom flutuante -->
<script src="/assets/js/v4-home.js"></script>     <!-- só na home -->
```

No `<head>`, **antes de tudo**, o v4 aplica o tema salvo (sem flash):

```html
<script>
  // Aplica o tema salvo antes de desenhar a página (mesma chave do v3: localStorage "Tema").
  (function() {
    var escuro = false;
    try { escuro = localStorage.getItem('Tema') === 'dark'; } catch (e) {}
    document.documentElement.setAttribute('data-theme', escuro ? 'dark' : 'light');
  })();
</script>
```

e `<meta name="theme-color" content="#263e0f">`.

---

## 2. Tokens de design

### 2.1 Cores da marca (o que o painel define)

| Variável | Valor | Uso |
|---|---|---|
| `--primary-color` | `#263e0f` (verde-oliva escuro) | fundo do header, botões primários, item ativo, abas, selo CA, odds selecionadas, barra do cupom |
| `--text-primary-color` | `#d4ec00` (verde-limão) | texto/ícone sobre `--primary-color`; fundo do botão "Cadastrar" |
| `--secondary-color` | `#068a00` (verde) | botões "Adicionar/aceitar" de sugestões |
| `--secondary-text` | `#ffffff` | texto sobre `--secondary-color` |
| `--hover-color` | `#606060` | (legado) |

`v4.css` faz a ponte:

```css
html:root { --brand: var(--primary-color); }               /* tudo que é "cor da marca" no app.css */
:root { --primary-accent-color: var(--primary-color); }    /* marca usada como TEXTO sobre fundo neutro */
```

> **Atenção para o tema novo:** o verde-oliva `#263e0f` é escuro; o `--text-primary-color`
> (`#d4ec00`) é o que garante contraste em cima dele. Nunca use `#263e0f` como cor de texto
> sobre fundo escuro.

### 2.2 Tokens do tema — claro (padrão)

Definidos em `:root` de `app.css`. Estes são os valores "de layout" que sustentam os componentes:

| Token | Valor | Para que serve |
|---|---|---|
| `--brand` | `#e8542b` → **sobrescrito** por `--primary-color` (`#263e0f`) | cor da marca |
| `--brand-hover` | `color-mix(in srgb, var(--brand) 86%, #000)` | hover da marca |
| `--brand-soft` | `color-mix(in srgb, var(--brand) 12%, transparent)` | fundo suave |
| `--bg` | `#eaeef9` | fundo da página |
| `--surface` | `#ffffff` | cartões, header de painéis, modal |
| `--surface-2` | `#f7f9fc` | fundo de odd, aba inativa, bloco secundário |
| `--surface-3` | `#e2e8f8` | hover/badges/divisórias fortes |
| `--surface-4` | `#d3dcf0` | borda de elemento ativo |
| `--hover` | `#f3f6fc` | hover de linha de jogo |
| `--line` | `#e2e8f8` | bordas e divisórias |
| `--odd-border` | `#e2e8f8` | borda do botão de odd |
| `--text` | `#151923` | texto principal |
| `--muted` | `#526789` | texto secundário |
| `--price` | `#3a7824` | preço da odd (verde) |
| `--live` | `#e63c12` | "ao vivo" |
| `--up` / `--down` | `#1f9d55` / `#d93636` | variação |
| `--up-text` / `--down-text` | `#16a34a` / `#dc2626` | texto de variação |
| `--warn-text` | `#b45309` | aviso |
| `--info-text` | `#1d6fd1` | informação/código de bilhete |
| `--open-text` / `--open-bg` | `#0369a1` / `#e0f2fe` | aposta em aberto |
| `--error-bg` / `--error-text` | `#fde8e8` / `#b42318` | erro |
| `--best` | `#3a7824` | melhor odd |
| `--focus` | `color-mix(in srgb, var(--brand) 80%, #000)` | outline de foco |
| `--scrim` | `#080a1073` | fundo escurecido dos modais |
| `--scrim-blur` | `blur(3px)` | desfoque atrás dos modais |
| `--shadow` | `#1519231f` | sombra padrão |
| `--toast-bg` | `#ffffff` | toast |
| `--chosen` | `#ffffff` | fundo do tab ativo |
| `--chosen-shadow` | `0 1px 3px #1519231a` | sombra do tab ativo |
| `--home-glow` | `var(--brand)` | brilho verde no topo da home |
| `--glow-open` | `21 25 35` | brilho da odd que abriu |
| `color-scheme` | `light` | |

Tokens específicos do bloco "destaques" (`.highlight`) e da barra de placar (`--sb-*`):

| Token | Claro | Uso |
|---|---|---|
| `--hl-bg` | `#f1f3f7` | fundo do card de destaque |
| `--hl-bolt` | `#1519230d` | raios decorativos (mask) |
| `--hl-text` | `#151923` | texto |
| `--hl-muted` | `#526789` | texto secundário |
| `--hl-border` | `#dde2ec` | borda |
| `--hl-hover` | `#c5d0ea` | borda no hover |
| `--hl-cell` / `--hl-cell-hover` | `#ffffff` / `#e2e8f8` | fundo das odds dentro do destaque |
| `--hl-selected` | `var(--brand)` | odd selecionada no destaque |
| `--sb-field` | `#d6ddee` | campo do placar |
| `--sb-line` | `#fffffff2` | linha do placar |
| `--sb-glass` | `#efeffa99` | vidro |
| `--sb-badge` | `#ffffff` | selo |
| `--sb-shadow` | `#232b361a` | sombra |
| `--sb-divider` | `#15192314` | divisória |

### 2.3 Tokens do tema — escuro (`[data-theme=dark]`)

| Token | Escuro | | Token | Escuro |
|---|---|---|---|---|
| `--bg` | `#090915` | | `--text` | `#f5f7fa` |
| `--surface` | `#212536` | | `--muted` | `#9aa7c4` |
| `--surface-2` | `#2d374d` | | `--price` | `#a3d369` |
| `--surface-3` | `#3b4863` | | `--live` | `#ff5a36` |
| `--surface-4` | `#475674` | | `--up-text` | `#4ade80` |
| `--hover` | `#282d40` | | `--down-text` | `#f87171` |
| `--line` | `#2d374d` | | `--warn-text` | `#facc15` |
| `--odd-border` | `transparent` | | `--info-text` | `#6cb4ff` |
| `--chosen` | `#3b4863` | | `--open-text` | `#7dd3fc` |
| `--chosen-shadow` | `none` | | `--open-bg` | `#38bdf829` |
| `--error-bg` | `#3a1717` | | `--error-text` | `#fca5a5` |
| `--best` | `#a3d369` | | `--focus` | `color-mix(in srgb, var(--brand) 55%, #fff)` |
| `--glow-open` | `255 255 255` | | `--toast-bg` | `#0f1420` |
| `--shadow` | `#00000073` | | `--hl-bg` | `#0f1222` |
| `--hl-bolt` | `#ffffff0b` | | `--hl-text` | `#f8f4ef` |
| `--hl-muted` | `#f8f4efb8` | | `--hl-border` | `#1d2236` |
| `--hl-hover` | `#3b4863` | | `--hl-cell` | `#f8f4ef` |
| `--hl-cell-hover` | `#e9e2d8` | | `--hl-selected` | `var(--brand)` |
| `--sk` | `var(--surface)` | | `--sk-hi` | `var(--surface-2)` |
| `color-scheme` | `dark` | | | |

> **Importante:** `--brand` **não** muda no escuro. A cor da marca continua `#263e0f`;
> é o `--bg`/`--surface` que mudam. O header continua verde-oliva nos dois temas.
> O que muda no header é a cor do texto/ícone: `--text-primary-color` (`#d4ec00`) nos dois.

### 2.4 Tipografia

Carregada por `v4/fonts.css` (Google Fonts, `fonts.gstatic.com`), com **as 20 famílias/pesos**
abaixo (todas já baixadas em `assets/fonts/google/`):

| Família | Peso/estilo | Onde é usada |
|---|---|---|
| **Geist** | 400–800 (variável) | corpo do site, preços, botões, inputs |
| **Barlow Condensed** | 700 normal e **700 itálico** | títulos com "cara de site de aposta": `.page-title`, `.home-title`, `.brand-mark`, `.highlight-team`, `.match-tag` |
| **Bricolage Grotesque** | 700 | títulos grandes do cassino (`.cas-section-title`) |
| **Poppins** | 800 | título do banner de oferta (`.offer-banner-title`, 30px) |
| **Anton** | 400 | botão do banner de oferta (`.offer-banner-more`) |

Fonte do corpo (de `app.css`):

```css
body {
  margin: 0;
  font: 400 14px/1.4 Geist, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  color: var(--text);
  background: var(--bg);
  -webkit-font-smoothing: antialiased;
}
```

Escala de tamanhos que aparece no tema:

| Elemento | Tamanho | Fonte |
|---|---|---|
| `.page-title` (título de página) | 28px/1.1, **itálico 700, UPPERCASE**, `letter-spacing:.2px` | Barlow Condensed |
| `.home-title` (título de seção da home) | 24px/1.1, itálico 700, UPPERCASE | Barlow Condensed |
| `.highlight-team` (times no destaque) | 20px/1.1, itálico 700, UPPERCASE | Barlow Condensed |
| `.brand-mark` (logo em texto, não usada — usa imagem) | 26px, itálico 700 | Barlow Condensed |
| `.match-tag` (selos CA/PA) | 11px/1, **itálico 700**, UPPERCASE | Barlow Condensed |
| `.offer-banner-title` | 30px/0.92, 800, UPPERCASE | Poppins |
| `.offer-banner-text` | 15px/1.1, 700, UPPERCASE | Barlow Condensed |
| `.offer-banner-more` | 14px/1.1, 400 | Anton |
| `.cas-section-title` | 21px/24px, 700, `letter-spacing:-.6px` | Bricolage Grotesque |
| `.team` / `.team-name` | 14px | Geist |
| `.when-top` | 13px/550 | Geist |
| `.when-sub` | 11px | Geist |
| `.odd-label-text` | 12px | Geist |
| `.odd-price` | 13px/700, `letter-spacing:-.02em`, `font-variant-numeric: tabular-nums` | Geist |
| `.league-name` | 13px | Geist 550 |
| `.league-cols` | 11px/500 | Geist |
| `.topnav-link` | 13px/450, UPPERCASE | Geist |
| `.account-signin` / `.account-signup` | 13px/600 | Geist |
| `.v4-cupom-tipo` | 15px/750 | Geist |
| `.v4-cupom-odd` | 17px/800 | Geist |
| `.v4-cupom-linha-2` | 12.5px/600 | Geist |
| `.slip-head strong` | 15px | Geist |
| `.slip-tab` | 12px | Geist |
| `.v4-rodape` | 13px | Geist |
| `.v4-rodape h3` | 12px, UPPERCASE, `letter-spacing:.05em` | Geist |

### 2.5 Raio, sombra, espaçamento

**Raio** (muito consistente no tema):

| Raio | Onde |
|---|---|
| `6px` | `.slip-clear`, `.row-tags` pequenos, `.v4-cupom-selo` |
| `7px` | `.stake-field`, `.slip-tab` |
| `8px` | `.odd` (botão), `.v4-botao`/`.btn`, `.v4-campo`, `.v4-dia`, `.more-link`, `.chip` (não), ícone de notícia |
| `9px` | `.slip-tabs` |
| `10px` | `.league`, `.aside-card`, `.match-card`, `.market`, `.empty`, `.ticket-item`, `.slip-pick` |
| `12px` | `.banner img`, `.highlight`, `.modal-content`, `.v4-banners-trilho`, `.ticket`, `.v4-texto` (16 na verdade) |
| `14px` | `.v4-cupom` (topo), `.slip`, `.story-viewer` |
| `16px` | `.v4-texto`, `.v4-acu-card`, `.v4-acu-bloco` |
| `17px`/`19px` | `.account-signin`/`.account-signup` (pill), `.search-pill` (pill) |
| `999px` | `.chip`, `.league-count`, `.row-more`, `.sport-tab`, `.bets-tabs`, `.cas-side-link`, `.bottom-nav-switch` |

**Sombras**:

| Elemento | Sombra |
|---|---|
| `.topbar` | `0 2px 8px #00000040` |
| `.v4-cupom` / `.slip` | `0 -6px 30px var(--shadow, rgba(15,23,42,.18))` |
| `.modal-content` | `0 20px 50px var(--shadow)` |
| `.to-top` | `0 4px 14px var(--shadow), 0 0 0 1px var(--line)` |
| `.toast` | `0 10px 30px var(--shadow)` |
| `.competition/highlight` | `0 1px 3px var(--shadow)` (nos cards do acumuladão) |
| `.story-card` (anel) | `0 0 0 1px var(--bg), 0 0 0 3px var(--brand)` |
| `.cas-card-pic` hover | `transform: scale(1.05)` |

**Espaçamentos usados** (base 2/4/6/8/10/12/14/16/18/20/22/24/32px):

- `.topbar`: `height:60px; padding:0 20px; gap:32px`
- `.content`: `padding:16px 20px 48px`
- `.home`: `gap:22px 18px`
- `.home-main`: `gap:22px`
- `.home-aside`: `gap:12px`
- `.v4-cupom-barra`: `padding:11px 16px 12px`
- `.slip-head`: `padding:12px 14px 8px`
- `.slip-foot`: `padding:10px 14px`
- `.match-row`: `padding:12px 14px; gap:14px`
- `.league-head`: `padding:10px 14px; gap:14px`
- `.aside-card`: `padding:14px`
- `.v4-rodape`: `padding:28px 20px 24px`
- `.odd`: `min-height:38px; padding:6px 12px; gap:8px`
- `.home-section`: `gap:10px`
- `.v4-ligas`: `gap:10px`

### 2.6 Breakpoints

O tema usa **mobile-first invertido** (`max-width`), com estes pontos (do CSS de verdade):

| Breakpoint | O que muda |
|---|---|
| `1500px` | largura máxima do `.home`/`.list-layout`/`.v4-rodape-*` (1500px); cassino `1560px` |
| `1280px` | **a coluna direita some** (`aside` 280px) e vira gaveta lateral; `.home` passa a 2 colunas (`200px | 1fr`); botão do bilhete no topo aparece |
| `1240px` | grade do cassino wide 5→4 colunas |
| `1180px` | `.match-layout` (jogo) 2 colunas |
| `960px` | menu lateral some (`home-side`), `.home` e `.list-layout` viram 1 coluna; `.home-quick` aparece; rodapé `5 → 2` colunas; `.action-more` some |
| `900px` | cassino wide 4→3 colunas |
| `860px` | header vira `min-height:56px` e quebra linha; `.page-title` 24px; `.match-row` 2 colunas (52px | 1fr) e odds na linha toda; `.league-head` vira `1fr 20px`; `.search-pill` vira ícone 36px; `.carousel-btn` some; `.row-more` some |
| `700px` | **bottom-nav aparece**; `.topnav` some; `.content` ganha `padding-bottom` para a nav; `.v4-cupom` vira barra de ponta a ponta acima da nav; `.v4-roda` ganha `padding-bottom`; `--bottom-nav-h` definido |
| `560px` | modais `.v4-sheet` viram **bottom sheet** (sobem de baixo); cadastro vira 1 coluna; `.account-signin/.account-signup` 32px |
| `420px` | `.account-row` 1 coluna |
| `400px` | `.account-signup` some; botões do topo 11.5px |

Variáveis de altura/layout:

```css
:root { --topbar-h: 60px; }                     /* vira 56px em <=860px */
@media (width<=700px) {
  :root { --bottom-nav-h: calc(56px + env(safe-area-inset-bottom)); }
}
```

### 2.7 Movimento

Durações e curvas usadas (para manter a "sensação" do tema):

| Interação | Animação |
|---|---|
| Cupom entrando | `v4-cupom-sobe .38s cubic-bezier(.2,.9,.3,1.15)` |
| Cupom badge "pulando" | `v4-cupom-pulo .45s ease-out` |
| Odd mudou | `v4-odd-mudou 1.6s ease-out` (fundo da marca some) |
| Odd subiu/desceu | `roll-in-up/roll-out-up .45s cubic-bezier(.2,.8,.2,1)` |
| Bottom sheet | `v4-sobe .25s ease-out` |
| Gaveta/bilhete lateral | `transform .25s ease-out` + scrim `opacity .25s` |
| Hover de linha | `background-color` instantâneo (sem transição) |
| Skeleton | `v4-sk 1.3s ease-in-out infinite` |
| Bloco que chega (AJAX) | `v4-chega .35s ease-out` |
| Botão de odd | `transition: opacity .3s` |

Sempre respeitar `@media (prefers-reduced-motion: reduce)` desligando as animações.

---

## 3. Estrutura da página

### 3.1 Esqueleto

```
<div class="app">
  <header class="topbar"> ... </header>                       ← 60px, sticky, cor da marca
  <main class="content content-home">  |  <main class="content">
    <div class="home">                                          ← 3 colunas: 230 | 1fr | 280
      <nav class="home-side"> ... </nav>                        ← menu lateral
      <div class="home-top">  atalhos (mobile) + destaques + stories  </div>
      <div class="home-main"> banners + seções de jogos  </div>
      <div class="v4-scrim"></div>
      <section class="v4-cupom" id="v4Cupom"> ... </section>    ← bilhete flutuante (fixed)
      <aside class="home-aside v4-aside" id="v4Aside"> ... </aside>  ← minhas apostas + ofertas
    </div>
  </main>
  <footer class="v4-rodape"> ... </footer>
  <button class="to-top" id="v4Topo"> ... </button>
  <nav class="bottom-nav"> ... </nav>                            ← só <=700px
</div>
```

Páginas internas (ao vivo, próximos, esporte, campeonato) trocam `.home` por `.list-layout`:

```
<main class="content">
  <div class="list-layout">                ← 230px | 1fr | 280px
    <nav class="home-side"> ... </nav>
    <section class="list-page"> ... </section>
    <aside class="home-aside v4-aside"> ... </aside>
  </div>
</main>
```

### 3.2 Container, larguras e grids

| Container | Regra |
|---|---|
| `.home` | `display:grid; grid-template-columns:230px minmax(0,1fr) 280px; grid-template-areas:"side top top" "side main aside"; gap:22px 18px; max-width:1500px; margin:0 auto; align-items:start` |
| `.list-layout` | `grid-template-columns:230px minmax(0,1fr) 280px; grid-template-areas:"side main aside"; gap:18px; max-width:1500px; margin:0 auto` |
| `.content` | `padding:16px 20px 48px` (≤860: `12px 12px 40px`) |
| `.home-main` | `display:flex; flex-direction:column; gap:22px; min-width:0` |
| `.home-side` | `width:230px; position:sticky; top:76px; max-height:calc(100vh - 92px); overflow-y:auto; gap:2px` |
| `.home-aside` | `display:flex; flex-direction:column; gap:12px; align-self:start; width:280px` |
| `.cas-layout` | `grid-template-columns:250px minmax(0,1fr); gap:32px; max-width:1560px` |

### 3.3 Topbar / header

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ [logo 160x28]   Esportes  Ao vivo  Próximos jogos  Cassino ...   [busca] [V5] [tema] [bilhete] [Entrar][Cadastrar] │  60px
└─────────────────────────────────────────────────────────────────────────────┘
```

- `header.topbar`: `display:flex; align-items:center; gap:32px; height:60px; padding:0 20px;`
  `position:sticky; top:0; z-index:20; background:var(--brand)` (**na prática `#263e0f`**),
  `box-shadow:0 2px 8px #00000040`.
- `.brand` / `.brand-logo`: `app.css` define `height:28px; max-width:160px; object-fit:contain`
  e o `v4.css` acrescenta `max-height:40px; width:auto`. Efeito final: logo com 28px de altura
  (o arquivo original tem 319×122).
- `.topnav`: `display:flex; align-self:stretch; gap:22px; overflow-x:auto` (sem scrollbar).
- `.topbar-actions`: `display:flex; align-items:center; gap:4px; margin-left:auto`.
  - `.search-pill`: `width:240px; height:38px; border-radius:19px; background:#ffffff2e`
    (v4.css troca para `color-mix(--text-primary-color 18%, transparent)`); ≤860 vira 36×36.
  - `.topbar-icon`: `40x40` (36 em ≤860), círculo, hover `#00000026`.
  - `.topbar-bets`: ícone do bilhete + `.topbar-badge` (contador no canto).
  - `.account-signin`: borda 1px, `height:34px; border-radius:17px`, transparente.
  - `.account-signup`: fundo `--text-primary-color` (`#d4ec00`) e texto `--primary-color`.
- ≤700px: `flex-wrap:nowrap; gap:12px`, `.topnav` **oculta** (navegação vai para o bottom-nav),
  `box-shadow:none`.
- ≤560px: `padding-top:4px`; se o topnav voltar a aparecer ele vira linha própria (`order:3; width:100%; height:40px`).

Markup exato: [`markup/01-header-nav.html`](markup/01-header-nav.html).

### 3.4 Menu lateral da home (`.home-side`)

Conteúdo, na ordem:

1. Atalhos: **Ao vivo agora** (com `.side-link-dot` vermelho pulsante), **Jogos de hoje**, **Próximos jogos**, **Seja afiliado** (ícone de usuários).
2. `<h3 class="home-side-title">Dias</h3>` + `.v4-dias` (3 botões `.v4-dia` com mês e dia; ativo = fundo da marca).
3. `<h3 class="home-side-title">Importar jogo</h3>` + `.v4-consulta` (campo `.v4-campo` + botão `.v4-botao`).
4. `<h3 class="home-side-title">Esportes</h3>` + 8 `.side-link` com ícone SVG (18px) e nome.
5. `<h3 class="home-side-title">Principais ligas</h3>` (AJAX) — linhas `.side-link.side-competition` com bandeira 16px, nome, selo PA opcional e contador `.side-link-count`.
6. `<footer class="site-footer side-footer">` — “Regras · © 2026 Bet24hs.site · +18”.

Medidas: `.side-link { padding:8px 10px; border-radius:8px; font-size:14px; gap:10px }`,
hover `background:var(--surface)`, ativo com fundo `var(--surface)` + texto da marca.
`.home-side-title { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin:14px 8px 6px }`.

Markup exato: [`markup/03-sidebar-esportes.html`](markup/03-sidebar-esportes.html).

### 3.5 Área principal da home (`.home-main`)

Ordem dos blocos:

1. `stories` (só desktop aqui; `.stories-wide`) — faixa de stories.
2. `.v4-banners` — carrossel de banners promocionais (4 imagens).
3. `.home-section` “**Junte-se à ação** `(N)`”:
   - `.home-section-head` com título + `.toggle` (Ao Vivo / Próximos)
   - `.sport-bar.sport-bar-small` (abas de esporte)
   - `.action-list` (linhas `.action-row`, 20 por vez, via AJAX)
   - `<a class="more-link">Mais eventos</a>`
4. Blocos AJAX `superodds`, `superdicas`, `dicas` (vazios no momento da captura).
5. `.home-section` “**Mais jogos de `<dia>`**” (hidden até ter dados):
   `.filters > .chips.chips-scroll` (Hoje/Amanhã/depois/Próximos) + `.v4-ligas` (campeonatos).
6. `.home-section` “**Jogos de amanhã**” (hidden até ter dados) + `.v4-ligas`.

No celular (≤960px) o `home-top` recebe: `.home-quick` (atalhos horizontais), `destaques`
(carrossel de destaques) e `stories` (`.stories-phone`), e o `home-side` some.

### 3.6 Coluna direita e bilhete

**São duas coisas diferentes no v4** (importante para não errar no Twig):

1. **`aside.home-aside.v4-aside` (280px)** — no desktop guarda os cartões
   `.aside-card.bets-card` (**Minhas apostas**) e `.aside-card.offers-card` (**Ofertas**).
   Nas páginas em que não há nada para mostrar ela existe vazia com `.v4-aside-vazio`
   (some no desktop e não ocupa espaço).
2. **`section.v4-cupom` (bilhete flutuante, 360px, `position:fixed`)** — é o **bilhete de apostas**.
   Fica no canto inferior direito em **todas** as páginas, independente da coluna direita:
   - fechado: só a barra `.v4-cupom-barra` (ícone + tipo + cotação + seta; segunda linha com
     times + `+N` + selo CA). **Só aparece quando há seleção** (`data-qtd` > 0);
   - aberto (`.is-aberto`): mostra o corpo `.v4-cupom-corpo` com o `.slip-card` completo
     (cabeçalho, compartilhar, abas Simples/Dupla/Múltipla, lista de `.ticket-item`,
     valor, totais, botão **Apostar**) e um `.v4-scrim` escurece o fundo.
   - ≤1280px o ícone do bilhete no topo (`.topbar-bets`) abre a **gaveta da coluna direita**
     (`.v4-aside.is-aberto`), e o cupom continua flutuando;
   - ≤700px o cupom vira barra de ponta a ponta **acima do bottom-nav**.

Medidas do cupom:

```css
.v4-cupom {
  position: fixed; right: 16px; bottom: 0; z-index: 46;
  width: 360px; max-width: calc(100vw - 32px);
  max-height: calc(100vh - 90px);
  display: flex; flex-direction: column;
  background: var(--surface);
  border-radius: 14px 14px 0 0;
  box-shadow: 0 -6px 30px var(--shadow);
  overflow: hidden;
}
.v4-cupom[data-qtd="0"]:not(.is-aberto) { display: none; }
@media (width <= 700px) {
  .v4-cupom { left:0; right:0; width:auto; bottom: var(--bottom-nav-h, 56px); }
  .v4-cupom.is-aberto { bottom: 0; max-height: 88vh; }
}
```

Markup exato (com 1 seleção, vindo do próprio site): [`markup/05-bilhete-cupom.html`](markup/05-bilhete-cupom.html).
Cartões da coluna direita: [`markup/12-home-aside-cards.html`](markup/12-home-aside-cards.html).

### 3.7 Rodapé

`.v4-rodape` — 5 colunas com títulos em caixa alta (Sobre nós / Suporte / Regras / Principal /
Redes sociais), texto legal e copyright com selo +18.

```css
.v4-rodape { margin-top:8px; padding:28px 20px 24px; border-top:1px solid var(--line);
             background:var(--surface); color:var(--muted); font-size:13px; }
.v4-rodape-colunas { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:20px;
                     max-width:1500px; margin:0 auto; }
.v4-rodape h3 { margin:0 0 8px; color:var(--text); font-size:12px; text-transform:uppercase; letter-spacing:.05em; }
.v4-rodape-colunas a { display:block; padding:3px 0; }
.v4-rodape-legal, .v4-rodape-copy { max-width:1500px; margin:18px auto 0; font-size:12px; line-height:1.5; }
.v4-rodape-copy { display:flex; align-items:center; gap:10px; margin-top:10px; }
@media (width<=960px) { .v4-rodape-colunas { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (width<=700px) { .v4-rodape { padding-bottom: calc(var(--bottom-nav-h) + 24px); } }
```

Markup exato: [`markup/06-footer.html`](markup/06-footer.html).

### 3.8 Bottom-nav (mobile)

- Só existe em `<=700px` (`display:none` acima disso).
- `position:fixed; bottom:0; left:0; right:0; height:var(--bottom-nav-h); z-index:35;`
  `background:var(--surface); border-top:1px solid var(--line);`
  `padding-bottom: env(safe-area-inset-bottom)`.
- Itens: **Início**, **Ao vivo**, **Próximos**, **Apostas** (`.bottom-nav-item`, 10px/700 de label,
  ícone dentro de `.bottom-nav-icon` 44×26) + o **botão redondo do cassino**
  (`.bottom-nav-switch.bottom-nav-switch-end`, 52px de largura, 36px de ícone, fundo
  `color-mix(--surface-2 95%, #000)`, raio `999px 0 0 999px`).
- Item ativo: `.bottom-nav-active` — no `app.css` usa `--brand`; no `v4.css` é **neutro**:
  `color:var(--text)` e fundo do ícone `color-mix(--text 10%, transparent)`.

Markup exato: [`markup/07-bottom-nav.html`](markup/07-bottom-nav.html).

### 3.9 Mapa responsivo resumido

| Largura | Colunas | Sidebar | Aside | Bilhete | Navegação |
|---|---|---|---|---|---|
| >1280px | 230 \| 1fr \| 280 | visível | visível | cupom 360 fixo | topnav |
| 961–1280px | 200 \| 1fr | visível | **gaveta** | cupom 360 fixo + botão no topo | topnav |
| 701–960px | 1fr | **oculta** | gaveta | cupom 360 | topnav (some em ≤700) |
| ≤700px | 1fr | oculta | gaveta | barra de ponta a ponta | **bottom-nav** |

---

## 4. Componentes

> Todos os trechos abaixo estão copiados do site antigo (classes reais). Os arquivos completos
> com o markup exato estão em `docs/bet24-ref/markup/`.

### 4.1 Carrossel de banners (hero)

CSS:

```css
.v4-banners { position: relative; }
.v4-banners-trilho {
  display:grid; grid-auto-flow:column; grid-auto-columns:100%; gap:10px;
  overflow-x:auto; scroll-snap-type:x mandatory; scrollbar-width:none; border-radius:12px;
}
.v4-banner { scroll-snap-align:start; }
.v4-banner img { display:block; width:100%; max-height:240px; object-fit:cover; border-radius:12px; }
.carousel-btn { width:34px; height:34px; border-radius:50%; background:#ffffffeb; color:#1a1f2b;
                font-size:22px; position:absolute; top:50%; transform:translateY(-50%);
                box-shadow:0 2px 8px #00000059; }
.carousel-prev { left:-12px; }  .carousel-next { right:-12px; }
@media (width<=960px) { .carousel-btn { display:none; } }
.v4-sem-setas > .carousel-btn { display:none; }   /* JS esconde quando cabe inteiro */
```

Markup:

```html
<section class="v4-banners" data-v4-carrossel>
  <div class="v4-banners-trilho" data-v4-trilho>
    <div class="v4-banner"><img src="https://cdn.mindsports.online/DB_NAME_L/dedc3e0691d73ded384f35954c03a70b.png" alt="Banner 1"></div>
    <div class="v4-banner"><img src="https://cdn.mindsports.online/DB_NAME_L/8d4d51eee61b78165d898dfec64a5f1a.png" alt="Banner 2"></div>
    <div class="v4-banner"><img src="https://cdn.mindsports.online/DB_NAME_L/b09e5de6869ea1655510db575e8b49fd.png" alt="Banner 3"></div>
    <div class="v4-banner"><img src="https://cdn.mindsports.online/DB_NAME_L/11f00a257e078246663dc0f02780d179.jpeg" alt="Banner 4"></div>
  </div>
  <button type="button" class="carousel-btn carousel-prev" data-v4-anterior aria-label="Anterior">&lsaquo;</button>
  <button type="button" class="carousel-btn carousel-next" data-v4-proximo aria-label="Próximo">&rsaquo;</button>
</section>
```

Arquivo: [`markup/02-hero-banners.html`](markup/02-hero-banners.html).

### 4.2 Destaques (`.highlights`) — cartões com odds

Carrossel horizontal de cartões de 346px (no celular `calc(100% - 28px)`), altura mínima 200px
(184px no celular), com um "banner de oferta" especial na primeira posição.

Estrutura de um cartão:

```html
<div class="highlight">
  <div class="highlight-top">
    <span class="highlight-when">31/10 09:30</span>
    <span class="highlight-league"><img class="flag" width="14" height="14" alt="" src=".../gb-eng.png"> Inglaterra -  Premier League</span>
  </div>
  <a class="highlight-teams" href="/v4/jogos/196786856">
    <div class="highlight-team"><img class="logo" width="26" height="26" alt="" src="...png"><span>Chelsea</span></div>
    <div class="highlight-team"><img class="logo" width="26" height="26" alt="" src="...png"><span>Man Utd</span></div>
  </a>
  <div class="highlight-odds">
    <button type="button" class="odd odd-pickable prematch" data-jogo-id="196786856" data-id="77912332">
      <span class="odd-label"><span class="odd-label-text">1</span></span><span class="odd-price">1,87</span>
    </button>
    <!-- X e 2 iguais -->
  </div>
  <a class="highlight-more" href="/v4/jogos/196786856">+950 mercados</a>
</div>
```

A variante "banner de oferta" (`article.highlight.offer-banner`) tem um visual próprio com
`clip-path` e tipografia Poppins/Anton — ver
[`markup/02b-destaques-highlights.html`](markup/02b-destaques-highlights.html).

### 4.3 Stories

`.stories` > `.stories-track` (flex, gap 12, overflow-x) > `.story-card` (82×120, 72×104 em ≤860)
com `img` + `.story-card-title`. O cartão tem anel de 3px na cor da marca; `.story-seen` fica neutro.
Há um visualizador em tela cheia (`.story-backdrop` + `.story-viewer` 9/16) — documentado no CSS.

### 4.4 Abas de esporte (`.sport-bar`)

```html
<nav class="sport-bar" aria-label="Esportes">
  <a class="sport-tab sport-tab-active" href="/v4/">
    <svg .../>Futebol
  </a>
  <a class="sport-tab" href="/v4/esportes/2"><svg .../>Basquete</a>
  ...
</nav>
```

```css
.sport-bar { display:flex; flex-wrap:nowrap; gap:6px; margin-bottom:12px; padding:0 12px;
             overflow-x:auto; scrollbar-width:none; scroll-snap-type:x proximity;
             mask-image:linear-gradient(90deg,#0000,#000 12px calc(100% - 24px),#0000); }
.sport-tab { display:inline-flex; align-items:center; gap:8px; padding:8px 14px;
             border-radius:999px; background:0 0; font-weight:500; }
.sport-tab-active { background:var(--chosen); box-shadow:var(--chosen-shadow); font-weight:700; }
.sport-bar-small .sport-tab { padding:6px 12px; font-size:12px; }
.sport-bar-small .sport-tab svg { width:22px; height:22px; margin:-2px 0; }
@media (width<=860px) {
  .sport-bar .sport-tab { flex-direction:column; min-width:64px; padding:7px 8px; border-radius:12px;
                          text-align:center; font-size:12px; }
  .sport-bar .sport-tab svg { width:26px; height:26px; margin:0; }
  .sport-bar .sport-count { display:none; }
}
```

### 4.5 Filtros / chips

```html
<div class="filters">
  <div class="chips chips-scroll" aria-label="Dia">
    <a class="chip chip-active" href="/v4/">Hoje</a>
    <a class="chip" href="/v4/amanha">Amanhã</a>
    <a class="chip" href="/v4/depois-amanha">dom., 01/11</a>
    <a class="chip" href="/v4/proximos">Próximos</a>
  </div>
</div>
```

`.chip`: pill `padding:7px 14px; font-size:12px`; `.chip-active`: fundo `var(--chosen)`,
sombra `var(--chosen-shadow)`, 700. `.chips-scroll`: rolagem horizontal com máscara nas bordas.

### 4.6 Campeonato (`.league`)

```html
<div class="league">
  <button type="button" class="league-head" aria-expanded="true">
    <span class="league-title">
      <img class="flag" width="16" height="16" loading="lazy" alt="" src="https://images.wolfsistemas.me/flags/br.png">
      <span class="league-name">Brasil - Serie A</span>
      <span class="league-count">1</span>
    </span>
    <span class="league-cols" aria-hidden="true"><span>1</span><span>X</span><span>2</span></span>
    <span class="league-chevron"><svg width="14" height="14" ...><path d="M6 9l6 6 6-6"/></svg></span>
  </button>
  <div class="league-body">
    <!-- .match-row ... -->
  </div>
</div>
```

Medidas efetivas (o `v4.css` sobrescreve o `app.css`):

```css
.league { background:var(--surface); border-radius:10px; margin-bottom:12px; overflow:hidden; }
.league-head { display:grid; grid-template-columns:minmax(228px,1fr) minmax(240px,480px) 52px;
               align-items:center; gap:14px; padding:10px 14px; font-size:13px; font-weight:550;
               background:var(--surface-2); width:100%; text-align:left; border:0; cursor:pointer; }
.league-head[aria-expanded=true] { border-bottom:1px solid var(--line); }
.league-head[aria-expanded=false] { border-bottom:0; }
.league-name { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.league-count { padding:1px 7px; border-radius:999px; background:var(--surface); color:var(--muted); font-size:11px; }
.league-cols { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; text-align:center;
               color:var(--muted); font-size:11px; }
.league-cols.row-odds-2 { grid-template-columns:repeat(2,1fr); }
.league-chevron { color:var(--muted); transition:transform .15s; justify-self:center; }
.league-chevron.is-closed { transform:rotate(180deg); }
@media (width<=860px) {
  .league-head { grid-template-columns:minmax(0,1fr) 20px; }
  .league-cols { display:none; }
}
```

### 4.7 Linha de jogo (`.match-row`) — pré-jogo

```html
<div class="match-row match-item has-tags" data-search="coritiba x vitoria 31/10/2026 16:00">
  <span class="match-tags match-tags-corner">
    <span class="match-tag match-tag-ca" data-tooltip="CA" title="Criar Aposta"><svg .../>CA</span>
    <span class="match-tag match-tag-ep" data-tooltip="PA" title="Pagamento Antecipado"><svg .../>2 gols</span>
  </span>

  <div class="when"><span class="when-top">16:00</span><span class="when-sub">31/10</span></div>

  <div class="teams">
    <a class="teams-link" href="/v4/jogos/196785640">
      <div class="team"><img class="logo" width="16" height="16" ...><span class="team-name">Coritiba</span></div>
      <div class="team"><img class="logo" width="16" height="16" ...><span class="team-name">Vitoria</span></div>
    </a>
    <div class="row-tags">
      <a class="match-stats" href="/v4/stats/..." target="_blank" rel="noopener" title="Estatísticas"><svg .../></a>
    </div>
  </div>

  <div class="row-odds">
    <button type="button" class="odd odd-pickable prematch" data-jogo-id="196785640" data-id="77715154">
      <span class="odd-label"><span class="odd-label-text">1</span></span>
      <span class="odd-price">2,11</span>
    </button>
    <!-- X e 2 iguais -->
  </div>

  <a class="row-more" href="/v4/jogos/196785640" title="Mais mercados">+950</a>
</div>
```

Medidas efetivas:

```css
.match-row { display:grid; align-items:center; gap:14px; padding:12px 14px;
             grid-template-columns: 64px minmax(150px,1fr) minmax(240px,480px) 52px;
             border-top:1px solid var(--line); }
.match-row:first-child { border-top:0; }
.match-row:hover { background:var(--hover); }
.match-row.has-tags { position:relative; padding-top:24px; }   /* espaço para os selos */
.match-tags-corner { position:absolute; top:0; left:0; z-index:1; display:flex; overflow:hidden;
                     border-radius:0 0 6px 0; }
.match-tag { display:inline-flex; align-items:center; gap:3px; height:17px; padding:0 7px 0 5px;
             color:#fff; font:italic 700 11px/1 'Barlow Condensed',sans-serif; text-transform:uppercase; }
.match-tag-ca { background:var(--primary-color); color:var(--text-primary-color); }
.match-tag-ep { background:#5337cc; }
.when { display:flex; flex-direction:column; align-items:center; gap:2px; }
.when-top { font-size:13px; font-weight:550; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; }
.when-sub { color:var(--muted); font-size:11px; }
.when-live .when-top { color:var(--live); }
.teams { display:flex; flex-direction:column; gap:3px; min-width:0; }
.team { display:flex; align-items:center; gap:8px; min-width:0; font-size:14px; font-weight:500; }
.team-name { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.match-row .team-name { white-space:normal; overflow:visible; overflow-wrap:break-word; line-height:1.25; } /* v4.css */
.logo { object-fit:contain; border-radius:50%; flex:none; }
.row-odds { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.row-odds-2 { grid-template-columns:repeat(2,1fr); }
.row-more { justify-self:center; padding:3px 7px; border-radius:999px; background:var(--surface-2);
            color:var(--muted); font-size:11px; font-weight:600; }
@media (width<=860px) {
  .match-row { grid-template-columns:52px minmax(0,1fr); }
  .match-row .row-odds { grid-column:1/-1; }
  .row-more { display:none; }
  .match-row.is-live { grid-template-columns:minmax(0,1fr); row-gap:8px; }
}
```

Markup exato: [`markup/04-grupo-jogos.html`](markup/04-grupo-jogos.html).

### 4.8 Linha de jogo AO VIVO

Acrescenta: `.match-row.is-live`, `.when.when-live`, minuto com raio no `.when-top`,
`<span class="team-score">` em cada time e, nas odds, variação `.odd-up`/`.odd-down`
com `.odd-arrow` e `.odd-glow`.

```html
<div class="match-row match-item is-live" data-search="coritiba x vitoria">
  <div class="when when-live">
    <span class="when-top"><svg class="bolt" .../><span>67'</span></span>
    <span class="when-sub">2º tempo</span>
  </div>
  <div class="teams">
    <a class="teams-link" href="/v4/jogos/aovivo/196785640">
      <div class="team"><img class="logo" ...><span class="team-name">Coritiba</span><span class="team-score">1</span></div>
      <div class="team"><img class="logo" ...><span class="team-name">Vitoria</span><span class="team-score">0</span></div>
    </a>
  </div>
  <div class="row-odds">
    <button type="button" class="odd odd-pickable odd-up rate-up-small" id="tx-77715154">
      <span class="odd-label"><span class="odd-label-text">1</span></span>
      <span class="odd-price"><span class="odd-arrow odd-arrow-up">&#9650;</span>2,15</span>
      <span class="odd-glow"></span>
    </button>
    <!-- X normal, 2 travado com <i class="fas fa-lock"></i> -->
  </div>
  <a class="row-more" href="/v4/jogos/aovivo/196785640" title="Mais mercados">+245</a>
</div>
```

`team-score`: `min-width:18px; text-align:right; font-weight:700; font-variant-numeric:tabular-nums`.

Markup + todos os estados: [`markup/04b-linha-jogo-ao-vivo-e-odd.html`](markup/04b-linha-jogo-ao-vivo-e-odd.html).

### 4.9 Botão de odd (`.odd`) — o componente mais usado

```css
.odd { display:flex; align-items:center; justify-content:space-between; gap:8px;
       min-height:38px; padding:6px 12px; border-radius:8px;
       background:var(--surface-2); border:1px solid var(--odd-border); }
.odd-label { display:flex; flex-direction:column; min-width:0; line-height:1.15; }
.odd-label-text { font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.odd-price { display:inline-flex; align-items:center; gap:5px; flex:none;
             font-size:13px; font-weight:700; color:var(--price);
             letter-spacing:-.02em; font-variant-numeric:tabular-nums; }
.odd-pickable:hover:not([aria-disabled=true]) { background:var(--surface-3); }
.odd.active, .odd.active:hover { background:var(--primary-color); border-color:var(--primary-color); }
.odd.active .odd-label-text, .odd.active .odd-price { color:var(--text-primary-color) !important; }
.odd.disable { cursor:not-allowed; opacity:.5; }
.odd.disable .odd-price { color:var(--muted); }
.odd.odd-up .odd-price { color:var(--up-text); }
.odd.odd-down .odd-price { color:var(--down-text); }
.odd-empty { opacity:.55; }
```

Estados (HTML completo em `04b`):

| Estado | Classes | Observação |
|---|---|---|
| normal | `odd odd-pickable prematch` | `data-jogo-id` + `data-id` (id da cotação) são os ganchos do bilhete |
| selecionado | `+ active` | backend marca; fundo verde-oliva e texto limão |
| travado | `odd odd-locked disable` + `disabled aria-disabled="true"` | preço vira `<i class="fas fa-lock"></i>` |
| vazio | `odd odd-pickable odd-empty` | `opacity .55` |
| ao vivo subiu | `+ odd-up` | `.odd-arrow-up` + `.odd-glow` |
| ao vivo desceu | `+ odd-down` | `.odd-arrow-down` + `.odd-glow` |

### 4.10 Linha compacta da home (`.action-row`)

É o cartão da seção "Junte-se à ação" — **não** é `.match-row`:

```css
.action-list { display:flex; flex-direction:column; gap:6px; }
.action-row { display:grid; grid-template-columns:64px minmax(0,1fr) minmax(300px,420px);
              align-items:center; gap:14px; padding:10px 14px; border-radius:10px;
              background:var(--surface); }
.action-row:hover { background:var(--hover); }
.action-when { display:flex; flex-direction:column; align-items:center; color:var(--muted);
               font-size:12px; font-variant-numeric:tabular-nums; }
.action-when strong { color:var(--text); font-size:13px; }
.action-when.is-live strong { color:var(--live); }
.action-league { display:flex; align-items:center; gap:6px; color:var(--muted); font-size:11px;
                 white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.action-more { margin-left:auto; flex:none; /* pill igual ao row-more */ }
@media (width<=960px) {
  .action-row { grid-template-columns:52px minmax(0,1fr); }
  .action-row .row-odds { grid-column:1/-1; }
  .action-more { display:none; }
}
```

Markup: [`markup/04c-linha-acao-home.html`](markup/04c-linha-acao-home.html).

### 4.11 Cartões da coluna direita (`aside-card`)

```css
.aside-card { background:var(--surface); border-radius:10px; padding:14px; }
```

- **Minhas apostas** (`.aside-card.bets-card`): título centralizado 13px/650 em caixa alta,
  abas `.bets-tabs` (pill com fundo `--surface-2`, ativo com fundo `--text` e texto `--bg`),
  lista `.bets-list` e botão `.bets-history` (bloco, 12px/650, uppercase).
  Vazio: `<p class="bets-empty">Não tem apostas em aberto</p>`.
- **Ofertas** (`.aside-card.offers-card`, `padding:0`): cabeçalho `.suggest-head` com ícone de
  presente + título, lista `<ul class="offers-list">` de `<li class="offer">` com
  `.offer-pic` (72×96, gradiente próprio por variante: `.offer-bolt`, `.offer-flame`,
  `.offer-trophy`, `.offer-cards`, `.offer-stars`, `.offer-bag`), `.offer-group` (11px uppercase),
  `.offer-title` (16px/600), `.offer-text` (13px muted) e `.offer-more` (botão com seta via `:after`).
  Na coluna de 280px: `.offers-card .offer-pic { width:56px; height:76px }`, título 15px.

Markup: [`markup/12-home-aside-cards.html`](markup/12-home-aside-cards.html).

### 4.12 Bilhete / cupom

Estrutura do `.slip-card` (o mesmo componente serve para o cupom flutuante e para a gaveta):

```
.slip-card
├── .slip-head              "Bilhete" + .slip-badge (contador) + .slip-clear (Limpar) + .v4-aside-fechar (X)
├── .v4-compartilhar        2 botões: Copiar | Whatsapp
├── .slip-tabs.ticket-menu  Simples | Dupla | Múltipla  (.slip-tab)
├── .ticket-info            lista de .ticket-item (rola)
├── p.v4-bilhete-vazio      "Clique em uma cotação para montar o seu bilhete." (só quando data-qtd=0)
└── .ticket-content.slip-foot
    ├── .ticket-content-value  .stake-field (R$ + valor) | nome do cliente | check PIX + campos PIX
    ├── dl.slip-totals         Bilhetes | Cotação | Aposta total | Valor do prêmio (.slip-return)
    └── button.slip-place      "Apostar"
```

Um `.ticket-item` (markup real vindo do backend, 1 seleção):

```html
<div class="ticket-item">
  <div class="ticket-item-header">
    <div class="ticket-item-league">Brasil - Serie A</div>
  </div>
  <div class="ticket-item-data">
    <a href="/v3/jogo?id=196785640" style="color:#000; text-decoration:none;">
      <div class="ticket-item-teams">
        <span><img src=".../29f69c1d....png"> Coritiba</span>
        <span><img src=".../dd5c2c38....png"> Vitoria</span>
      </div>
    </a>
    <div class="ticket-item-date">
      <i class="fal fa-clock"></i> 31/10/2026 16:00
      <span class="ticket-item-remove">
        <a href="javascript:remover(196785640, '77715154');"><i class="far fa-trash-alt" aria-hidden="true"></i></a>
      </span>
    </div>
  </div>
  <div class="ticket-item-odd">
    <div class="ticket-item-market">Vencedor do Encontro</div>
    <div class="ticket-item-type">Casa <span class="ticket-item-value">2,11</span></div>
  </div>
</div>
```

Medidas/estados: `.ticket-item { position:relative; padding:10px 12px; border-radius:10px;
background:var(--surface-2); font-size:12px }`, `.ticket-item-remove` 26×26 no canto,
`.ticket-item-value` pill `--surface-3` com `--price`, `.slip-return dd` 16px/750 `--price`.
O botão `.slip-place` no cupom é `background:var(--primary-color); color:var(--text-primary-color)`;
desabilitado `background:var(--surface-3); color:var(--muted)`.

Estados do cupom que o Twig/JS precisa controlar:

| Estado | Como |
|---|---|
| sem seleção | `#v4Cupom[data-qtd="0"]` → `display:none`; `.slip-card[data-qtd="0"]` mostra `.v4-bilhete-vazio` e esconde compartilhar/abas |
| fechado com seleção | barra visível (`.v4-cupom-barra`), corpo oculto |
| aberto | `.v4-cupom.is-aberto` (mostra `.v4-cupom-corpo`, gira a seta 180°, liga `.v4-scrim.is-aberto`, `body.v4-bilhete-aberto`) |
| 1ª odd entrou | `.is-entrando` (`animation v4-cupom-sobe .38s`) |
| contador mudou | `.is-bump` (`animation v4-cupom-pulo .45s`) |
| tipos | barra mostra `Simples` (1), `Dupla` (2), `Tripla` (3), `Múltipla (N)` |

Markup exato com valores: [`markup/05-bilhete-cupom.html`](markup/05-bilhete-cupom.html).

### 4.13 Modais

Base (o v4 usa a marcação de modal do Bootstrap 4, mas o CSS é do tema):

```css
.modal { display:none; position:fixed; inset:0; z-index:60; align-items:center; justify-content:center;
         padding:16px; background:var(--scrim); backdrop-filter:var(--scrim-blur); }
.modal.is-aberto { display:flex; }
.modal-dialog { width:min(520px,100%); max-height:min(86vh,760px); display:flex; }
.modal-content { width:100%; display:flex; flex-direction:column; border-radius:12px;
                 background:var(--surface); color:var(--text); box-shadow:0 20px 50px var(--shadow); overflow:hidden; }
.modal-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; }
.modal-header .close { width:32px; height:32px; border:0; border-radius:50%;
                       background:var(--surface-2); font-size:20px; }
.modal-body { padding:0 14px 14px; overflow-y:auto; }
```

Variantes de largura: `.v4-entrar-dialog` 460px, `.v4-cadastro-dialog` 560px (max-height 92vh/860),
`.v4-busca-dialog` 560px (max-height 80vh/720, alinhado ao topo com `margin-top:8vh`),
`.v4-turbo-dialog` 520px, `.v4-ma-modal` 620px.

No celular (≤560px) os modais com `.v4-sheet` viram **bottom sheet**:

```css
@media (width<=560px) {
  .modal.v4-sheet { align-items:flex-end; padding:0; }
  .modal.v4-sheet .modal-dialog { width:100%; max-height:92vh; }
  .modal.v4-sheet .modal-content { border-radius:18px 18px 0 0; padding-bottom:env(safe-area-inset-bottom); }
  .modal.v4-sheet.is-aberto .modal-content { animation:v4-sobe .25s ease-out; }
}
@keyframes v4-sobe { from { transform:translateY(100%); } to { transform:none; } }
```

Markup: entrar [`08`](markup/08-modal-entrar.html), cadastro [`09`](markup/09-modal-cadastro.html),
busca [`10`](markup/10-modal-busca.html), turbinada [`11`](markup/11-modal-turbinada.html).

**Formulários** — classes compartilhadas entre modal e páginas de conta:

```css
.account-field { display:flex; flex-direction:column; gap:5px; min-width:0; }
.account-label { color:var(--muted); font-size:13px; }
.account-input { width:100%; height:46px; padding:0 14px; font-size:16px; border-radius:12px;
                 border:1px solid var(--line); background:var(--surface); color:var(--text); }
.account-input:focus { border-color:var(--brand); box-shadow:0 0 0 3px color-mix(in srgb, var(--brand) 16%, transparent); outline:none; }
.account-input[aria-invalid=true] { border-color:var(--down-text); }
.account-submit { min-height:40px; padding:0 16px; border-radius:10px; font-size:12px;
                  font-weight:550; text-transform:uppercase; letter-spacing:.04em;
                  background:var(--brand); color:#fff; border:0; }
.v4-cadastro-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }   /* 1 coluna em <=560px */
.v4-cadastro-erro { padding:10px 14px; border-radius:10px; background:var(--error-bg); color:var(--error-text); font-size:13px; }
```

> No tema novo, `--primary-color` é `#263e0f` e `--text-primary-color` `#d4ec00`; por isso os
> botões do tema usam **texto limão sobre verde-oliva**, e o `.account-submit` do `app.css`
> (texto branco) é repassado por `.v4-botao`/`v4.css` para o par da marca. Manter o par.

### 4.14 Avisos, tooltips, toasts e estados vazios

- **Barra de aviso** `.v4-alerta`: `padding:8px 16px; background:#eab3081f; color:var(--warn-text); font-size:13px; text-align:center`.
- **Tooltip** (selos CA/PA): `.tooltip-box` — fundo `#151a24`, texto `#c5cdd0`, raio 8px,
  `max-width:270px`, `z-index:9999`; cabeçalho `.tooltip-header` com badge + `.tooltip-title`
  (12px/700 branco) e `.tooltip-text` (13px). É criado por JS e anexado ao `<body>`.
- **Toast**: `.toast` fixo no rodapé centralizado, fundo `--toast-bg`, borda `--line`, raio 10px,
  `.toast-check` (círculo verde 22px) e `.toast-link`. No celular sobe
  `bottom: calc(var(--bottom-nav-h) + 72px)`.
- **Estado vazio**: `.empty > .empty-state` com `.empty-title` (15px/600) e `.empty-text` (13px);
  botões `.empty-button` / `.empty-button.is-quiet`; ícone em `.empty-icon` (56px, círculo).
- **SweetAlert2** para erros: `.swal2-popup { font-family: inherit }`,
  `.swal2-styled.swal2-confirm { background:var(--primary-color) !important; color:var(--text-primary-color) !important }`.
- **Skeleton**: `.v4-sk` (gradiente animado), `.v4-sk-jogo`, `.v4-sk-hora`, `.v4-sk-time`,
  `.v4-sk-odd`, `.v4-sk-cartao` (340×190), `.v4-sk-cartao`, `.v4-sk-titulo`, `.v4-sk-linha`.

Markup: [`markup/15-avisos-toasts-estados.html`](markup/15-avisos-toasts-estados.html).

### 4.15 Cassino

Layout próprio, sem bilhete: `.cas-layout` (250px | 1fr), `.cas-side` (menu de categorias
sticky), `.cas-main` (busca `.cas-search` + seções `.cas-section` com grade `.cas-grid.cas-grid-wide`
de 5–6 colunas). Card `.cas-card` = `.cas-card-pic` (`aspect-ratio` 16/9 no app.css → **6/7 no
v4.css**, capa em pé) + `.cas-card-name`. Cabeçalho de seção com contador `.cas-count` pill de
34px. Fonte dos títulos: Bricolage Grotesque 700.

Markup: [`markup/14-cassino.html`](markup/14-cassino.html) e
[`markup/14b-cassino-cards.html`](markup/14b-cassino-cards.html).

### 4.16 Páginas de texto (regras, políticas) e Acumuladão

- **Texto**: `.v4-texto` — `max-width:920px; padding:32px 36px; border-radius:16px;
  background:var(--surface); font-size:15px; line-height:1.65` com `h1` 26px,
  links na cor da marca. ≤860px `margin:12px; padding:20px 18px`.
- **Acumuladão** (`.v4-acu`): topo `.v4-acu-topo` com fundo da marca e prêmio;
  cards `.v4-acu-card` (radius 16, sombra, prêmio 24px/700); informações `.v4-acu-info` (4 cols);
  blocos `.v4-acu-bloco`, jogos `.v4-acu-jogo`, compra `.v4-acu-compra`.
- **Longo prazo**: `.v4-longo` (lista de botões `.v4-longo-nome`).

### 4.17 Programa de afiliados (standalone)

`/v4/programa-afiliados` **não usa o tema**: tem CSS próprio no `<head>` (variáveis
`.glow`/`.grade`/`.hero`/`.card`/`.btn-primario`), header simplificado (logo + “Afiliados” +
links de âncora + Entrar) e uma landing com hero, estatísticas, cards, tabela de exemplo e CTA.
Serve como referência visual para uma landing nova, mas **não** compartilha tokens com o tema.
Ver [`markup/16-pagina-afiliados.html`](markup/16-pagina-afiliados.html) e `gaps.md`.

---

## 5. Comportamentos de JavaScript

### 5.1 Tema claro/escuro

- Chave: `localStorage['Tema']` com o valor `'dark'`; ausente/qualquer outra coisa = claro.
- O `<script>` do `<head>` aplica `data-theme` no `<html>` **antes** do primeiro paint.
- Botão `#theme-toggle` (ou `.js-tema`) alterna:

```js
function v4Tema(escuro) {
  document.documentElement.setAttribute('data-theme', escuro ? 'dark' : 'light');
  try {
    if (escuro) localStorage.setItem('Tema', 'dark');
    else localStorage.removeItem('Tema');
  } catch (e) {}
}
```

- Os ícones `.v4-tema-claro` (lua) e `.v4-tema-escuro` (sol) alternam por CSS
  (`:root[data-theme=dark] .v4-tema-claro { display:none }`).
- `<meta name="theme-color">` acompanha a cor da marca, não o tema.

### 5.2 Bilhete / cupom

`v4.js` mantém a contagem (`#qntyItens`) espelhada no `.v4-qtd` do topo.
`v4-cupom.js` lê o DOM do bilhete e atualiza a barra fechada:

- tipo: `Simples / Dupla / Tripla / Múltipla (N)` (segundo `#qntyItens`);
- cotação: texto de `#lblCotacao`;
- seleções: nomes extraídos de `.ticket-info .ticket-item` (times + `.ticket-item-type`,
  trocando “Casa”/“1” pelo nome do time e “Fora”/“2” pelo segundo time);
- mostra até 2 nomes + `.v4-cupom-mais` (`+N`);
- `.v4-cupom-selo` (CA) aparece se houver `.bet-builder-group` no bilhete;
- `.is-entrando` na 1ª odd; `.is-bump` quando a quantidade muda; remove tudo com 0;
- abrir/fechar também escuta `[data-v4-bilhete]` (topo/bottom-nav), `[data-v4-bilhete-fechar]`,
  clique na barra, `.v4-scrim` e `Escape`.

### 5.3 Colapso de campeonato e de mercado

```js
$(document).on('click', '.league-head', function() {
  var aberto = $(this).attr('aria-expanded') !== 'false';
  $(this).attr('aria-expanded', aberto ? 'false' : 'true');
  $(this).find('.league-chevron').toggleClass('is-closed', aberto);
  $(this).next('.league-body').prop('hidden', aberto);
});
```

O `.market-head` faz o mesmo adicionando `.is-fechado` no `.market`.

### 5.4 Carrosséis

`[data-v4-carrossel]` + `[data-v4-trilho]`: as setas rolam `max(240, clientWidth * 0.8)` com
`behavior:'smooth'`; o CSS `.v4-sem-setas > .carousel-btn` esconde as setas quando o trilho cabe
inteiro (ajustado por JS no load/resize).

### 5.5 Outros comportamentos do `v4.js`

- Modais: `[data-toggle="modal"]` abre, `[data-dismiss="modal"]` fecha, clique fora fecha,
  `Escape` fecha.
- Menus suspensos do topo (`[data-v4-menu]` / `.wallet-menu`): abre/fecha com `hidden` e
  `aria-expanded`, fecha ao clicar fora.
- Bilhete em telas ≤1280 (`[data-v4-bilhete]`): alterna `.is-aberto` na `#v4Aside` e no `.v4-scrim`,
  e `body.v4-bilhete-aberto` (trava scroll).
- `#v4Topo`: scroll suave ao topo.
- Ao vivo: a lista é redesenhada a cada 20 s (marcando as odds já escolhidas com `.active`).
- Home: blocos em AJAX, 20 jogos por lote via `IntersectionObserver`, e a cada 60 s confere se as
  cotações mudaram (atualiza só os botões, com `.v4-odds-mudou`).
- Busca: campo filtra as linhas já desenhadas (`v4FiltrarJogos`) e busca em modal
  (`data-v4-busca`) com resultados em `.search-result`.

---

## 6. Inventário de imagens

### 6.1 Imagens do tema (baixadas)

Todas vêm do CDN `https://cdn.mindsports.online/DB_NAME_L/`. `DB_NAME_L` é um **placeholder literal
de configuração** (o CDN responde); no tema novo use o CDN/banco do site atual.

| Arquivo local (`assets/img/`) | Dimensões | Bytes | URL original | Uso |
|---|---|---|---|---|
| `logo-bet24hs.png` | 319×122 | 53.414 | `https://cdn.mindsports.online/DB_NAME_L/7c6e29633294ed7cd044fe3a79ac1f70.png` | logo do header (`.brand-logo`), `<link rel="icon">`, `og:image`/`twitter:image` |
| `banner-01.png` | 1530×400 | 834.064 | `https://cdn.mindsports.online/DB_NAME_L/dedc3e0691d73ded384f35954c03a70b.png` | banner 1 do carrossel da home |
| `banner-02.png` | 1530×400 | 792.707 | `https://cdn.mindsports.online/DB_NAME_L/8d4d51eee61b78165d898dfec64a5f1a.png` | banner 2 |
| `banner-03.png` | 1530×400 | 826.113 | `https://cdn.mindsports.online/DB_NAME_L/b09e5de6869ea1655510db575e8b49fd.png` | banner 3 |
| `banner-04.jpeg` | 1600×721 | 201.740 | `https://cdn.mindsports.online/DB_NAME_L/11f00a257e078246663dc0f02780d179.jpeg` | banner 4 |

Além destas, o v4 usa:

- **`og:image`** = a mesma do logo.
- **`<link rel="icon">`** = a mesma do logo (PNG, sem favicon.ico próprio).
- **Nenhuma outra imagem estática do tema.** Ícones, ilustrações de "Ofertas", bandeiras de
  cassino etc. são **SVG inline** ou **Font Awesome**; os raios decorativos dos destaques são
  `mask-image` com data-URI SVG (`--hl-bolts`).

### 6.2 Imagens dinâmicas (não são assets do tema)

| Padrão | Host | Observação |
|---|---|---|
| `https://images.wolfsistemas.me/s/<hash>.png` | images.wolfsistemas.me | escudos dos times (16×16 na linha, 26×26 no destaque). **2.498 URLs distintas** aparecem nas páginas capturadas. Vêm do backend/provedor. |
| `https://images.wolfsistemas.me/flags/<pais>.png` | images.wolfsistemas.me | bandeiras de campeonato (`br.png`, `gb-eng.png`, `es.png`, `de.png`, `fr.png`, ...) |
| `https://images.wolfsistemas.me/s/<hash>.png` no `.ticket-item` | idem | escudos dentro do bilhete |
| capas dos jogos de cassino | CDN do provedor (PlayFivers etc.) | carregadas por `v4-cassino.js` |

O tema novo deve continuar usando as imagens do **seu** provedor de dados; o importante é manter
os tamanhos/classes (`.logo` 16px, `.flag` 16px, `.highlight-team .logo` 26px).

> Lista completa das 2.523 URLs de imagem encontradas nas páginas: `_temp/v4ref/_asset_urls.txt`.
> O manifesto dos downloads (48 arquivos) está em `_temp/v4ref/assets/assets-manifest.json`.

---

## 7. Inventário de ícones

O v4 **não usa sprite**: todos os ícones são `<svg>` inline com
`viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
stroke-linejoin="round" aria-hidden="true"` (exceções: raio `.bolt` com `fill="currentColor"`,
ilustrações `.cas-art` com gradientes, e os ícones do Font Awesome).

Os SVGs completos, com a indicação de onde cada um aparece, estão em
[`markup/00-icones-svg.html`](markup/00-icones-svg.html). Resumo:

| Área | Ícones |
|---|---|
| Header | busca (lupa), tema claro (lua), tema escuro (sol), bilhete; texto “V5” |
| Menu lateral | bolinha “ao vivo” (CSS), afiliado (usuários), 8 esportes: futebol, basquete, lutas, vôlei, futebol americano, hóquei, beisebol, rugby (+ tênis na sport-bar) |
| Toggle da home | raio (`bolt`), relógio |
| Linha de jogo | raio (ao vivo), selo CA (raio cheio), selo PA (setas), estatísticas (barras), cadeado (FA `fa-lock`) |
| Bilhete | bilhete 18px, chevron 14px, lixeira, X, copiar, usuário, Whatsapp (FA `fab`) |
| Bottom-nav | casa, raio, calendário, prancheta com checks, roleta do cassino (SVG 32px com gradientes) |
| Rodapé | Font Awesome: `fab fa-instagram`, `fab fa-facebook`, `fab fa-whatsapp`; selo `+18` é texto |
| Ofertas | presente 16px + 6 ilustrações `.cas-art` 32px (raio, chama, troféu, cartas, estrelas, saco) |
| Utilitários | voltar ao topo, chevron de campeonato, lupa do cassino |

**Font Awesome:** `all.min.css` é o **Font Awesome Pro 5.11.2** (licença comercial). As webfonts
referenciadas são `fa-brands-400`, `fa-duotone-900`, `fa-light-300`, `fa-regular-400`,
`fa-solid-900` (eot/ttf/woff/woff2/svg) — as versões woff2/woff/ttf foram baixadas para
`assets/webfonts/`. Os ícones usados no markup são `fas` (solid), `far` (regular), `fal` (light)
e `fab` (brands).

---

## 8. Dados que o Twig precisa fornecer

Para o tema novo manter o visual, o backend atual precisa entregar (por jogo):

| Campo no v4 | Exemplo | Usado em |
|---|---|---|
| `casa` / `fora` | `Coritiba` / `Vitoria` | `.team-name` |
| `ec` / `ef` (escudo) | URL | `.logo` (16px) |
| `cn` (campeonato) | `Brasil - Serie A` | `.league-name`, `.action-league-name` |
| `bd` (bandeira) | `br.png` | `.flag` |
| `cid` (id do campeonato) | `1570` | agrupamento/`data-liga` |
| `h` / `dm` / `dia` | `16:00` / `31/10` / `Amanhã` | `.when-top`, `.when-sub`, `.action-when` |
| `busca` | `coritiba x vitoria 31/10/2026 16:00` | `data-search` (filtro local) |
| `o` (odds) | `[["77715154","2,11"], …]` | `.odd` (`data-id`, `data-jogo-id`) |
| `mais` | `["/v4/jogos/196785640","950"]` | `.row-more` / `.action-more` (`+950`) |
| `ca` / `pa` | `true` | selos `.match-tag-ca` / `.match-tag-ep` |
| `st` | `/v4/stats/<token>` | `.match-stats` |
| `x` | `true` | tem empate → 3 odds; `false` → 2 odds (`.row-odds-2`) |
| ao vivo: `tempo`, `periodo`, `placar` | `67`, `2º tempo`, `1 x 0` | `.when-live`, `.team-score` |
| bilhete: `#qntyItens`, `#lblCotacao`, `#lblApostado`, `#lblValorPremio`, `#jsonEscolhas` | | barra do cupom, totais |

Os **ids do bilhete** (`#qntyItens`, `#lblCotacao`, `#lblApostado`, `#lblValorPremio`,
`#valorApostado`, `#btnApostar`, `#limpar`, `#txtConsultar`) são o contrato entre o PHP antigo e
os JS do tema. No tema novo basta manter os mesmos ids/atributos se quiser reaproveitar os
comportamentos (`v4-cupom.js` é 100% DOM-driven e não fala com o backend).

---

## 9. Checklist de fidelidade

Ao portar para Twig, conferir:

- [ ] Ordem: `all.min.css` → `fonts.css` → `app.css` → `:root` da marca → `v4.css` (é o que garante as cores).
- [ ] `--primary-color:#263e0f`, `--text-primary-color:#d4ec00`, `--brand:var(--primary-color)`.
- [ ] Header sticky 60px com a cor da marca; topnav em caixa alta 13px; `.topnav-active` com borda inferior de 3px.
- [ ] Grid da home `230px | 1fr | 280px`, `max-width:1500px`, gap `22px 18px`; `.list-layout` para páginas internas.
- [ ] `.match-row` em 4 colunas `64px | minmax(150,1fr) | minmax(240,480) | 52px`; ≤860px vira 2 colunas.
- [ ] Odd com `min-height:38px`, raio 8, preço 13px/700 verde; selecionada verde-oliva + limão.
- [ ] Bilhete flutuante 360px no canto inferior direito (não é a coluna de 280px!) e some com 0 seleções.
- [ ] Coluna direita (280px) com Minhas apostas + Ofertas; gaveta ≤1280px.
- [ ] Rodapé 5 colunas, `padding:28px 20px 24px`, títulos 12px uppercase.
- [ ] Bottom-nav só ≤700px, altura `calc(56px + safe-area)`.
- [ ] `data-theme` aplicado antes do paint pelo script do `<head>` (fundo escuro `#090915`).
- [ ] Tema escuro não muda a cor da marca (header continua verde-oliva).
- [ ] `prefers-reduced-motion` desliga as animações.
- [ ] Bandeiras/escudos com as classes `.flag`/`.logo` nos tamanhos certos (16/26px).

---

## 10. Como usar os assets baixados

| Pasta | Conteúdo | Como usar |
|---|---|---|
| `_temp/v4ref/assets/webfonts/` | 15 arquivos `fa-*` (woff2/woff/ttf, 5 famílias) | copiar para `assets/webfonts/` do site atual; o `all.min.css` referencia `../webfonts/` |
| `_temp/v4ref/assets/fonts/google/` | 20 `.woff2` (Anton 400, Barlow Condensed 700 + itálico, Bricolage Grotesque 700, Geist 400–800, Poppins 800 — subsets latin/latin-ext/vietnamese/cyrillic/devanagari) | copiar para `assets/fonts/google/` e trocar as URLs do `fonts.css` por caminhos locais (`url('../fonts/google/...woff2')`) |
| `_temp/v4ref/assets/img/` | logo + 4 banners | copiar para `img/logo.png`, `img/banners/*.png` |
| `_temp/v4ref/assets/js/` | `v4.js`, `v4-home.js`, `v4-cupom.js`, `tema-view.js`, `v4-cassino.js`, `v4-carteira.js`, `v4-conta.js` | **referência de comportamento**; não copiar como está (fala com `/router`, `/api` e `/hub` do v4 — ver `gaps.md`) |
| `_temp/v4ref/assets/css/` | `all.min.css`, `v4/{fonts,app,v4}.css` | `all.min.css` (Font Awesome) e `v4/v4.css` podem ser copiados; `app.css` também, desde que se remova a dependência de `.app`/Bootstrap. Ver `assets-css.md` |

> Todas as fontes foram baixadas para o site conseguir rodar **offline**, sem depender do
> `fonts.gstatic.com`. Basta reescrever o `fonts.css` apontando para os arquivos locais.
