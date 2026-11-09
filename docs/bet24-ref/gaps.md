# Gaps: o que do v4 NÃO é reaproveitável no tema novo

Este documento lista o que, no site antigo `bet24hs.site/v4`, **depende do backend/produto v4** e
precisa ser **refeito** no sistema atual (`C:\wwwroot\banca`) — tanto no backend/PHP quanto na
camada de Twig/JS do tema "bet24".

> Captura feita em **30/10/2026, ~23h40 (America/Sao_Paulo)**. O site antigo é dinâmico: os jogos,
> odds e banners mudam a cada minuto. O markup congelado está em `_temp/v4ref/`.

---

## 1. Arquitetura do v4 (por que não se copia)

O v4 é um **aplicativo PHP próprio** servido em `/v4/`, com:

- controller/renderer de páginas (`/v4/index.php`, `/v4/aovivo.php`, …) e partials (`partials/*.php`,
  `partials/helpers.php`) que geram o HTML das linhas de jogo (`v4_linha_jogo`, `v4_linha_acao`,
  `v4_ligas`, `v4_tags_canto`);
- um **router central** `/router?path=<recurso>` que devolve **HTML** (blocos da home) ou **JSON**
  (jogos, escolhas do bilhete, caixa, cassino);
- **APIs próprias** (`/api/partidas_aovivo`, `/api/mobile/*`, `/api/pix`, `/api/cpf`,
  `/api/checkdeposit`, `/api/cashout*`);
- um subsistema de conta separado (**HUB**, `/hub/*` — outro design system, Bootstrap 3);
- integração com provedores de dados de futebol e de cassino.

Nada disso existe no sistema atual com os mesmos contratos. **O que se aproveita é a aparência**:
CSS, markup/classes, assets e a lógica de interface (tema claro/escuro, cupom flutuante,
colapso de campeonato, carrosséis). Toda a ligação com dados precisa ser refeita.

---

## 2. Endpoints do v4 que aparecem no front (todos a substituir)

### 2.1 Home e listas

| Endpoint | O que devolve | Usado em |
|---|---|---|
| `GET /router?path=v4.home&bloco=jogos[&hash=]` | JSON `{dados:{principais[],demais[],amanha[],icones,vazio,dia_semana}}` | `v4-home.js` |
| `GET /router?path=v4.home&bloco=destaques` | JSON `{dados:{html}}` | carrossel "Destaques" |
| `GET /router?path=v4.home&bloco=stories` | JSON `{dados:{computador,celular,lista,marca}}` | stories |
| `GET /router?path=v4.home&bloco=ligas` | JSON `{dados:{html}}` | "Ligas populares" (mobile) |
| `GET /router?path=v4.home&bloco=lateral` | JSON `{dados:{html}}` | "Principais ligas" da sidebar |
| blocos `superodds`, `superdicas`, `dicas` | JSON `{dados:{html}}` | seções extras da home (vazias na captura) |
| `GET /api/partidas_aovivo` | JSON `{partidas:[…]}` | página ao vivo (`aovivo.html`) — o HTML da linha é montado no JS |
| `GET /router?path=bilhetes.escolhas` | lista de ids de odds já no bilhete | marcar `.active` nas odds |

### 2.2 Bilhete (carrinho)

| Endpoint | Usado em |
|---|---|
| `/router?path=apostar.adicionar&tema=V3&jogo=<jogo>&taxa=<cotação>` | adicionar seleção |
| `/router?path=apostar.remover&tema=V3&jogo=<jogo>&taxa=<cotação>` | remover |
| `/router?path=apostar.valor&valor=<valor>` | atualizar valor apostado |
| `/router?path=apostar.finalizar&tema=V3` | confirmar aposta |
| `/router?path=bilhetes.compartilhar`, `/router?path=bilhetes.importar&bilhete=<cod>` | compartilhar/importar bilhete |
| `/api/mobile/adicionar_aovivo`, `/remover_aovivo`, `/atualizar_valor`, `/finalizar_aovivo` | bilhete ao vivo |
| `/api/pix?codigo=…`, `/api/cpf?cpf=…`, `/api/finalizar_acumuladao` | pagamento PIX / validação de CPF / acumuladão |

O `v4.js` (Parte 2) é literalmente o JS do v3 apontado para `tema=V3&app=v3` — **não portar**.

### 2.3 Conta / carteira

