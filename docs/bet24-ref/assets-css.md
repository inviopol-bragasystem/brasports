# Mapa dos CSS/JS do v4 (o que dá para reaproveitar)

Complementa o [`spec.md`](spec.md). Aqui está o inventário técnico dos arquivos do tema v4:
tamanho, função, ordem de carregamento e o que pode ser copiado direto para o tema novo.

Tudo em `C:\wwwroot\banca\_temp\v4ref\` (cópias em `_temp\v4ref\assets\`).

---

## 1. Visão geral

| Arquivo | Bytes | Linhas | Regras CSS ≈ | Função |
|---|---:|---:|---:|---|
| `css_all.min.css` | 163.161 | 5 | 3.955 | **Font Awesome Pro 5.11.2** completo (ícones + `@font-face` das 5 famílias) |
| `css_v4_fonts.css` | 8.430 | 359 | — | **@font-face do Google Fonts** (Anton, Barlow Condensed, Bricolage Grotesque, Geist, Poppins) |
| `css_v4_app.css` | 195.916 | 2 (minificado) | 2.281 | **Layout e componentes base** do tema (tokens, header, home, jogos, bilhete, modais, rodapé, cassino, conta) |
| `css_v4_v4.css` | 91.640 | 1.789 | 986 | **Camada de marca do v4**: ponte de cores, correções de medidas e blocos exclusivos (cupom flutuante, home AJAX, minhas apostas, afiliados) |
| `js_v4.js` | 60.179 | 1.794 | — | Comportamento geral: tema claro/escuro, menus, modais, carrosséis, filtro de busca, colapso, **regras do bilhete** (Parte 2, copiada do v3) |
| `js_v4-home.js` | 24.397 | 556 | — | Home em AJAX: blocos + jogos em lotes de 20, atualização de cotações, templates de `.action-row`/`.liga` |
| `js_v4-cupom.js` | 5.378 | 125 | — | Bilhete flutuante (`.v4-cupom`): texto da barra, abrir/fechar, animações |
| `js_tema-view.js` | 1.717 | 56 | — | Troca layout v4 ↔ v5 (localStorage/cookie `themeView`) — **não usar** |
| `js_v4-cassino.js` | 16.547 | 375 | — | Cassino: lista/categorias/busca em AJAX, abrir jogo |
| `js_v4-carteira.js` | 31.095 | 748 | — | Depósito/saque em AJAX (PIX, QR, passos, polling) |
| `js_v4-conta.js` | 6.079 | 216 | — | Área de conta: cancelar bilhete, cash-out, caixa |

Ordem de carregamento (do `<head>` para o `<body>`):

```
1. all.min.css
2. v4/fonts.css
3. v4/app.css
4. <style> inline com --primary-color / --text-primary-color (cores do painel)
5. v4/v4.css
... fim do body ...
6. jquery 1.12
7. sweetalert2@11
8. jquery.mask 1.14.16
9. v4.js
10. tema-view.js
11. v4-cupom.js
12. v4-home.js   (só na home)
```

---

## 2. CSS — análise arquivo por arquivo

### 2.1 `all.min.css` (Font Awesome Pro 5.11.2)

- Cabeçalho: `/*! Font Awesome Pro 5.11.2 by @fontawesome - https://fontawesome.com
  License - https://fontawesome.com/license (Commercial License) */`
- Contém **apenas** o Font Awesome: classes `.fa`, `.fas`, `.far`, `.fal`, `.fad`, `.fab`,
  utilitários de tamanho (`.fa-lg`, `.fa-2x`…), rotação, `fa-spin`, `fa-stack`, e os
  `@font-face` das 5 famílias apontando para `../webfonts/fa-*.{eot,svg,ttf,woff,woff2}`.
- **Não tem reset/normalize, não tem Bootstrap, não tem grid.**
- **Reaproveitamento: direto**, desde que se copie `webfonts/` para o caminho relativo
  (`assets/css/../webfonts/`).
- ⚠️ **Licença:** é Font Awesome **Pro** (licença comercial). Se o tema novo não puder usar a
  licença Pro, trocar por Font Awesome Free 5.15.4 (mesmas classes `fas`/`far`/`fab`, mesmos
  arquivos) — o markup do v4 só usa `fas` (solid), `far` (regular), `fal` (light) e `fab` (brands).
  Os ícones `fal` (light) **não existem** no Free; os poucos usos no v4 são:
  `fal fa-clock` (data do bilhete), `fal fa-user-circle` (menu Conta do hub). Substituir por
  `far`/`fas` equivalentes.

### 2.2 `v4/fonts.css`

- 20 blocos `@font-face` apontando para `https://fonts.gstatic.com/s/...` (todas as variantes
  relevantes de Anton 400, Barlow Condensed 700/itálico-700, Bricolage Grotesque 700,
  Geist 400–800 variável e Poppins 800).
