# Referência visual do tema v4 → tema "bet24" (site11)

Material de análise do site antigo **`https://bet24hs.site/v4/`** para construir o tema **bet24**
em Twig no sistema atual (`C:\wwwroot\banca`).

> **Comece por:** [`spec.md`](spec.md) — é a especificação completa (tokens, layout, componentes).
> Precisa de um recorte de código? Vá direto em [`markup/`](markup/).
> Vai copiar CSS/JS? Leia [`assets-css.md`](assets-css.md) e [`gaps.md`](gaps.md) antes.

## Arquivos desta pasta

| Arquivo | Conteúdo |
|---|---|
| [`spec.md`](spec.md) | **Especificação visual**: tokens de cor (claro/escuro), tipografia, raio/sombra/espaçamento, breakpoints, estrutura de página (header, sidebar, main, aside, bilhete, rodapé, bottom-nav), todos os componentes com trechos de HTML exatos, inventário de imagens e de ícones, comportamentos JS, checklist de fidelidade |
| [`assets-css.md`](assets-css.md) | Mapa dos CSS/JS: tamanho, função, ordem de carregamento, endpoints de cada JS, páginas capturadas, o que copiar direto × reescrever |
| [`gaps.md`](gaps.md) | O que **não** é reaproveitável (router/API/hub do v4, conta em Bootstrap 3, hCaptcha, tracking, cassino, service worker) e o que precisa ser refeito |
| [`markup/`](markup/) | 21 recortes `.html` com o markup exato (só estrutura + classes) |

## Recortes de markup (`markup/`)

| Arquivo | Componente |
|---|---|
| [`00-icones-svg.html`](markup/00-icones-svg.html) | Inventário dos ~48 ícones SVG inline, com onde cada um aparece |
| [`01-header-nav.html`](markup/01-header-nav.html) | Header/topbar + topnav completa (logo, busca, tema, bilhete, entrar/cadastrar) |
| [`02-hero-banners.html`](markup/02-hero-banners.html) | Carrossel de banners (hero) |
| [`02b-destaques-highlights.html`](markup/02b-destaques-highlights.html) | Cartões de destaque (highlights) renderizados pelo backend |
| [`03-sidebar-esportes.html`](markup/03-sidebar-esportes.html) | Menu lateral da home (dias, importar jogo, esportes, ligas) |
| [`04-grupo-jogos.html`](markup/04-grupo-jogos.html) | Campeonato (`.league`) + linhas de jogo (`.match-row`) + botões de odd |
| [`04b-linha-jogo-ao-vivo-e-odd.html`](markup/04b-linha-jogo-ao-vivo-e-odd.html) | Linha ao vivo e **todos os estados** do botão de odd |
| [`04c-linha-acao-home.html`](markup/04c-linha-acao-home.html) | Linha compacta da home ("Junte-se à ação") |
| [`05-bilhete-cupom.html`](markup/05-bilhete-cupom.html) | Bilhete: cupom flutuante + bilhete completo (com 1 seleção real) |
| [`06-footer.html`](markup/06-footer.html) | Rodapé de 5 colunas |
| [`07-bottom-nav.html`](markup/07-bottom-nav.html) | Barra de navegação mobile (+ botão do cassino) |
| [`08-modal-entrar.html`](markup/08-modal-entrar.html) | Modal "Entrar" |
| [`09-modal-cadastro.html`](markup/09-modal-cadastro.html) | Modal "Criar conta" |
| [`10-modal-busca.html`](markup/10-modal-busca.html) | Modal de busca de jogos |
| [`11-modal-turbinada.html`](markup/11-modal-turbinada.html) | Modal informativo (modelo com passos numerados) |
| [`12-home-aside-cards.html`](markup/12-home-aside-cards.html) | Coluna direita: Minhas apostas + Ofertas |
| [`13-pagina-lista-aovivo.html`](markup/13-pagina-lista-aovivo.html) | Página de lista (page-head + sport-bar + container) |
| [`14-cassino.html`](markup/14-cassino.html) | Estrutura da página do cassino |
| [`14b-cassino-cards.html`](markup/14b-cassino-cards.html) | Cards/seções do cassino |
| [`15-avisos-toasts-estados.html`](markup/15-avisos-toasts-estados.html) | Avisos, tooltips, estados vazios, toasts |
| [`16-pagina-afiliados.html`](markup/16-pagina-afiliados.html) | Landing de afiliados (standalone, CSS próprio) |

## Assets baixados

Em `C:\wwwroot\banca\_temp\v4ref\assets\` — **51 arquivos, ~7,2 MB**:

| Pasta | Conteúdo |
|---|---|
| `webfonts/` | 15 arquivos Font Awesome Pro 5.11.2 (woff2/woff/ttf × 5 famílias) |
| `fonts/google/` | 20 `.woff2` (Anton, Barlow Condensed, Bricolage Grotesque, Geist, Poppins) |
| `img/` | logo (319×122) + 4 banners (1530×400, 1600×721) |
| `js/` | 7 JS do tema (v4, v4-home, v4-cupom, tema-view, v4-cassino, v4-carteira, v4-conta) |
| `css/` | `all.min.css` + `v4/{fonts,app,v4}.css` |
| `assets-manifest.json` | origem, destino, bytes e status de cada arquivo |

## Dados brutos da análise (fora de `docs/`)

Em `C:\wwwroot\banca\_temp\v4ref\` (não versionar como documentação):

- `pages/*.html` — 19 páginas capturadas do v4 (algumas com 1–2 MB);
- `css_v4_*.css`, `css_all.min.css`, `js_v4*.js` — originais;
- `blocks/` — blocos renderizados pelo `/router` do v4 (`destaques.html`, `ligas.html`,
  `lateral.html`, `bilhete-preenchido.html`) + JSONs originais;
- `_asset_urls.txt` — as 2.523 URLs de imagem encontradas nas páginas;
- `_svgs_home.txt` / `_svgs_aovivo.txt` — SVGs distintos por página;
- `_app_*.css` — extratos de regras do `app.css` por área;
- `pretty_amanha_ligas.html` — lista de jogos identada para leitura;
- `cssq.ps1`, `baixar-assets.ps1`, `gerar-markup.ps1` — scripts usados na análise.

## Notas

- O v4 é um app PHP próprio em `/v4/`; **nada do backend é reaproveitável**. Só a aparência.
- O tema novo é **Twig** no sistema atual: as rotas `/v4/...` e `/hub/...` dos recortes precisam
  ser trocadas pelas rotas do sistema atual.
- Cores da marca: `--primary-color: #263e0f` (verde-oliva) e `--text-primary-color: #d4ec00`
  (verde-limão). O resto dos tokens está no `spec.md`.
- As páginas de conta do v4 são o **HUB** (Bootstrap 3) — não têm design v4. Ver `gaps.md`.