| Endpoint | Usado em |
|---|---|
| `/hub/entrar`, `/hub/cadastro`, `/hub/recuperar-senha` | login/cadastro/recuperação |
| `/router?path=bilhetes.cancelar&id=…`, `/router?path=caixa&subTipo=…` | `v4-conta.js` |
| `/api/cashout`, `/api/cashout_info?id=…` | encerrar aposta |
| `/api/checkdeposit?id=…` | polling do PIX (`v4-carteira.js`) |
| `/router?path=cassino.inicio\|jogos\|abrir` | cassino (`v4-cassino.js`) |

**No tema novo, tudo isso deve apontar para as rotas/controllers do sistema atual** (Twig). As
classes visuais continuam válidas; os `href`/`action`/`fetch` não.

---

## 3. Área de conta: o v4 NÃO tem design próprio

Descoberta importante: as páginas de conta do v4 (`/v4/conta`, `/v4/depositos`, `/v4/saques`,
`/v4/perfil`, `/v4/apostas`) **redirecionam para o HUB** (`/hub/entrar`) quando o usuário não está
logado. As 5 páginas capturadas (`pages/conta.html`, `depositos.html`, `saques.html`,
`perfil.html`, `apostas.html`) são **exatamente o mesmo arquivo** (MD5 idêntico, 15.705 bytes): a
**página de login do HUB**, com outro design system:

- Bootstrap 3.3.7 (`bootstrap.min.css`, `bootstrap.min.js`) + jQuery 3.6 + jQuery UI 1.13;
- `dist/css/hub-theme.css` ("Sistema de design do HUB — reskin em cima do Bootstrap 3");
- tema claro/escuro **com outro mecanismo**: `localStorage['hub_theme']` + classe `.theme-dark`
  no `<html>` (≠ `localStorage['Tema']` + `data-theme` do v4);
- variáveis `--primary-color`/`--primary-text` próprias (na captura: `#2980b9`).

O v4 embute essas páginas num **iframe dentro do modal "Minha conta"**
(`#v4ContaModal`, `html.v4-embed`, `partials/conta-modal.php`), escondendo topo/rodapé e navegando
para fora quando o link não é de conta.

**Consequências para o tema novo:**

1. Se o sistema atual tem área de conta própria, ela precisa ser **re-estilizada** no visual do
   tema bet24 (não existe referência pronta no v4 para isso — só a moldura do modal).
2. Se for mantida a ideia de modal-ifram, o `html.v4-embed` e o CSS correspondente
   (`.v4-conta-modal`, `.v4-cm-*`, `.acct-*`) podem ser reaproveitados como estrutura, mas o
   conteúdo interno é do HUB.
3. **Não** copiar o Bootstrap 3 para o tema novo sem necessidade — ele conflita com o tema v4
   (que não usa Bootstrap).

---

## 4. Dependências externas / de produto

| Dependência | O que é | Precisa refazer? |
|---|---|---|
| `images.wolfsistemas.me` | escudos de times (`/s/<hash>.png`) e bandeiras (`/flags/<pais>.png`) | **sim** — usar o provedor/CDN do sistema atual; manter classes `.logo`/`.flag` |
| CDN `cdn.mindsports.online/<DB_NAME>/...` | logo, banners, og:image | **sim** (ou reenviar as imagens baixadas para o CDN atual). `DB_NAME_L` é placeholder literal de configuração |
| API de futebol (jogos ao vivo, campeonatos) | alimenta linhas, placares, odds | **sim** — o sistema atual tem o próprio `jogos.php`/`apifutebol` |
| Provedor de cassino (PlayFivers etc.) | capas e jogos | só se o cassino existir no site novo |
| **hCaptcha** — sitekey `500e2c08-0ca9-4115-8604-da1f28727c05` | usado nos modais de cadastro e recuperação | **sim** — trocar pela chave do site atual |
| **Font Awesome Pro 5.11.2** | ícones | avaliar licença; alternativa FA Free 5.15.4 (perde `fal`) |
| Google Fonts (gstatic) | Anton, Barlow Condensed, Bricolage Grotesque, Geist, Poppins | **não** — já baixadas em `assets/fonts/google/`; reescrever o `fonts.css` |
| SweetAlert2 v11 (jsDelivr) | avisos/erros | pode manter ou trocar pelo alerta do sistema |
| jQuery 1.12 (Google CDN) | base dos JS do tema | o v4 depende dele; o sistema atual pode ter outra versão |
| jQuery Mask 1.14.16 | máscaras de CPF/telefone | pode manter |
| `sw.js` (service worker) | o v4 manda **desregistrar** SW antigo (`navigator.serviceWorker.getRegistrations()`) | descartar |