- **Subsets:** `latin`, `latin-ext`, `vietnamese`, `cyrillic`, `cyrillic-ext`, `devanagari`.
  Só os subsets `latin`/`latin-ext` são necessários para pt-BR.
- **Reaproveitamento:** as 20 `.woff2` foram baixadas para
  `_temp/v4ref/assets/fonts/google/`. Reescrever os `src: url(...)` para
  `url('../fonts/google/<arquivo>.woff2')` (mesmo `unicode-range`/`font-display: swap`) e o
  tema fica **offline** e sem requisição a terceiros.

### 2.3 `v4/app.css` — o "layout"

Estrutura observada (2.281 regras):

| Grupo | Exemplos de seletores |
|---|---|
| Tokens | `:root` (55 tokens), `[data-theme=dark]` (tokens do escuro), `--sk`/`--sk-hi` |
| Base | `*{box-sizing}`, `html{background:var(--bg)}`, `body{font:400 14px/1.4 Geist…}`, `[hidden]` |
| Topbar | `.topbar .brand .brand-logo .brand-mark .topnav .topnav-link .topnav-active .topbar-actions .topbar-icon .search-pill` |
| Conta/topo | `.account-actions .account-signin .account-signup .account-me .account-avatar .account-form .account-input .account-submit …` |
| Layout | `.content .home .home-top .home-side .home-main .home-aside .list-layout .match-layout .aside-card .page-title .page-head .filters .chips .chip .sport-bar .sport-tab .toggle` |
| Jogos | `.league .league-head .league-title .league-count .league-cols .league-chevron .league-body .match-row .when .teams .team .team-name .team-score .row-odds .row-more .odd .odd-label .odd-price .odd-arrow .odd-glow .match-card .market .market-head .market-grid .odds-table` |
| Home | `.highlights .highlight .highlight-* .offer-banner .offer-banner-* .stories .story-* .carousel-btn .home-quick .quick-link .more-link .home-title .home-section .home-footer` |
| Bilhete | `.slip .slip-bar .slip-body .slip-head .slip-badge .slip-clear .slip-tabs .slip-tab .slip-list .slip-pick .slip-price .slip-remove .slip-single .stake .stake-field .stake-currency .stake-quick .slip-foot .slip-multi .slip-alert .slip-totals .slip-return .slip-place .ticket .ticket-* .bets-card .bets-tabs .bets-tab .bets-list .bets-history` |
| Modais | `.modal .modal-dialog .modal-content .modal-header .modal-body .modal-footer .popup-img` |
| Rodapé | `.site-footer .site-footer-link .site-footer-age` |
| Bottom-nav | `.bottom-nav .bottom-nav-item .bottom-nav-icon .bottom-nav-label .bottom-nav-active .bottom-nav-switch …` |
| Cassino | `.cas-layout .cas-side .cas-side-* .cas-main .cas-search .cas-section .cas-grid .cas-card .cas-card-pic .cas-count …` |
| Conta/outros | `.acct-*`, `.wallet-*`, `.v4-*` helpers, `.empty*`, `.toast`, `.search-*` |
| Skeletons | `.v4-sk*`, `.story-sk` |

Pontos de atenção:
- O `app.css` **não traz reset** (nem Bootstrap). A página depende de `box-sizing` global e de
  estilos explícitos. Ao portar, garantir que o reset do sistema atual não conflite.
- Usa `color-mix()` e `:has()` e `@media (width<=X)` (sintaxe nova) — funciona em Chrome/Edge/Safari
  atuais; se o sistema precisar de navegadores antigos, converter para `rgba()`/`max-width`.
- **Reaproveitamento recomendado: sim, como base**, cortando as seções que não existem no sistema
  novo (conta/hub, cassino se não houver, acumuladão se não houver). É o arquivo que dá o
  "esqueleto" (grids, header, linhas de jogo, bilhete, rodapé).

### 2.4 `v4/v4.css` — a camada de marca e as correções

1.789 linhas legíveis, organizadas em seções comentadas (índice no topo):

```
A. Variáveis e ponte de cores      H. Página de jogo
B. Base e utilitários              I. Modais, avisos e SweetAlert
C. Barra superior, menus e busca   J. Rodapé
D. Menu lateral                    L. Jogo ao vivo (layout de detalhe)
E. Linhas de jogo, campeonatos     M. Área de conta
F. Home                            K. Responsivo
G. Bilhete (coluna direita)        N. Páginas de texto
O. Acumuladão e longo prazo        P. Cadastro em modal
Q. Banner/modal "aposta turbinada" R. Criar aposta, selo CA, ofertas
S. Login em modal/bottom sheet     T. Busca em modal
U. Stories                         V. Cassino
+ Bilhete flutuante (.v4-cupom)    + Minhas apostas / acompanhar
```

Contém:
- **ponte de cores** (`--brand: var(--primary-color)`, listas de seletores que passam a usar
  `--text-primary-color`/`--primary-accent-color`);
- **redefinição das medidas** dos componentes (`.match-row`, `.league-head` etc.);
- **blocos que só existem no v4**: `.v4-cupom*`, `.v4-banners`, `.v4-dias`, `.v4-consulta`,
  `.v4-rodape`, `.v4-scrim`, `.v4-aside` (gaveta), `.v4-sk*` (skeletons), `.v4-cadastro-*`,
  `.v4-turbo-*`, `.v4-conta-modal`, `.v4-ma-*`, `.v4-tabela*`, `.v4-carteira*`, `.v4-pix-*`,
  `.v4-passos`, `.v4-pago`, `.v4-ac-*` (acompanhar), `.v4-paginas`, `.v4-texto`, `.v4-acu-*`,
  `.v4-longo`.

**Reaproveitamento: direto.** É o arquivo que faz o tema parecer "bet24" (verde-oliva/limão).
Só remover as seções de páginas que não existirão (ex.: `M` conta, `V` cassino) e o
`.topbar-view` (botão V5).

### 2.5 Regras que NÃO se aplicam ao tema novo

- `.topbar-view` / `data-theme-view` / `js_tema-view.js` — troca de layout v4→v5, específico do site.
- `html.v4-embed …` — modo iframe da conta dentro do modal; só faz sentido se o sistema novo
  embutir as páginas de conta.
- `.app-game`, `.app-account` — variantes de layout do v4.
- `.v4-carteira*` — depósito/saque AJAX que conversa com `/api/checkdeposit`, `/router?path=caixa`.
- `.cas-*` — só se o cassino for mantido.

---

## 3. JS — análise e endpoints

Todos os arquivos assumem **jQuery 1.12 global** (o `v4-cupom.js`/`v4-cassino.js`/`v4-carteira.js`
recebem `window.jQuery` como `$`). Nenhum é módulo ES.

### 3.1 `v4.js` (1.794 linhas)