### 4.1 Rastreamento (CRM / Meta Pixel / UTM)

O `<head>` do v4 contém:

- script que grava `v4_fbclid`, `v4_fbclid_ts`, `v4_utm_source`, `v4_utm_campaign` em cookies por
  90 dias (lidos pelo servidor no cadastro/depósito/aposta);
- `window.v4Evento(nome, dados, idEvento)` para GTM/Pixel — **desligado** na captura
  (`temGtm = false`, `temPixel = false`), com os eventos `cadastro`, `pix_gerado`, `deposito`
  (primeiro vira `ftd`), `aposta`.

**Não é visual** — se o site novo tiver tracking, reimplementar com os cookies/eventos do sistema
atual. Não copiar os nomes `v4_*`.

---

## 5. Funcionalidades do front que precisam de backend equivalente

Sem o backend, estes pedaços do tema ficam "mortos" e precisam ser religados:

1. **Bilhete (carrinho)** — `v4.js` Parte 2 + `v4-cupom.js`. O `v4-cupom.js` só **lê o DOM**
   (`#qntyItens`, `#lblCotacao`, `.ticket-item`), então pode ser reaproveitado assim que o
   sistema atual renderizar o bilhete com os mesmos ids/classes. As chamadas de
   adicionar/remover/finalizar são do backend e devem ser trocadas.
2. **Home em AJAX** — opcional. O tema novo pode renderizar os jogos direto no Twig (como
   `amanha.html` faz) e ignorar os blocos AJAX, ganhando simplicidade. Se quiser o efeito de
   carregar em lotes/skeletons, precisa de um endpoint que devolva os jogos em JSON.
3. **Busca** — o v4 tem dois níveis: filtro local por `data-search` (`v4FiltrarJogos`) e modal de
   busca que consulta `/router`/API. O filtro local é reaproveitável; a busca de servidor não.
4. **Stories** — depende de conteúdo cadastrado no CRM do v4 (lista + imagens). No tema novo,
   pode virar um carrossel de banners/ofertas ou ser alimentado por uma tabela do sistema.
5. **SuperOdds / SuperDicas / Dicas / Múltiplas do dia** — blocos gerados pelo backend v4
   (vazios na captura). Precisam de lógica equivalente (ou serem omitidos).
6. **Acumuladão / Longo prazo** — regras e dados no backend v4; só o visual (`v4-acu-*`,
   `v4-longo-*`) é reaproveitável.
7. **Cassino** — catálogo e provedores do v4; sem integração, remover as seções `.cas-*`.
8. **Ao vivo** — `/api/partidas_aovivo` a cada 20 s e o `.match-row.is-live`/`.odd-up`/`.odd-down`
   montados no cliente. No sistema atual, adaptar ao formato de dados existente.
9. **Importar bilhete por código**, **compartilhar bilhete (base64/WhatsApp)**, **cash-out**,
   **criar aposta turbinada (CA)**, **pagamento antecipado (PA)** — todos são produto/backend do v4.
10. **Troca de layout v4↔v5** (`.topbar-view`, `tema-view.js`, cookie `themeView`, constante
    `TEMANOVO`) — **descartar**.

---

## 6. Detalhes de markup que NÃO devem ser copiados como estão

| Item | Problema | O que fazer |
|---|---|---|
| `href="/v4/…"`, `/v4/jogos/…`, `/v4/campeonatos/…`, `/v4/stats/…` | rotas do v4 | trocar pelas rotas do sistema atual (Twig) |
| `action="/v4/cadastro"`, `/hub/entrar`, `/hub/recuperar-senha` | backend v4/hub | apontar para cadastro/login atuais |
| `onclick="adicionaAovivo(…)`, `remover(…)`, `ObterBilheteBase64('all')`, `AtualizarValor(…)` | funções globais do JS v4 | trocar pela camada JS do tema novo |
| `data-jogo-id` / `data-id` / `data-off` / `.prematch` / `.active` | ganchos do bilhete v4 | manter o **papel** (identificar jogo/cotação) com os nomes do sistema novo ou compatibilizar |
| `style="color:#000; text-decoration:none"` no `.ticket-item-data a` | **bug** do backend: no tema escuro o link fica preto sobre fundo escuro | não copiar; usar `color: inherit` |
| `#tagQuantidade`, `#tagCotacao`, `#tagPremio`, `#tagApostado`, `#jsonEscolhas` duplicados no `.ticket-info` | markup repete os mesmos `id` duas vezes (HTML inválido) | emitir uma vez só |
| `src="https://cdn.mindsports.online/DB_NAME_L/…"` | placeholder literal `DB_NAME_L` | trocar pelo CDN do site atual |
| `data-sitekey="500e2c08-…"` | chave hCaptcha do v4 | trocar pela do site atual |
| `id="v4TurbinadaModal"`, `#v4Cupom`, `#v4Aside`, `#v4Topo`, `#v4BuscaModal`, `#v4EntrarModal`, `#v4CadastroModal` | prefixo `v4-` | manter se quiser reaproveitar o JS; senão renomear para `bet24-*` |
| `.topbar-view` / `data-theme-view="v5"` / texto "V5" | botão de troca de layout | remover |
| `html.v4-embed …` (CSS) | modo iframe de conta | só se usar modal-ifram |
| `<script>if('serviceWorker' in navigator){…unregister…}</script>` | limpeza de SW antigo | remover |
| `.v4-rodape` com “© 2026 - Bet24hs.site” e links para `/v4/paginas/*` | conteúdo do site antigo | trocar textos/links do site novo |
| Redes sociais do rodapé apontando para Instagram/WhatsApp do bet24hs | links do antigo dono | trocar |
| Comentários e caminhos absolutos nos recortes (`pages/home.html`) | são referência de captura | usar `docs/bet24-ref/markup/*.html` (já recortados) |

---

## 7. Compatibilidade de navegador / stack

O CSS do v4 usa recursos modernos:

| Recurso | Onde | Risco |
|---|---|---|
| `color-mix()` | largamente (tokens, hovers) | Chrome 111+/Safari 16.2+; sem suporte em navegadores antigos |
| `:has()` | `.v4-aside:has(...)`, `.app:has(.slip)` | Chrome 105+/Safari 15.4+ |
| `@media (width<=860px)` (range) | todo o CSS | Chrome 104+ |
| `aspect-ratio` | cassino, campos | amplo |
| `env(safe-area-inset-*)` | bottom-nav/cupom | iOS Safari |
| `mask-image` / `-webkit-mask` | chips, sport-bar, destaques | precisa prefixo |
| `font-variant-numeric: tabular-nums` | preços | amplo |
| `dvh` | `.cas-side`, `.app-game` | Chrome 108+ |

Se o público do sistema atual usa navegadores antigos, converter (o visual não muda).

---

## 8. O que fazer com os arquivos "pesados" da captura

- `pages/amanha.html` (2,2 MB), `pages/depois-amanha.html` (1 MB), `pages/proximos.html` (322 KB)
  são listas renderizadas no servidor **com dados daquele dia** (out/2026). Servem como
  **referência de markup repetido**, não como arquivo para copiar.
- Para construir, use os recortes em `docs/bet24-ref/markup/` (um componente por arquivo).
- Blocos AJAX renderizados foram salvos em `_temp/v4ref/blocks/` (`destaques.html`, `ligas.html`,
  `lateral.html`, `bilhete-preenchido.html` + JSONs originais).

---

## 9. Resumo: o que dá e o que não dá para reaproveitar

### Dá para reaproveitar (copiar/adaptar)

- `all.min.css` + webfonts do Font Awesome (avaliar licença).
- `fonts.css` reescrito para os `.woff2` locais (já baixados).
- `app.css` (base) e `v4.css` (marca) — com cortes das seções sem equivalente.
- Todos os **tokens de design** (cores/fontes/medidas/breakpoints) — documentados no `spec.md`.
- Os **componentes visuais**: topbar/topnav, banners, destaques, stories, sport-bar, chips,
  campeonato (`league`), linhas de jogo (pré-jogo, ao vivo, compacta), botão de odd (todos os
  estados), cartões da coluna direita, bilhete/cupom (markup e CSS), modais, avisos/tooltips,
  rodapé, bottom-nav, cassino (visual), acumuladão (visual).
- `v4-cupom.js` (DOM puro) e os **templates HTML** do `v4-home.js`.
- Logo e banners (imagens baixadas).

### Não dá para reaproveitar (refazer)

- Rotas e endpoints (`/router`, `/api/*`, `/hub/*`) e todo o JS de produto (Parte 2 do `v4.js`,
  `v4-carteira.js`, `v4-conta.js`, `v4-cassino.js`).
- Área de conta (HUB com Bootstrap 3) — precisa de design próprio no tema novo.
- Dados de jogos/odds/escudos/bandeiras e de cassino.
- hCaptcha, tracking/UTM/fbclid, cookies `v4_*`/`hub_theme`/`themeView`, service worker.
- Troca de layout v4↔v5.