Parte 1 — interface (reaproveitável):
- `v4Tema(escuro)` + `#theme-toggle`/`.js-tema` + `localStorage['Tema']`.
- `v4Bilhete(abrir)` (gaveta lateral), `v4AtualizarQtd()` (contador do topo).
- Delegação de eventos: `.league-head`, `.market-head`, modais (`[data-toggle/dismiss="modal"]`),
  menus (`[data-v4-menu]`), `[data-v4-bilhete]`, carrosséis, `#v4Topo`, `Escape`.
- `v4FiltrarJogos(termo)` — filtra `.match-item` por `data-search` normalizado e esconde
  campeonatos vazios.
- `v4Escapar`, `v4Normalizar`, `v4Rotulos`.

Parte 2 — bilhete (NÃO reaproveitar direto: fala com o backend v4). Endpoints:

```
/router?path=apostar.adicionar&tema=V3&jogo=…&taxa=…
/router?path=apostar.remover&tema=V3&jogo=…&taxa=…
/router?path=apostar.valor&valor=…
/router?path=apostar.finalizar&tema=V3
/router?path=bilhetes.compartilhar
/router?path=bilhetes.importar&bilhete=…
/api/partidas_aovivo
/api/pix?codigo=…
/api/cpf?cpf=…
/api/mobile/*  (adicionar/remover/atualizar/finalizar ao vivo)
/api/finalizar_acumuladao
/hub/entrar?red=/v4/ | /hub/cadastro | /hub/recuperar-senha
```

### 3.2 `v4-home.js` (556 linhas)

Home em AJAX. Endpoints:

```
GET /router?path=v4.home&bloco=jogos[&hash=…]      → JSON { dados: { principais[], demais[], amanha[], icones{…}, vazio, dia_semana } }
GET /router?path=v4.home&bloco=destaques|stories|ligas|lateral|superodds|superdicas|dicas
GET /router?path=bilhetes.escolhas                 → lista de ids já no bilhete
```

O que é reaproveitável: os **templates de HTML** (`linhaAcao`, `linhaJogo`, `liga`, `odds`, `times`,
`tags`, `selos`) — são a referência exata do markup de `04-grupo-jogos.html` e
`04c-linha-acao-home.html`. O resto (hash, lotes, IntersectionObserver, revalidação a cada 60 s)
só faz sentido com um backend equivalente.

### 3.3 `v4-cupom.js` (125 linhas)

**Reaproveitável quase inteiro** (é 100% DOM): lê `#qntyItens`, `#lblCotacao`,
`.ticket-info .ticket-item` e atualiza a barra do cupom. Não faz requisição. Para o tema novo,
ajustar apenas os seletores se o markup do bilhete mudar.

### 3.4 `v4-cassino.js` (375 linhas)

Endpoints: `GET /router?path=cassino.inicio|cassino.jogos|cassino.abrir`, login via
`/hub/entrar?red=…`. Útil como referência dos templates de card/seção
(`14b-cassino-cards.html`).

### 3.5 `v4-carteira.js` (748 linhas) e `v4-conta.js` (216 linhas)

Depósito/saque PIX e conta (cash-out, cancelar bilhete, caixa). Endpoints:
`/api/checkdeposit?id=`, `/api/cashout`, `/api/cashout_info?id=`,
`/router?path=bilhetes.cancelar&id=`, `/router?path=caixa&subTipo=`. **Não reaproveitar** sem o
backend correspondente.

### 3.6 `tema-view.js` (56 linhas)

Troca de layout v4/v5 via `localStorage['themeView']` + cookie. **Descartar.**

---

## 4. Páginas capturadas (o que cada uma é)

| Arquivo | Bytes | Layout | Observação |
|---|---:|---|---|
| `home.html` | 165.194 | `.home` (3 col.) | estrutura + esqueletos; jogos/destaques via AJAX. **É a mesma página de `cadastro.html`** (o /v4/cadastro devolveu a home com o modal) |
| `cadastro.html` | 165.194 | idem | idêntico ao home (hash MD5 igual) |
| `aovivo.html` | 247.862 | `.list-layout` | lista em JS a partir de `/api/partidas_aovivo` (20 s); HTML do template ao vivo nas linhas ~2820-2960 |
| `proximos.html` | 322.330 | `.list-layout` | lista renderizada no servidor |
| `amanha.html` | 2.242.229 | `.list-layout` | **referência principal de `.league` + `.match-row`** (985 jogos, 1.500+ ligas em JS/CSS) |
| `depois-amanha.html` | 1.062.373 | `.list-layout` | idem |
| `esportes2.html` | 535.759 | `.list-layout` | página de esporte (Basquete, 217 jogos) — mostra `.page-title` + `sport-bar` |
| `acumuladao.html` | 238.475 | `.list-layout.v4-acu` | blocos `.v4-acu-*` |
| `longo-prazo.html` | 237.945 | `.list-layout` | `.v4-longo-*` |
| `minhas-apostas.html` | 239.136 | `.list-layout` | página de apostas |
| `acompanhar.html` | 107.623 | `.v4-ac-pagina` | acompanhar bilhete por código |
| `cassino.html` | 102.689 | `.cas-layout` | lista de cassino (esqueleto + JS) |
| `regras.html` | 139.268 | `.v4-texto` | página de texto |
| `programa-afiliados.html` | 38.614 | **standalone** | landing de afiliados com CSS próprio (não usa o tema) |
| `conta`, `depositos`, `saques`, `perfil`, `apostas` | 15.705 cada | **Bootstrap 3.3.7** | todos **idênticos** (MD5 igual): são a página de **login do HUB** (`/hub/entrar`) para onde o v4 redireciona quem não está logado — ver `gaps.md` |

---

## 5. O que copiar direto × o que reescrever

| Item | Decisão |
|---|---|
| `webfonts/fa-*.{woff2,woff,ttf}` | **copiar** |
| `all.min.css` | **copiar** (avaliar licença Pro; alternativa: FA Free 5.15.4) |
| `fonts.css` + 20 `.woff2` | **copiar reescrevendo as URLs** para `../fonts/google/` |
| `app.css` | **copiar como base**, removendo seções não usadas |
| `v4.css` | **copiar**, removendo `M` (conta), `V` (cassino) se não houver, `.topbar-view` e `html.v4-embed` |
| `v4-cupom.js` | **copiar/adaptar** (DOM puro) |
| `v4-home.js` | **só os templates** (HTML) |
| `v4.js` | **copiar a Parte 1**; descartar a Parte 2 (bilhete/backend) e reescrever contra as rotas do sistema atual |
| `v4-cassino.js`, `v4-carteira.js`, `v4-conta.js` | **não copiar** (dependem de rotas do v4); usar como referência |
| `tema-view.js` | **descartar** |
| logo/banners | **copiar** (ou reenviar para o CDN do site atual) |

### Cuidados técnicos ao portar

1. **Sintaxe CSS moderna**: `@media (width<=860px)`, `color-mix()`, `:has()`, `env()`.
   Se o sistema atual precisar suportar navegadores antigos, converter.
2. **`--primary-color` vem do painel** no v4 (PHP). No tema novo, ou emitir do mesmo jeito ou
   fixar no `:root` (o valor do bet24 é `#263e0f`).
3. **`all.min.css` + `app.css` não têm reset**: o tema depende de `*{box-sizing:border-box}` e de
   não haver estilos de terceiros. Se o sistema atual carregar Bootstrap, isolar o tema (o v4 não
   carrega Bootstrap no layout — só nas páginas antigas do hub).
4. **Font Awesome Pro**: as classes `fal` usadas (`fal fa-clock`, `fal fa-user-circle`) não
   existem no Free.
5. **`data-theme`** deve ser aplicado no `<html>` **antes do CSS** para não piscar o tema claro.
6. **CSS grande**: `app.css` + `v4.css` ≈ 288 KB sem minificar. Se o sistema tiver minificação
   automática, juntar `app` + `v4` na ordem certa preservando as variáveis inline no meio.
