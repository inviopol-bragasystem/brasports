(function () {
  "use strict";

  const body = document.body;
  const DESKTOP = 1024;
  // Base da API: data-api-base do layout OU deriva do path atual (funciona em subpasta)
  function resolveApiBase() {
    var fromDom = (document.body.dataset.apiBase || "").trim();
    if (fromDom) {
      if (fromDom.slice(-1) !== "/") fromDom += "/";
      return fromDom;
    }
    var path = window.location.pathname || "/";
    // /onewin/index.php → /onewin/ | /index.php → /
    path = path.replace(/\/index\.php$/i, "/");
    if (path.slice(-1) !== "/") {
      path = path.replace(/\/[^/]*$/, "/");
    }
    return path || "/";
  }
  const apiBase = resolveApiBase();
  const csrfToken = document.body.dataset.csrf || "";
  const accountUrl = document.body.dataset.accountUrl || apiBase + "index.php?page=account";

  /** Tradução JS: lê o dicionário exposto pelo layout (window.__LANG__) */
  function tr(key, fb) {
    try {
      var d = window.__LANG__ || {};
      var v = d[key];
      if (v === undefined || v === null || v === key) {
        return fb !== undefined ? fb : key;
      }
      return String(v);
    } catch (e) {
      return fb !== undefined ? fb : key;
    }
  }

  /** Moeda/símbolo/separadores conforme idioma (body data-*) */
  function moneyMeta() {
    var b = document.body || {};
    var d = b.dataset || {};
    return {
      currency: d.currency || "BRL",
      symbol: d.currencySymbol || "R$",
      decimal: d.moneyDecimal || ",",
      thousands: d.moneyThousands || ".",
    };
  }
  function formatMoney(n) {
    var m = moneyMeta();
    var v = Number(n);
    if (isNaN(v)) v = 0;
    var parts = v.toFixed(2).split(".");
    var intPart = parts[0];
    var decPart = parts[1] || "00";
    // milhares
    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, m.thousands);
    return m.symbol + " " + intPart + m.decimal + decPart;
  }

  /* Máscara simples de CPF */
  function maskCpf(v) {
    v = String(v || "").replace(/\D/g, "").slice(0, 11);
    if (v.length > 9) return v.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, "$1.$2.$3-$4");
    if (v.length > 6) return v.replace(/(\d{3})(\d{3})(\d{0,3})/, "$1.$2.$3");
    if (v.length > 3) return v.replace(/(\d{3})(\d{0,3})/, "$1.$2");
    return v;
  }
  document.querySelectorAll("[data-cpf-mask]").forEach(function (el) {
    el.addEventListener("input", function () {
      var pos = el.selectionStart;
      var before = el.value.length;
      el.value = maskCpf(el.value);
      var after = el.value.length;
      try {
        el.setSelectionRange(pos + (after - before), pos + (after - before));
      } catch (e) {}
    });
  });

  function withCsrf(payload) {
    if (payload instanceof FormData) {
      if (csrfToken) payload.append("_csrf", csrfToken);
      return payload;
    }
    var o = payload && typeof payload === "object" ? Object.assign({}, payload) : {};
    if (csrfToken) o._csrf = csrfToken;
    return o;
  }

  function isDesktop() {
    return window.innerWidth >= DESKTOP;
  }

  /* ---------- Toast + Confirm (substitui alert/confirm nativos) ---------- */
  function ensureToastHost() {
    var host = document.getElementById("app-toast-host");
    if (host) return host;
    host = document.createElement("div");
    host.id = "app-toast-host";
    host.className = "app-toast-host";
    host.setAttribute("aria-live", "polite");
    document.body.appendChild(host);
    return host;
  }

  function showToast(message, type, opts) {
    type = type || "info";
    opts = opts || {};
    var host = ensureToastHost();
    var el = document.createElement("div");
    el.className = "app-toast app-toast--" + type;
    el.setAttribute("role", "status");
    var icons = { success: "✓", error: "!", info: "i", warn: "!" };
    el.innerHTML =
      '<span class="app-toast__icon" aria-hidden="true">' +
      (icons[type] || "i") +
      "</span>" +
      '<div class="app-toast__body">' +
      '<div class="app-toast__msg"></div></div>' +
      '<button type="button" class="app-toast__close" aria-label="Fechar">×</button>';
    el.querySelector(".app-toast__msg").textContent = String(message || "");
    host.appendChild(el);
    requestAnimationFrame(function () {
      el.classList.add("is-in");
    });
    var ttl = opts.duration != null ? opts.duration : type === "error" ? 5200 : 3800;
    var timer = setTimeout(close, ttl);
    function close() {
      clearTimeout(timer);
      el.classList.remove("is-in");
      el.classList.add("is-out");
      setTimeout(function () {
        if (el.parentNode) el.parentNode.removeChild(el);
      }, 280);
    }
    el.querySelector(".app-toast__close").addEventListener("click", close);
    return { close: close };
  }

  function showConfirm(opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      var existing = document.getElementById("app-confirm-modal");
      if (existing) existing.remove();
      var overlay = document.createElement("div");
      overlay.id = "app-confirm-modal";
      overlay.className = "app-confirm";
      overlay.setAttribute("role", "dialog");
      overlay.setAttribute("aria-modal", "true");
      var hideCancel = !!opts.hideCancel;
      overlay.innerHTML =
        '<div class="app-confirm__card">' +
        '<div class="app-confirm__icon app-confirm__icon--' +
        (opts.variant || "accent") +
        '" aria-hidden="true">' +
        (opts.icon || "?") +
        "</div>" +
        '<h3 class="app-confirm__title"></h3>' +
        '<p class="app-confirm__text"></p>' +
        (opts.detailHtml
          ? '<div class="app-confirm__detail">' + opts.detailHtml + "</div>"
          : "") +
        '<div class="app-confirm__actions">' +
        (hideCancel
          ? ""
          : '<button type="button" class="btn btn--ghost app-confirm__cancel"></button>') +
        '<button type="button" class="btn btn--accent app-confirm__ok"></button>' +
        "</div></div>";
      overlay.querySelector(".app-confirm__title").textContent = opts.title || "Confirmar";
      overlay.querySelector(".app-confirm__text").textContent = opts.message || "";
      var cancelBtn = overlay.querySelector(".app-confirm__cancel");
      if (cancelBtn) cancelBtn.textContent = opts.cancelText || "Cancelar";
      overlay.querySelector(".app-confirm__ok").textContent = opts.okText || "Confirmar";
      if (opts.danger) {
        overlay.querySelector(".app-confirm__ok").classList.remove("btn--accent");
        overlay.querySelector(".app-confirm__ok").classList.add("btn--danger");
      }
      document.body.appendChild(overlay);
      requestAnimationFrame(function () {
        overlay.classList.add("is-open");
      });
      function finish(val) {
        overlay.classList.remove("is-open");
        setTimeout(function () {
          if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        }, 200);
        resolve(val);
      }
      if (cancelBtn) {
        cancelBtn.addEventListener("click", function () {
          finish(false);
        });
      }
      overlay.querySelector(".app-confirm__ok").addEventListener("click", function () {
        finish(true);
      });
      overlay.addEventListener("click", function (e) {
        if (e.target === overlay) finish(false);
      });
      document.addEventListener(
        "keydown",
        function onKey(e) {
          if (e.key === "Escape") {
            document.removeEventListener("keydown", onKey);
            finish(false);
          }
        },
        { once: true }
      );
    });
  }

  // expõe para scripts inline de páginas (logout etc.)
  window.showToast = showToast;
  window.showConfirm = showConfirm;

  /* ---------- Language switcher (pt-BR / pt / EN / ES) ---------- */
  (function initLangSwitch() {
    var wrap = document.getElementById("lang-switch");
    var btn = document.getElementById("lang-switch-btn");
    var menu = document.getElementById("lang-switch-menu");
    if (!wrap || !btn || !menu) return;

    function closeMenu() {
      menu.hidden = true;
      btn.setAttribute("aria-expanded", "false");
      btn.classList.remove("is-open");
    }
    function openMenu() {
      menu.hidden = false;
      btn.setAttribute("aria-expanded", "true");
      btn.classList.add("is-open");
    }

    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      if (menu.hidden) openMenu();
      else closeMenu();
    });

    document.addEventListener("click", function (e) {
      if (!wrap.contains(e.target)) closeMenu();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeMenu();
    });

    menu.querySelectorAll("[data-set-lang]").forEach(function (item) {
      item.addEventListener("click", async function () {
        var lang = item.getAttribute("data-set-lang");
        if (!lang) return;
        item.disabled = true;
        try {
          var fd = new FormData();
          fd.append("lang", lang);
          var res = await fetch(apiBase + "api/set_lang.php", {
            method: "POST",
            body: fd,
            credentials: "same-origin",
          });
          var data = await res.json();
          if (data && data.ok) {
            try {
              localStorage.setItem("onewin_lang", data.locale || lang);
            } catch (err) {}
            // recarrega página no mesmo path (sem ?lang sujo)
            var u = new URL(window.location.href);
            u.searchParams.delete("lang");
            window.location.href = u.toString();
            return;
          }
        } catch (err) {
          // fallback GET
          var u2 = new URL(window.location.href);
          u2.searchParams.set("lang", lang);
          window.location.href = u2.toString();
          return;
        }
        item.disabled = false;
        closeMenu();
      });
    });
  })();

  /* ---------- Sidebar ----------
     Desktop: aberta por padrão; compacta pela seta sob a imagem.
     Mobile: overlay; abre pelo Menu do tabbar.
  */
  const sidebar = document.getElementById("sidebar");
  const sidebarOverlay = document.getElementById("sidebar-overlay");
  const expandBtn = document.getElementById("sidebar-expand");

  function openMobileSidebar() {
    if (!sidebar) return;
    sidebar.classList.add("is-open");
    sidebarOverlay && sidebarOverlay.classList.add("is-open");
    body.classList.add("modal-open");
  }
  function closeMobileSidebar() {
    if (!sidebar) return;
    if (isDesktop()) return; // desktop não "fecha" de verdade
    sidebar.classList.remove("is-open");
    sidebarOverlay && sidebarOverlay.classList.remove("is-open");
    if (!document.querySelector(".modal-root.is-open")) {
      body.classList.remove("modal-open");
    }
  }

  // Desktop compact toggle (seta sob a imagem)
  function setCompact(compact) {
    body.classList.toggle("sidebar-compact", !!compact);
    try {
      localStorage.setItem("onewin_sidebar_compact", compact ? "1" : "0");
    } catch (e) {}
    if (expandBtn) {
      expandBtn.setAttribute("aria-label", compact ? "Expandir menu" : "Recolher menu");
      expandBtn.title = compact ? "Expandir" : "Recolher";
    }
  }

  // restore compact preference (desktop only)
  try {
    if (isDesktop() && localStorage.getItem("onewin_sidebar_compact") === "1") {
      setCompact(true);
    }
  } catch (e) {}

  // Desktop: sidebar sempre "aberta" (visivel). Mobile: fechada.
  if (sidebar) {
    if (isDesktop()) sidebar.classList.add("is-open");
    else sidebar.classList.remove("is-open");
  }

  document.querySelectorAll("[data-open-sidebar]").forEach((b) =>
    b.addEventListener("click", openMobileSidebar)
  );
  document.querySelectorAll("[data-close-sidebar]").forEach((b) =>
    b.addEventListener("click", () => {
      // no mobile fecha; no desktop deixa navegar
      closeMobileSidebar();
    })
  );
  sidebarOverlay && sidebarOverlay.addEventListener("click", closeMobileSidebar);

  if (expandBtn) {
    expandBtn.addEventListener("click", () => {
      setCompact(!body.classList.contains("sidebar-compact"));
    });
  }

  window.addEventListener("resize", () => {
    if (isDesktop()) {
      sidebar && sidebar.classList.add("is-open");
      sidebarOverlay && sidebarOverlay.classList.remove("is-open");
      body.classList.remove("modal-open");
    } else {
      // mobile: fecha se não estiver explícito
      sidebar && sidebar.classList.remove("is-open");
      body.classList.remove("sidebar-compact");
    }
  });

  /* Sidebar collapsible groups */
  document.querySelectorAll("[data-toggle-group]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const expanded = btn.getAttribute("aria-expanded") === "true";
      btn.setAttribute("aria-expanded", expanded ? "false" : "true");
      const items = btn.parentElement && btn.parentElement.querySelector(".sidebar-group__items");
      if (items) items.classList.toggle("is-open", !expanded);
    });
  });

  /* ---------- Auth modals ---------- */
  const loginModal = document.getElementById("modal-login");
  const registerModal = document.getElementById("modal-register");

  function openModal(el) {
    if (!el) return;
    closeMobileSidebar();
    document.querySelectorAll(".modal-root.is-open").forEach((m) => m.classList.remove("is-open"));
    el.classList.add("is-open");
    body.classList.add("modal-open");
  }
  function closeModals() {
    document.querySelectorAll(".modal-root.is-open").forEach((m) => m.classList.remove("is-open"));
    if (!sidebar || !sidebar.classList.contains("is-open") || isDesktop()) {
      body.classList.remove("modal-open");
    }
  }

  /* Forgot Password */
  const forgotPasswordModal = document.getElementById("modal-forgot-password");

  document.querySelectorAll("[data-open-login]").forEach((b) =>
    b.addEventListener("click", (e) => {
      e.preventDefault();
      openModal(loginModal);
    })
  );
  document.querySelectorAll("[data-open-register]").forEach((b) =>
    b.addEventListener("click", (e) => {
      e.preventDefault();
      openModal(registerModal);
    })
  );
  document.querySelectorAll("[data-open-forgot-password]").forEach((b) =>
    b.addEventListener("click", (e) => {
      e.preventDefault();
      openModal(forgotPasswordModal);
    })
  );
  document.querySelectorAll("[data-close-modal]").forEach((b) =>
    b.addEventListener("click", closeModals)
  );
  document.querySelectorAll(".modal-backdrop").forEach((b) =>
    b.addEventListener("click", closeModals)
  );

  const params = new URLSearchParams(location.search);
  const auth = params.get("auth");
  if (auth === "login") openModal(loginModal);
  if (auth === "register") {
    openModal(registerModal);
  }

  async function postJson(url, data) {
    const res = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(withCsrf(data)),
      credentials: "same-origin",
      redirect: "follow",
    });
    // 302/HTML em vez de JSON = URL da API errada ou sessão/redirect
    const ct = (res.headers.get("content-type") || "").toLowerCase();
    if (ct.indexOf("application/json") === -1) {
      return {
        ok: false,
        status: res.status,
        json: { error: "API retornou HTML/redirect (HTTP " + res.status + "). Recarregue a página." },
      };
    }
    const json = await res.json().catch(() => ({}));
    return { ok: res.ok && json.ok, status: res.status, json };
  }

  /* ---------- Captcha (conta já vem do PHP; JS só renova) ---------- */
  var registerCaptcha = null;
  (function setupMathCaptcha() {
    var qEl = document.getElementById("reg-captcha-q");
    var idEl = document.getElementById("reg-captcha-id");
    var ansEl = document.getElementById("reg-captcha-answer");
    var reloadBtn = document.getElementById("reg-captcha-reload");
    if (!qEl || !idEl) return;

    var loading = false;

    async function loadChallenge() {
      if (loading) return;
      loading = true;
      if (reloadBtn) reloadBtn.disabled = true;
      try {
        var res = await fetch(apiBase + "api/captcha.php?action=create&_=" + Date.now(), {
          method: "GET",
          credentials: "same-origin",
          headers: { Accept: "application/json" },
        });
        var data = await res.json().catch(function () {
          return {};
        });
        if (data && data.ok) {
          idEl.value = data.id || "";
          // mostra sempre "5 + 3 = ?" (display) — nunca apaga a conta
          qEl.textContent = data.display || (data.question ? data.question + " = ?" : qEl.textContent);
          if (ansEl) {
            ansEl.value = "";
            ansEl.focus();
          }
        }
      } catch (err) {
        // mantém a conta atual do PHP se a API falhar
      } finally {
        loading = false;
        if (reloadBtn) reloadBtn.disabled = false;
      }
    }

    registerCaptcha = {
      load: loadChallenge,
      reset: loadChallenge,
    };

    if (reloadBtn) {
      reloadBtn.addEventListener("click", function (e) {
        e.preventDefault();
        loadChallenge();
      });
    }
  })();

  function bindAuthForm(formId, endpoint) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const errEl = form.querySelector(".form-error");
      const btn = form.querySelector('[type="submit"]');
      if (errEl) errEl.textContent = "";

      if (formId === "form-register") {
        var ans = document.getElementById("reg-captcha-answer");
        var cid = document.getElementById("reg-captcha-id");
        if (!cid || !cid.value || !ans || String(ans.value).trim() === "") {
          if (errEl) errEl.textContent = "Digite o resultado da conta para continuar.";
          if (ans) ans.focus();
          return;
        }
      }

      const fd = new FormData(form);
      const payload = Object.fromEntries(fd.entries());
      if (btn) btn.disabled = true;
      try {
        const { ok, json } = await postJson(endpoint, payload);
        if (!ok) {
          if (errEl) errEl.textContent = json.error || "Erro. Tente novamente.";
          if (formId === "form-register" && registerCaptcha) {
            registerCaptcha.reset();
          }
          return;
        }
        if (formId === "form-register") {
          try { localStorage.setItem("pixel_track", JSON.stringify({event: "CompleteRegistration"})); } catch(e){}
        }
        setTimeout(function () {
          window.location.replace(apiBase + "index.php?page=home");
        }, 200);
      } catch (err) {
        if (errEl) errEl.textContent = "Erro de conexão.";
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  }

  bindAuthForm("form-login", apiBase + "api/login.php");
  bindAuthForm("form-register", apiBase + "api/register.php");

  /* Forgot Password form */
  (function () {
    const form = document.getElementById("form-forgot-password");
    if (!form) return;
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const btn = document.getElementById("forgot-btn");
      const errEl = form.querySelector(".form-error");
      const successEl = form.querySelector(".form-success");
      const email = document.getElementById("forgot-email").value.trim();

      if (!email) {
        if (errEl) { errEl.textContent = "Informe seu e-mail."; errEl.style.display = "block"; }
        return;
      }

      if (errEl) { errEl.textContent = ""; errEl.style.display = "none"; }
      if (successEl) { successEl.style.display = "none"; }
      if (btn) btn.disabled = true;

      try {
        const fd = new FormData(form);
        const payload = Object.fromEntries(fd.entries());
        const { ok, json } = await postJson(apiBase + "api/forgot_password.php", payload);
        if (!ok) {
          if (errEl) { errEl.textContent = json.error || "Erro. Tente novamente."; errEl.style.display = "block"; }
          return;
        }
        // Always show success even if email doesn't exist (security)
        if (successEl) {
          successEl.textContent = json.message || "Se o e-mail estiver cadastrado, você receberá instruções para redefinir sua senha.";
          successEl.style.display = "block";
        }
        if (errEl) errEl.style.display = "none";
        document.getElementById("forgot-email").value = "";
        btn.textContent = "Enviado ✓";
        setTimeout(() => {
          if (btn) btn.textContent = "Enviar link";
          closeModals();
        }, 3000);
      } catch (err) {
        if (errEl) { errEl.textContent = "Erro de conexão."; errEl.style.display = "block"; }
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  })();

  /* Perfil no mobile: navegação same-origin relativa (evita perder cookie/sessão) */
  (function bindProfileLink() {
    var link = document.getElementById("header-profile-link");
    if (!link) return;
    link.addEventListener(
      "click",
      function (e) {
        e.preventDefault();
        e.stopPropagation();
        var url = link.getAttribute("href") || accountUrl || "index.php?page=account";
        // força path relativo/absoluto no MESMO host — nunca troca de domínio
        try {
          var u = new URL(url, window.location.href);
          window.location.assign(u.pathname + u.search + u.hash);
        } catch (err) {
          window.location.href = url;
        }
      },
      { capture: true }
    );
  })();

  /* ---------- Banner carousel ---------- */
  document.querySelectorAll("[data-carousel]").forEach((root) => {
    const track = root.querySelector(".banner-carousel__track");
    const slides = root.querySelectorAll(".banner-slide");
    const dotsWrap = root.querySelector(".banner-carousel__dots");
    if (!track || slides.length < 2) return;

    let index = 0;
    let timer;

    function go(i) {
      index = (i + slides.length) % slides.length;
      track.style.transform = "translateX(-" + index * 100 + "%)";
      if (dotsWrap) {
        dotsWrap.querySelectorAll(".banner-carousel__dot").forEach((d, di) => {
          d.classList.toggle("is-active", di === index);
        });
      }
    }

    if (dotsWrap) {
      slides.forEach((_, i) => {
        const d = document.createElement("button");
        d.type = "button";
        d.className = "banner-carousel__dot" + (i === 0 ? " is-active" : "");
        d.addEventListener("click", () => {
          go(i);
          restart();
        });
        dotsWrap.appendChild(d);
      });
    }

    const prev = root.querySelector("[data-carousel-prev]");
    const next = root.querySelector("[data-carousel-next]");
    prev && prev.addEventListener("click", () => { go(index - 1); restart(); });
    next && next.addEventListener("click", () => { go(index + 1); restart(); });

    function restart() {
      clearInterval(timer);
      timer = setInterval(() => go(index + 1), 5000);
    }
    restart();

    let startX = 0;
    track.addEventListener("touchstart", (e) => { startX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener(
      "touchend",
      (e) => {
        const dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) {
          go(index + (dx < 0 ? 1 : -1));
          restart();
        }
      },
      { passive: true }
    );
  });

  /* ---------- Game row arrows ---------- */
  document.querySelectorAll("[data-row]").forEach((section) => {
    const row = section.querySelector(".game-row");
    const prev = section.querySelector("[data-row-prev]");
    const next = section.querySelector("[data-row-next]");
    if (!row) return;
    const scrollBy = () => Math.min(row.clientWidth * 0.8, 420);
    prev && prev.addEventListener("click", () => row.scrollBy({ left: -scrollBy(), behavior: "smooth" }));
    next && next.addEventListener("click", () => row.scrollBy({ left: scrollBy(), behavior: "smooth" }));
  });

  /* ---------- Sports filters + odds ---------- */
  document.querySelectorAll("[data-sports-filters]").forEach((wrap) => {
    const board = wrap.closest(".sports-board") || document;
    const cards = board.querySelectorAll(".match-card");
    wrap.querySelectorAll("[data-filter]").forEach((btn) => {
      btn.addEventListener("click", () => {
        wrap.querySelectorAll("[data-filter]").forEach((b) => b.classList.remove("is-active"));
        btn.classList.add("is-active");
        const f = btn.getAttribute("data-filter");
        cards.forEach((card) => {
          const sport = card.getAttribute("data-sport");
          const live = card.getAttribute("data-live") === "1";
          let show = true;
          if (f === "live") show = live;
          else if (f && f !== "all") show = sport === f;
          card.classList.toggle("is-hidden", !show);
        });
      });
    });
  });

  document.querySelectorAll(".odd-btn:not(:disabled)").forEach((btn) => {
    btn.addEventListener("click", () => {
      const card = btn.closest(".match-card");
      if (card) card.querySelectorAll(".odd-btn").forEach((b) => b.classList.remove("is-selected"));
      btn.classList.add("is-selected");
    });
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModals();
      closeMobileSidebar();
    }
  });

  /* ---------- Saldo: sync cassino → header ---------- */
  function formatMoneyBrLocal(n) {
    return formatMoney(n);
  }

  function setActiveCurrencyMeta(currency, symbol) {
    if (!currency) return;
    var c = String(currency).toUpperCase();
    document.body.dataset.currency = c;
    if (symbol) document.body.dataset.currencySymbol = symbol;
    var curEls = document.querySelectorAll(".header-balance__cur, [data-active-currency-label]");
    curEls.forEach(function (el) {
      el.textContent = c;
    });
    var hb = document.getElementById("header-balance");
    if (hb) hb.setAttribute("data-active-currency", c);
  }

  function applyBalanceToUi(balance, currency, symbol) {
    if (balance == null || isNaN(Number(balance))) return;
    if (currency) setActiveCurrencyMeta(currency, symbol);
    var txt = formatMoney(balance);
    document.querySelectorAll(".header-balance__value, [data-balance-value]").forEach(function (el) {
      el.textContent = txt;
    });
    var playBal = document.getElementById("play-balance");
    if (playBal) playBal.textContent = txt;
    var sportsBal = document.getElementById("sports-balance");
    if (sportsBal) sportsBal.textContent = txt;
    var depBal = document.getElementById("dep-balance");
    if (depBal) depBal.textContent = txt;
    var shellBal = document.getElementById("game-shell-balance");
    if (shellBal) shellBal.textContent = txt;
  }

  var balancePollTimer = null;
  function stopBalancePoll() {
    if (balancePollTimer) {
      clearInterval(balancePollTimer);
      balancePollTimer = null;
    }
  }
  async function refreshBalance() {
    if (document.body.dataset.isAuth !== "1") return null;
    try {
      var res = await fetch(apiBase + "api/balance.php?_=" + Date.now(), {
        credentials: "same-origin",
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        cache: "no-store",
      });
      var data = await res.json().catch(function () {
        return {};
      });
      if (data && data.ok && data.balance != null) {
        applyBalanceToUi(data.balance, data.currency, data.currency_symbol);
        return data.balance;
      }
    } catch (e) {}
    return null;
  }
  function startBalancePoll(ms) {
    stopBalancePoll();
    refreshBalance();
    balancePollTimer = setInterval(refreshBalance, ms || 4000);
  }

  // poll leve enquanto logado (todas as páginas)
  if (document.body.dataset.isAuth === "1") {
    startBalancePoll(12000);
    document.addEventListener("visibilitychange", function () {
      if (!document.hidden) refreshBalance();
    });
  }

  /* ---------- Game launch: só ao clicar em Jogar → shell tela cheia no site
     (igual Pragmatic no cassino antigo). NÃO usa requestFullscreen do browser. ---------- */
  function openGameShell(url, title) {
    var shell = document.getElementById("game-shell");
    var iframe = document.getElementById("game-iframe");
    var titleEl = document.getElementById("game-shell-title");
    if (!shell || !iframe || !url) {
      window.location.href = url;
      return;
    }
    if (titleEl && title) titleEl.textContent = title;
    document.body.classList.add("game-open", "modal-open");
    shell.hidden = false;
    shell.setAttribute("aria-hidden", "false");
    iframe.src = url;
    startBalancePoll(2000);
  }

  function closeGameShell() {
    var shell = document.getElementById("game-shell");
    var iframe = document.getElementById("game-iframe");
    if (iframe) iframe.src = "about:blank";
    if (shell) {
      shell.hidden = true;
      shell.setAttribute("aria-hidden", "true");
    }
    document.body.classList.remove("game-open", "modal-open");
    refreshBalance();
    startBalancePoll(12000);
  }

  // expõe para outros scripts
  window.openGameShell = openGameShell;
  window.closeGameShell = closeGameShell;

  const launchBtn = document.getElementById("btn-launch-game");
  if (launchBtn) {
    launchBtn.addEventListener("click", async () => {
      const err = document.getElementById("launch-error");
      var gameId = launchBtn.getAttribute("data-game-id") || "";
      var gameCode = launchBtn.getAttribute("data-game-code") || "";
      var gameTitle =
        (document.getElementById("play-title") && document.getElementById("play-title").textContent) ||
        "Jogo";
      if (!gameId && gameCode) gameId = gameCode;
      if (err) err.textContent = "Abrindo jogo...";
      launchBtn.disabled = true;
      try {
        const res = await fetch(apiBase + "api/game_launch.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-Token": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
          redirect: "follow",
          body: JSON.stringify(
            withCsrf({
              game_id: gameId,
              game_code: gameCode,
            })
          ),
        });
        if (res.status === 302 || (res.redirected && res.url && res.url.indexOf("game_launch") === -1)) {
          if (err) err.textContent = "Sessão expirada. Faça login novamente.";
          return;
        }
        const data = await res.json().catch(function () {
          return { ok: false, error: "Resposta inválida do servidor (HTTP " + res.status + ")" };
        });
        if (!data.ok) {
          // sem saldo → redireciona para carteira
          if (data.code === "no_balance" || res.status === 402) {
            var wUrl =
              data.redirect ||
              launchBtn.getAttribute("data-wallet-url") ||
              document.body.dataset.walletUrl ||
              apiBase + "index.php?page=wallet";
            if (err) err.textContent = data.error || "Sem saldo — abrindo carteira…";
            window.location.href = wUrl;
            return;
          }
          if (err) err.textContent = data.error || "Falha ao abrir jogo";
          return;
        }
        if (data.balance != null) {
          applyBalanceToUi(data.balance, data.currency, data.currency_symbol);
        }
        var launchUrl = data.launch_url || "";
        if (!launchUrl) {
          if (err) err.textContent = "Launcher não retornou URL do jogo";
          return;
        }
        if (err) err.textContent = "";
        
        if (typeof window.trackPixelEvent === 'function') window.trackPixelEvent('BetPlaced');

        // Sempre shell fullscreen no mesmo site (TADA/Spribe/Pragmatic/WG…)
        // Só redireciona se open_mode for redirect explícito
        var mode = String(data.open_mode || "iframe").toLowerCase();
        if (mode === "redirect") {
          window.location.href = launchUrl;
        } else {
          openGameShell(launchUrl, gameTitle);
        }
      } catch (e) {
        if (err) err.textContent = "Erro de conexão com o launcher";
      } finally {
        launchBtn.disabled = false;
      }
    });
  }
  var closeGame = document.getElementById("btn-close-game");
  if (closeGame) {
    closeGame.addEventListener("click", function () {
      closeGameShell();
    });
  }
  // ESC fecha o jogo
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && document.body.classList.contains("game-open")) {
      closeGameShell();
    }
  });

  /* ---------- Sports API + Bilhete ---------- */
  window.bilhete = window.bilhete || [];

  function calcOdds(j) {
    var hId = (j.teams && j.teams.home && j.teams.home.id) || 1;
    var aId = (j.teams && j.teams.away && j.teams.away.id) || 2;
    var hg = (j.goals && j.goals.home) || 0;
    var ag = (j.goals && j.goals.away) || 0;
    var el = (j.fixture && j.fixture.status && j.fixture.status.elapsed) || 0;
    var fH = 1 + (hId % 10) / 100;
    var fA = 1 + (aId % 10) / 100;
    var oH = (2.4 * fA) / fH;
    var oA = (2.4 * fH) / fA;
    var oX = 3.1;
    var tM = 1 + el / 90;
    var df = hg - ag;
    if (df > 0) {
      oH /= 1 + df * 0.4 * tM;
      oA *= 1 + df * 1.4 * tM;
      oX *= 1 + df * 0.4 * tM;
    } else if (df < 0) {
      var ad = Math.abs(df);
      oA /= 1 + ad * 0.4 * tM;
      oH *= 1 + ad * 1.4 * tM;
      oX *= 1 + ad * 0.4 * tM;
    } else if (el > 45) {
      oX /= tM;
      oA *= tM;
      oH *= tM;
    }
    return {
      home: Math.max(1.05, oH).toFixed(2),
      draw: Math.max(1.05, oX).toFixed(2),
      away: Math.max(1.05, oA).toFixed(2),
    };
  }

  function esc(s) {
    return String(s || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function betTypeLabel(type) {
    if (type === "home") return tr("bettype_home");
    if (type === "away") return tr("bettype_away");
    if (type === "draw") return tr("bettype_draw");
    return String(type || "");
  }

  function isMobileSportsUi() {
    return window.innerWidth < 1100;
  }

  function renderSportsList(container, data) {
    if (!container) return;
    if (!data || !data.response || !data.response.length) {
      container.innerHTML =
        '<div class="empty-state sports-empty-state">' +
        '<div class="sports-empty-icon">⚽</div>' +
        '<h3>' + tr("no_matches") + '</h3>' +
        '<p>' + esc(data && data.error ? data.error : tr("no_matches_desc")) + '</p>' +
        "</div>";
      return;
    }
    var h = "";
    var curLg = "";
    var rowOpen = false;
    data.response.forEach(function (item) {
      var f = item.fixture || {};
      var lg = item.league || {};
      var hm = (item.teams && item.teams.home) || {};
      var aw = (item.teams && item.teams.away) || {};
      var gl = item.goals || {};
      var st = f.status || {};
      var odds = calcOdds(item);
      var lives = ["1H", "2H", "HT", "ET", "P", "LIVE", "BT"].indexOf(st.short) !== -1;
      if (lg.name !== curLg) {
        if (rowOpen) {
          h += "</div>";
          rowOpen = false;
        }
        curLg = lg.name;
        h +=
          '<div class="sp-liga">' +
          '<div class="sp-liga__icon">' +
          (lg.logo ? '<img src="' + esc(lg.logo) + '" alt="" onerror="this.parentNode.innerHTML=\'🏆\'">' : "🏆") +
          "</div>" +
          '<div class="sp-liga__title"><span>' +
          esc(lg.country || "") +
          "</span><strong>" +
          esc(lg.name) +
          "</strong></div></div>" +
          '<div class="sp-matches-row">';
        rowOpen = true;
      }
      var sh = gl.home != null ? gl.home : lives ? 0 : "-";
      var sa = gl.away != null ? gl.away : lives ? 0 : "-";
      var homeInitial = (hm.name || "C").charAt(0).toUpperCase();
      var awayInitial = (aw.name || "F").charAt(0).toUpperCase();
      var matchTime = f.date ? f.date.slice(11, 16) : (f.hour || "16:00");
      h +=
        '<article class="sp-match" data-fid="' +
        esc(f.id) +
        '">' +
        '<div class="sp-match__info">' +
        '<div class="sp-match__meta">' +
        (lives
          ? '<span class="sp-badge-live"><span class="sp-badge-live__dot"></span>' + esc(st.elapsed || "1") + "'</span>"
          : '<span class="sp-badge-time">' + esc(matchTime) + "</span>") +
        "</div>" +
        '<div class="sp-match__teams-col">' +
        '<div class="sp-team-row">' +
        '<div class="sp-team-info">' +
        (hm.logo
          ? '<img src="' + esc(hm.logo) + '" alt="" class="sp-team__logo" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-flex\';"><span class="sp-team__avatar" style="display:none">' + homeInitial + "</span>"
          : '<span class="sp-team__avatar">' + homeInitial + "</span>") +
        '<span class="sp-team__name">' + esc(hm.name) + "</span>" +
        "</div>" +
        '<strong class="sp-team-score">' + esc(sh) + "</strong>" +
        "</div>" +
        '<div class="sp-team-row">' +
        '<div class="sp-team-info">' +
        (aw.logo
          ? '<img src="' + esc(aw.logo) + '" alt="" class="sp-team__logo" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-flex\';"><span class="sp-team__avatar" style="display:none">' + awayInitial + "</span>"
          : '<span class="sp-team__avatar">' + awayInitial + "</span>") +
        '<span class="sp-team__name">' + esc(aw.name) + "</span>" +
        "</div>" +
        '<strong class="sp-team-score">' + esc(sa) + "</strong>" +
        "</div>" +
        "</div>" +
        "</div>" +
        '<div class="sp-odds">' +
        oddBtn(item, "home", "1", tr("odd_home"), odds.home) +
        oddBtn(item, "draw", "X", tr("odd_draw"), odds.draw) +
        oddBtn(item, "away", "2", tr("odd_away"), odds.away) +
        "</div></article>";
    });
    if (rowOpen) h += "</div>";
    container.innerHTML = h;
    // re-marca odds já no bilhete
    (window.bilhete || []).forEach(function (sel) {
      var btn = container.querySelector(
        '.odd-btn[data-fid="' + sel.fixture_id + '"][data-bet-type="' + sel.bet_type + '"]'
      );
      if (btn) btn.classList.add("is-selected");
    });
  }

  function oddBtn(item, type, code, labelText, odd) {
    var f = item.fixture || {};
    var hm = (item.teams && item.teams.home) || {};
    var aw = (item.teams && item.teams.away) || {};
    var lg = item.league || {};
    return (
      '<button type="button" class="odd-btn" data-fid="' +
      esc(f.id) +
      '" data-bet-type="' +
      esc(type) +
      '" data-odd="' +
      esc(odd) +
      '" data-home="' +
      esc(hm.name || "") +
      '" data-away="' +
      esc(aw.name || "") +
      '" data-home-logo="' +
      esc(hm.logo || "") +
      '" data-away-logo="' +
      esc(aw.logo || "") +
      '" data-league="' +
      esc(lg.name || "") +
      '"><div class="odd-btn__info"><span class="odd-btn__code">' +
      esc(code) +
      '</span><span class="odd-btn__sub">' +
      esc(labelText) +
      '</span></div><span class="odd-btn__value">' +
      esc(odd) +
      "</span></button>"
    );
  }

  function selectionFromOddBtn(btn) {
    if (!btn) return null;
    var fid = parseInt(btn.getAttribute("data-fid"), 10);
    var odd = parseFloat(btn.getAttribute("data-odd"));
    var type = btn.getAttribute("data-bet-type") || "";
    if (!fid || !(odd >= 1.01) || !type) return null;
    return {
      fixture_id: fid,
      bet_type: type,
      odd: odd,
      home: btn.getAttribute("data-home") || "",
      away: btn.getAttribute("data-away") || "",
      home_logo: btn.getAttribute("data-home-logo") || "",
      away_logo: btn.getAttribute("data-away-logo") || "",
      league: btn.getAttribute("data-league") || "",
    };
  }

  function addToSlip(sel) {
    if (!sel || !sel.fixture_id) return;
    window.bilhete = window.bilhete.filter(function (b) {
      return String(b.fixture_id) !== String(sel.fixture_id);
    });
    window.bilhete.push(sel);
    renderSlip();
    // no mobile, ao montar o bilhete abre o sheet se já houver seleção
    if (isMobileSportsUi() && window.bilhete.length === 1) {
      // só mostra float na 1ª — usuário toca para abrir; evita abrir sozinho a cada odd
    }
  }

  function setSlipVisible(visible) {
    var slip = document.getElementById("betslip");
    var fl = document.getElementById("betslip-float");
    if (slip) {
      if (visible) {
        slip.removeAttribute("hidden");
        slip.hidden = false;
      } else if (!slip.classList.contains("is-sheet")) {
        slip.hidden = true;
        slip.setAttribute("hidden", "");
      }
      slip.classList.toggle("is-open", !!visible);
    }
    if (fl) {
      if (visible && isMobileSportsUi()) {
        fl.hidden = false;
        fl.removeAttribute("hidden");
        fl.classList.add("is-open");
        fl.setAttribute("aria-hidden", "false");
      } else {
        fl.hidden = true;
        fl.setAttribute("hidden", "");
        fl.classList.remove("is-open");
        fl.setAttribute("aria-hidden", "true");
      }
    }
  }

  function renderSlip() {
    var items = document.getElementById("betslip-items");
    var foot = document.getElementById("betslip-foot");
    var count = document.getElementById("betslip-count");
    var oddEl = document.getElementById("betslip-odd");
    var retEl = document.getElementById("betslip-return");
    var amountEl = document.getElementById("betslip-amount");
    var flc = document.getElementById("betslip-float-count");
    var flo = document.getElementById("betslip-float-odd");
    if (!items) return;

    var total = window.bilhete.reduce(function (a, b) {
      return a * (parseFloat(b.odd) || 1);
    }, 1);
    total = Math.max(1, total);
    if (count) count.textContent = String(window.bilhete.length);
    if (oddEl) oddEl.textContent = total.toFixed(2);
    var amount = amountEl ? parseFloat(String(amountEl.value).replace(",", ".")) || 0 : 0;
    if (retEl) retEl.textContent = formatMoney(amount * total);

    if (!window.bilhete.length) {
      items.innerHTML = '<div class="betslip__empty">' + tr("ticket_empty") + '</div>';
      if (foot) foot.hidden = true;
      setSlipVisible(false);
      closeBetSheet();
      return;
    }
    items.innerHTML = window.bilhete
      .map(function (b, i) {
        return (
          '<div class="betslip__item"><div><b>' +
          esc(b.home) +
          " " + tr("vs_short") + " " +
          esc(b.away) +
          "</b><small>" +
          esc(betTypeLabel(b.bet_type)) +
          " @ " +
          esc(b.odd) +
          (b.league ? " · " + esc(b.league) : "") +
          '</small></div><button type="button" data-rm="' +
          i +
          '" aria-label="' + tr("remove") + '">×</button></div>'
        );
      })
      .join("");
    if (foot) foot.hidden = false;
    setSlipVisible(true);
    if (flc) flc.textContent = String(window.bilhete.length);
    if (flo) flo.textContent = total.toFixed(2);
  }

  function formatMoneyBr(n) {
    return formatMoney(n);
  }

  function showTicketConfirmed(data, selections, amount) {
    var code = data.ticket_code || ("SP-" + String(data.bet_id || data.ticket_id || "").padStart(6, "0"));
    var when = data.created_at || "";
    if (!when) {
      var d = new Date();
      when =
        String(d.getDate()).padStart(2, "0") +
        "/" +
        String(d.getMonth() + 1).padStart(2, "0") +
        "/" +
        d.getFullYear() +
        " " +
        String(d.getHours()).padStart(2, "0") +
        ":" +
        String(d.getMinutes()).padStart(2, "0");
    }
    var items = data.items && data.items.length ? data.items : selections || [];
    var oddTotal = Number(data.odd_total || 0);
    var pot = Number(data.potential_win || amount * oddTotal);
    var stake = Number(data.amount != null ? data.amount : amount);
    var typeLbl = items.length > 1 ? tr("bet_type_multiple") + " (" + items.length + ")" : tr("bet_type_single");

    var itemsHtml = items
      .map(function (it) {
        return (
          '<div class="ticket-receipt__pick">' +
          '<div class="ticket-receipt__match">' +
          esc(it.home || it.home_team || "") +
          ' <span>' + tr("vs_short") + '</span> ' +
          esc(it.away || it.away_team || "") +
          "</div>" +
          '<div class="ticket-receipt__meta">' +
          "<span>" +
          esc(betTypeLabel(it.bet_type)) +
          "</span>" +
          "<b>" +
          Number(it.odd || 0).toFixed(2) +
          "</b></div>" +
          (it.league || it.league_name
            ? '<div class="ticket-receipt__league">' + esc(it.league || it.league_name) + "</div>"
            : "") +
          "</div>"
        );
      })
      .join("");

    var detail =
      '<div class="ticket-receipt">' +
      '<div class="ticket-receipt__banner">✓ ' + tr("ticket_confirmed_banner") + '</div>' +
      '<div class="ticket-receipt__code-wrap">' +
      '<span class="ticket-receipt__code-label">' + tr("ticket_code_label") + '</span>' +
      '<strong class="ticket-receipt__code">' +
      esc(code) +
      "</strong>" +
      '<span class="ticket-receipt__time">' +
      esc(when) +
      "</span></div>" +
      '<div class="ticket-receipt__type">' +
      esc(typeLbl) +
      ' · <span style="color:#fbbf24">' + tr("status_pending") + '</span></div>' +
      '<div class="ticket-receipt__picks">' +
      itemsHtml +
      "</div>" +
      '<div class="app-confirm__rows">' +
      "<div><span>" + tr("amount_staked") + "</span><b>" +
      formatMoney(stake) +
      "</b></div>" +
      "<div><span>" + tr("total_odd") + "</span><b>" +
      oddTotal.toFixed(2) +
      "</b></div>" +
      '<div class="is-hl"><span>' + tr("possible_return") + '</span><b>' +
      formatMoney(pot) +
      "</b></div>" +
      (data.new_balance != null
        ? "<div><span>" + tr("new_balance") + "</span><b>" + formatMoney(data.new_balance) + "</b></div>"
        : "") +
      "</div></div>";

    return showConfirm({
      title: tr("bet_success_title"),
      message: data.msg || tr("bet_success_msg"),
      detailHtml: detail,
      okText: tr("view_my_bets"),
      cancelText: tr("close"),
      icon: "✓",
      variant: "accent",
    });
  }

  async function placeSportsBet() {
    var msg = document.getElementById("betslip-msg");
    var amountEl = document.getElementById("betslip-amount");
    var amount = parseFloat(String((amountEl && amountEl.value) || "0").replace(",", "."));
    var submitBtns = document.querySelectorAll("#betslip-submit, [data-betslip-submit]");
    var selectionsSnapshot = (window.bilhete || []).slice();
    if (!selectionsSnapshot.length || !(amount >= 1)) {
      if (msg) {
        msg.style.color = "#ff6b6b";
        msg.textContent = tr("bet_min_msg") + formatMoney(1);
      }
      showToast(tr("bet_min_msg") + formatMoney(1), "warn");
      return;
    }
    if (msg) {
      msg.style.color = "";
      msg.textContent = tr("confirming_bet");
    }
    submitBtns.forEach(function (b) {
      b.disabled = true;
    });
    var fd = new FormData();
    fd.append("action", "place_bet");
    fd.append("amount", String(amount));
    fd.append("bets", JSON.stringify(selectionsSnapshot));
    withCsrf(fd);
    try {
      var res = await fetch(apiBase + "api/sports_bet.php", {
        method: "POST",
        body: fd,
        credentials: "same-origin",
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      });
      var data = await res.json().catch(function () {
        return {};
      });
      if (data.status === "success" || data.ok) {
        if (msg) {
          msg.style.color = "#3dc476";
          msg.textContent = data.msg || tr("bet_success_confirm");
        }
        if (data.new_balance != null) {
          applyBalanceToUi(data.new_balance);
          var bal = document.getElementById("sports-balance");
          if (bal) bal.textContent = formatMoneyBr(data.new_balance);
        }
        window.bilhete = [];
        renderSlip();
        closeBetSheet();
        // limpa seleção visual nas odds
        document.querySelectorAll(".odd-btn.is-selected").forEach(function (b) {
          b.classList.remove("is-selected");
        });
        showToast(data.msg || tr("bet_success_confirm"), "success", { duration: 4200 });
        var goMyBets = await showTicketConfirmed(data, selectionsSnapshot, amount);
        if (goMyBets) {
          var myBtn = document.getElementById("btn-my-bets");
          if (myBtn) {
            document.querySelectorAll("[data-sports-tabs-btn]").forEach(function (b) {
              b.classList.remove("is-active");
            });
            myBtn.classList.add("is-active");
            loadMyBets("all");
          }
        }
      } else {
        if (msg) {
          msg.style.color = "#ff6b6b";
          msg.textContent = data.msg || tr("bet_error_http") + " " + res.status + ")";
        }
        showToast(data.msg || tr("bet_error_confirm"), "error");
      }
    } catch (e) {
      if (msg) {
        msg.style.color = "#ff6b6b";
        msg.textContent = tr("bet_error_connect");
      }
      showToast(tr("bet_error_connect"), "error");
    } finally {
      submitBtns.forEach(function (b) {
        b.disabled = false;
      });
    }
  }

  // Delegação: odds + bilhete desktop/sheet mobile (touch-friendly)
  document.addEventListener("input", function (e) {
    if (e.target && e.target.id === "betslip-amount") renderSlip();
  });
  document.addEventListener("click", function (e) {
    var t = e.target;
    if (!t || !t.closest) return;

    // Botão de preset de valor no bilhete (+10, +25, +50, +100)
    var presetBtn = t.closest("[data-betslip-preset]");
    if (presetBtn) {
      e.preventDefault();
      var addVal = parseFloat(presetBtn.getAttribute("data-betslip-preset") || "0");
      var input = document.getElementById("betslip-amount");
      if (input && !isNaN(addVal)) {
        var current = parseFloat(String(input.value).replace(",", ".")) || 0;
        input.value = Math.max(1, Math.round(current + addVal));
        renderSlip();
      }
      return;
    }

    // Clique em odd (lista de esportes)
    var oddBtnEl = t.closest(".odd-btn[data-fid][data-bet-type]");
    if (oddBtnEl && !oddBtnEl.disabled) {
      e.preventDefault();
      var sel = selectionFromOddBtn(oddBtnEl);
      if (sel) {
        var card = oddBtnEl.closest(".sp-match, .match-card");
        if (card) {
          card.querySelectorAll(".odd-btn").forEach(function (b) {
            b.classList.remove("is-selected");
          });
        }
        oddBtnEl.classList.add("is-selected");
        addToSlip(sel);
      }
      return;
    }

    if (t.closest("#betslip-clear")) {
      e.preventDefault();
      window.bilhete = [];
      document.querySelectorAll(".odd-btn.is-selected").forEach(function (b) {
        b.classList.remove("is-selected");
      });
      renderSlip();
      return;
    }
    if (t.closest("#betslip-submit, [data-betslip-submit]")) {
      e.preventDefault();
      placeSportsBet();
      return;
    }
    var rm = t.closest("[data-rm]");
    if (rm && rm.closest("#betslip-items, #betslip-sheet, #betslip")) {
      e.preventDefault();
      var idx = parseInt(rm.getAttribute("data-rm"), 10);
      if (!isNaN(idx)) {
        var removed = window.bilhete[idx];
        window.bilhete.splice(idx, 1);
        if (removed) {
          document
            .querySelectorAll('.odd-btn[data-fid="' + removed.fixture_id + '"]')
            .forEach(function (b) {
              b.classList.remove("is-selected");
            });
        }
        renderSlip();
      }
    }
  });

  function loadSports(tipo, containerId) {
    var box = document.getElementById(containerId);
    if (!box) return;
    box.innerHTML = '<div class="empty-state" style="padding:20px">' + tr("loading") + '</div>';
    fetch(apiBase + "api/sports_data.php?tipo=" + encodeURIComponent(tipo) + "&_=" + Date.now(), {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        renderSportsList(box, data);
      })
      .catch(function () {
        box.innerHTML = '<div class="empty-state" style="padding:20px">' + tr("error_loading_sports") + '</div>';
      });
  }

  // Esportes: só na página sports + logado
  var sportsPage = document.querySelector("[data-sports-page]");
  var canLoadSports = sportsPage && sportsPage.getAttribute("data-sports-page") === "1" && document.getElementById("sports-list-page");

  document.querySelectorAll("[data-sports-cat]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var cat = btn.getAttribute("data-sports-cat");
      document.querySelectorAll("[data-sports-cat]").forEach(function (b) {
        b.classList.toggle("is-active", b === btn);
      });
      var box = document.getElementById("sports-list-page");
      if (!box) return;
      if (cat !== "all" && cat !== "futebol") {
        box.innerHTML =
          '<div class="empty-state sports-empty-state">' +
          '<div class="sports-empty-icon">🏆</div>' +
          '<h3>' + tr("category_coming_soon") + '</h3>' +
          '<p>' + tr("category_coming_soon_text") + '</p>' +
          '</div>';
      } else {
        var activeTab = document.querySelector("[data-sports-tabs-btn].is-active");
        var tipo = activeTab ? activeTab.getAttribute("data-tipo") : "live";
        loadSports(tipo || "live", "sports-list-page");
      }
    });
  });

  document.querySelectorAll("[data-sports-tabs-btn]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var tipo = btn.getAttribute("data-tipo");
      if (!tipo || !canLoadSports) return;
      document.querySelectorAll("[data-sports-tabs-btn]").forEach(function (b) {
        b.classList.toggle("is-active", b === btn);
      });
      var myBox = document.getElementById("my-bets-box");
      var listEl = document.getElementById("sports-list-page");
      var myBtn = document.getElementById("btn-my-bets");
      if (myBox) myBox.style.display = "none";
      if (listEl) listEl.style.display = "";
      if (myBtn) myBtn.classList.remove("is-active");
      loadSports(tipo, "sports-list-page");
    });
  });

  if (canLoadSports) {
    loadSports("live", "sports-list-page");
  }

  var myBetsBtn = document.getElementById("btn-my-bets");
  var myBetsFilter = "all";

  function myBetsStatusLabel(st) {
    if (st === "won") return tr("status_won");
    if (st === "lost") return tr("status_lost");
    if (st === "cashed_out") return tr("status_cashed_out");
    return tr("status_pending");
  }
  function myBetsStatusColor(st) {
    if (st === "won") return "#00C774";
    if (st === "lost") return "#ef4444";
    if (st === "cashed_out") return "#60a5fa";
    return "#fbbf24";
  }
  function myBetsPickLabel(it) {
    if (!it) return "";
    if (it.bet_type === "home") return it.home_team || tr("odd_home");
    if (it.bet_type === "away") return it.away_team || tr("odd_away");
    if (it.bet_type === "draw") return tr("bettype_draw");
    return it.bet_type || "";
  }

  async function loadMyBets(filter) {
    var box = document.getElementById("my-bets-box");
    var listEl = document.getElementById("sports-list-page");
    if (!box) return;
    myBetsFilter = filter || "all";
    if (listEl) listEl.style.display = "none";
    box.style.display = "block";
    box.hidden = false;
    box.innerHTML =
      '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">' +
      [
        ["all", tr("mybets_all")],
        ["pending", tr("mybets_pending")],
        ["won", tr("mybets_won")],
        ["lost", tr("mybets_lost")],
        ["cashed_out", tr("mybets_cashed")],
      ]
        .map(function (f) {
          var on = f[0] === myBetsFilter;
          return (
            '<button type="button" class="sports-filter' +
            (on ? " is-active" : "") +
            '" data-mybets-filter="' +
            f[0] +
            '">' +
            f[1] +
            "</button>"
          );
        })
        .join("") +
      "</div>" +
      '<div id="my-bets-list" style="min-height:80px">' + tr("loading_bets") + '</div>';

    box.querySelectorAll("[data-mybets-filter]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        loadMyBets(btn.getAttribute("data-mybets-filter") || "all");
      });
    });

    var list = document.getElementById("my-bets-list");
    var fd = new FormData();
    fd.append("action", "my_bets");
    fd.append("filter", myBetsFilter);
    withCsrf(fd);
    try {
      var res = await fetch(apiBase + "api/sports_bet.php", {
        method: "POST",
        body: fd,
        credentials: "same-origin",
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      });
      var data = await res.json().catch(function () {
        return { status: "error", msg: tr("error_loading_bets") + " (HTTP " + res.status + ")" };
      });
      if (data.status === "error" || (res.status >= 400 && !data.data && !data.bets)) {
        if (list) {
          list.innerHTML =
            '<p style="color:#ef4444;text-align:center;padding:24px">' +
            esc(data.msg || tr("error_loading_bets") + " (HTTP " + res.status + ")") +
            "</p>";
        }
        return;
      }
      if (data.new_balance != null) {
        applyBalanceToUi(data.new_balance);
        var balEl = document.getElementById("sports-balance");
        if (balEl) balEl.textContent = formatMoneyBr(data.new_balance);
      }
      if (data.resolve && data.resolve.resolved > 0) {
        showToast(
          tr("auto_resolved") + ": " +
            data.resolve.resolved +
            (data.resolve.won ? " · " + data.resolve.won + " " + tr("results_won") : "") +
            (data.resolve.lost ? " · " + data.resolve.lost + " " + tr("results_lost") : ""),
          data.resolve.won > 0 ? "success" : "info"
        );
      }
      var bets = data.data || data.bets || [];
      if (!Array.isArray(bets)) bets = [];
      if (!bets.length) {
        if (list)
          list.innerHTML =
            '<p class="muted" style="text-align:center;padding:32px">' + tr("no_bets") + '</p>';
        return;
      }
      if (!list) return;
      list.innerHTML = bets
        .map(function (b) {
          var items = b.items || [];
var typeLbl = items.length > 1 ? tr("bet_type_multiple") + " (" + items.length + ")" : tr("bet_type_single");
          var st = b.status || "pending";
          var col = myBetsStatusColor(st);
          var betKey = String(b.local_id != null && b.source === "local" ? "L" + b.local_id : b.id);
          var itemsHtml = items
            .map(function (it) {
              var live =
                it.is_live
                  ? '<span style="color:#ef4444;font-weight:800;font-size:11px;margin-left:6px">● ' +
                    esc(it.score || tr("live_badge")) +
                    "</span>"
                  : "";
              return (
                '<div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06)">' +
                '<div style="font-size:11px;color:var(--text-3)">' +
                esc(it.league_name || "") +
                live +
                "</div>" +
                '<div style="font-size:13px;font-weight:600;margin:4px 0">' +
                esc(it.home_team || "") +
                ' <span style="opacity:.5">' + tr("vs_short") + '</span> ' +
                esc(it.away_team || "") +
                "</div>" +
                '<div style="display:flex;justify-content:space-between;font-size:12px">' +
                "<span>" + tr("pick") + ": <b>" +
                esc(myBetsPickLabel(it)) +
                "</b></span>" +
                "<b>" +
                Number(it.odd || 0).toFixed(2) +
                "</b>" +
                "</div></div>"
              );
            })
            .join("");
          var cashBtn =
            st === "pending"
              ? '<button type="button" class="btn btn--accent btn--sm" style="width:100%;margin-top:10px" data-cashout="' +
                esc(betKey) +
                '" data-cashout-source="' +
                esc(b.source || "") +
                '">' + tr("cashout_suggestion") + '</button>'
              : "";
          var rawId = String(b.local_id != null && b.source === "local" ? b.local_id : b.id).replace(/^L/, "");
          var ticketCode =
            b.ticket_code ||
            "SP-" + String(rawId).padStart(6, "0");
          return (
            '<div class="account-card" style="margin-bottom:12px;padding:14px">' +
            '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px">' +
            "<div><div style=\"font-size:13px;font-weight:800;letter-spacing:.03em\">" +
            esc(ticketCode) +
            '</div><span style="font-size:11px;opacity:.7">' +
            esc(typeLbl) +
            "</span><br><span style=\"font-size:12px;color:var(--text-3)\">" +
            esc(b.created_at || "") +
            "</span></div>" +
            '<span style="font-size:12px;font-weight:800;color:' +
            col +
            '">' +
            esc(myBetsStatusLabel(st)) +
            "</span></div>" +
            itemsHtml +
            '<div style="margin-top:10px;font-size:13px;display:flex;flex-direction:column;gap:4px">' +
            "<div style=\"display:flex;justify-content:space-between\"><span>" + tr("amount_staked") + "</span><b>" +
            formatMoney(b.amount || 0) +
            "</b></div>" +
            "<div style=\"display:flex;justify-content:space-between\"><span>" + tr("total_odd") + "</span><b>" +
            Number(b.odd || b.odd_total || 0).toFixed(2) +
            "</b></div>" +
            "<div style=\"display:flex;justify-content:space-between\"><span>" + tr("return_label") + "</span><b style=\"color:" +
            col +
            '">' +
            formatMoney(b.potential_win || 0) +
            "</b></div>" +
            "</div>" +
            cashBtn +
            "</div>"
          );
        })
        .join("");

      list.querySelectorAll("[data-cashout]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          doCashout(btn.getAttribute("data-cashout"), btn, btn.getAttribute("data-cashout-source") || "");
        });
      });
    } catch (e) {
      if (list) list.innerHTML = '<p style="color:#ef4444;text-align:center;padding:24px">' + tr("error_loading_bets") + '</p>';
    }
  }

  async function doCashout(betId, btn, source) {
    if (!betId || !btn) return;
    var original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = tr("calculating");
    try {
      var fd = new FormData();
      fd.append("action", "cashout");
      fd.append("bet_id", String(betId));
      fd.append("preview", "1");
      if (source) fd.append("source", source);
      withCsrf(fd);
      var res = await fetch(apiBase + "api/sports_bet.php", {
        method: "POST",
        body: fd,
        credentials: "same-origin",
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      });
      var data = await res.json().catch(function () {
        return { status: "error", msg: "Resposta inválida" };
      });
      btn.disabled = false;
      btn.innerHTML = original;
      if (data.status === "error") {
        showToast(data.msg || tr("cashout_failed"), data.auto_resolved ? "info" : "error");
        if (data.auto_resolved) loadMyBets(myBetsFilter);
        return;
      }
      if (data.status !== "preview") {
        showToast(tr("cashout_comm_error"), "error");
        return;
      }
      var detail =
        '<div class="app-confirm__rows">' +
        '<div><span>' + tr("odd_original") + '</span><b>' +
        esc(String(data.odd_original)) +
        "</b></div>" +
        "<div><span>" + tr("odd_current") + "</span><b>" +
        esc(String(data.odd_atual)) +
        "</b></div>" +
        '<div class="is-hl"><span>' + tr("you_receive") + "</span><b>" +
        formatMoney(data.cashout_val) +
        "</b></div></div>";
      var ok = await showConfirm({
        title: tr("cashout_title"),
        message: tr("cashout_msg"),
        detailHtml: detail,
        okText: tr("cashout_ok"),
        cancelText: tr("not_now"),
        icon: "$",
        variant: "accent",
      });
      if (!ok) return;
      btn.disabled = true;
      btn.innerHTML = tr("cashing_out");
      var fd2 = new FormData();
      fd2.append("action", "cashout");
      fd2.append("bet_id", String(betId));
      if (source) fd2.append("source", source);
      withCsrf(fd2);
      var res2 = await fetch(apiBase + "api/sports_bet.php", {
        method: "POST",
        body: fd2,
        credentials: "same-origin",
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      });
      var rx = await res2.json().catch(function () {
        return { status: "error", msg: tr("cashout_failed") };
      });
      btn.disabled = false;
      btn.innerHTML = original;
      if (rx.status === "success") {
        if (rx.new_balance != null) applyBalanceToUi(rx.new_balance);
        var bal = document.getElementById("sports-balance");
        if (bal && rx.new_balance != null) bal.textContent = formatMoneyBr(rx.new_balance);
        showToast(rx.msg || tr("cashout_done"), "success");
        loadMyBets(myBetsFilter);
      } else {
        showToast(rx.msg || tr("cashout_failed"), "error");
      }
    } catch (e) {
      btn.disabled = false;
      btn.innerHTML = original;
      showToast(tr("cashout_comm_error"), "error");
    }
  }

  /** Computa bilhetes finalizados automaticamente (igual cassino antigo) */
  async function runSportsAutoResolve(silent) {
    try {
      var fd = new FormData();
      fd.append("action", "auto_resolve");
      withCsrf(fd);
      var res = await fetch(apiBase + "api/sports_bet.php", {
        method: "POST",
        body: fd,
        credentials: "same-origin",
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      });
      var data = await res.json().catch(function () {
        return null;
      });
      if (!data || data.status !== "success") return;
      if (data.new_balance != null) {
        applyBalanceToUi(data.new_balance);
        var bal = document.getElementById("sports-balance");
        if (bal) bal.textContent = formatMoneyBr(data.new_balance);
      }
      var resolved = data.stats && data.stats.resolved ? Number(data.stats.resolved) : 0;
      if (resolved > 0 && !silent) {
        var won = data.stats.won || 0;
        var lost = data.stats.lost || 0;
        var cred = data.stats.credited
          ? " · " + formatMoney(data.stats.credited) + " " + tr("results_credited")
          : "";
        showToast(
          tr("sports_updated") + ": " +
            resolved +
            " (" +
            won +
            " " + tr("results_won") + ", " +
            lost +
            " " + tr("results_lost") + ")" +
            cred,
          won > 0 ? "success" : "info",
          { duration: 5000 }
        );
      }
    } catch (e) {}
  }

  if (myBetsBtn) {
    myBetsBtn.addEventListener("click", function (e) {
      e.preventDefault();
      document.querySelectorAll("[data-sports-tabs-btn]").forEach(function (b) {
        b.classList.remove("is-active");
      });
      myBetsBtn.classList.add("is-active");
      loadMyBets("all");
    });
  }

  // Auto-computa apostas finalizadas ao entrar em Esportes e a cada 45s
  if (canLoadSports && document.body.dataset.isAuth === "1") {
    runSportsAutoResolve(false);
    setInterval(function () {
      if (document.hidden) return;
      runSportsAutoResolve(true);
    }, 45000);
  }

  // mobile: move o bilhete real para o sheet (não clona — mantém IDs e valor)
  // float/sheet ficam FORA do aside (aside é display:none no mobile)
  var flBtn = document.getElementById("betslip-float");
  var sheet = document.getElementById("betslip-sheet");
  var sheetBg = document.getElementById("betslip-sheet-bg");
  var slipHome = null;

  function closeBetSheet() {
    document.body.classList.remove("betslip-sheet-open");
    if (sheet) {
      sheet.hidden = true;
      sheet.setAttribute("hidden", "");
      sheet.classList.remove("is-open");
    }
    if (sheetBg) {
      sheetBg.hidden = true;
      sheetBg.setAttribute("hidden", "");
      sheetBg.classList.remove("is-open");
    }
    var slip = document.getElementById("betslip");
    if (slip && slipHome && slip.parentNode !== slipHome) {
      slip.classList.remove("is-sheet");
      slip.style.display = "";
      slipHome.appendChild(slip);
      // no mobile o aside esconde o painel; float continua se houver itens
      if (window.bilhete && window.bilhete.length) {
        slip.classList.add("is-open");
        slip.removeAttribute("hidden");
        slip.hidden = false;
      }
    }
  }

  function openBetSheet() {
    if (!sheet || !sheetBg) return;
    var body = document.getElementById("betslip-sheet-body");
    var slip = document.getElementById("betslip");
    if (!body || !slip) return;
    if (!slipHome) slipHome = slip.parentNode;
    sheet.hidden = false;
    sheet.removeAttribute("hidden");
    sheet.classList.add("is-open");
    sheetBg.hidden = false;
    sheetBg.removeAttribute("hidden");
    sheetBg.classList.add("is-open");
    slip.hidden = false;
    slip.removeAttribute("hidden");
    slip.classList.add("is-open", "is-sheet");
    slip.style.display = "flex";
    body.appendChild(slip);
    document.body.classList.add("betslip-sheet-open");
    renderSlip();
  }

  if (flBtn) {
    flBtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      openBetSheet();
    });
  }
  if (sheetBg) {
    sheetBg.addEventListener("click", function () {
      closeBetSheet();
    });
  }
  // reabre float corretamente ao redimensionar (rotacionar celular)
  window.addEventListener(
    "resize",
    function () {
      if (!window.bilhete || !window.bilhete.length) return;
      if (!isMobileSportsUi()) {
        closeBetSheet();
        setSlipVisible(true);
      } else {
        var slip = document.getElementById("betslip");
        if (slip && !slip.classList.contains("is-sheet")) {
          setSlipVisible(true);
        }
      }
    },
    { passive: true }
  );

  renderSlip();

  /* ---------- Free money: roleta (visual cassino antigo) + missões ---------- */
  (function initFreeMoney() {
    var page = document.getElementById("fm-page");
    if (!page) return;
    var logged = page.getAttribute("data-logged") === "1";
    var wheel = document.getElementById("fm-wheel");
    var stage = document.getElementById("fm-wheel-stage");
    var spinBtn = document.getElementById("fm-spin-btn");
    var spinLabel = document.getElementById("fm-spin-label");
    var centerTrigger = document.getElementById("fm-center-trigger");
    var resultWrap = document.getElementById("fm-result-wrap");
    var spinMsg = document.getElementById("fm-spin-msg");
    var prizesBox = document.getElementById("fm-prizes");
    var missionsBox = document.getElementById("fm-missions");
    var spinning = false;
    var segments = [];
    var canSpin = false;
    var alreadySpun = false;
    var duration = 6;
    var secondsUntilReset = 0;
    var countdownTimer = null;

    function statusLabel(st) {
      var map = {
        nao_iniciada: "Ativar",
        em_progresso: "Em progresso",
        disponivel: "Resgatar",
        resgatada: "Concluída",
        jogando: "Jogando",
      };
      return map[st] || st || "";
    }

    function setSpinLabel(label) {
      if (spinLabel) spinLabel.textContent = label;
      if (spinBtn) {
        spinBtn.classList.toggle("is-busy", label === "GIRANDO...");
        spinBtn.classList.toggle("is-disabled", label === "ENTENDIDO" || label === "OK" || label === "LOGIN");
      }
    }

    function tone(kind, velocity) {
      try {
        var AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        var ctx = new AudioCtx();
        var now = ctx.currentTime;
        if (kind === "tick") {
          var clickOsc = ctx.createOscillator();
          var clickGain = ctx.createGain();
          clickOsc.type = "square";
          clickOsc.frequency.setValueAtTime(800 + (velocity || 0) / 2, now);
          clickGain.gain.setValueAtTime(0.0001, now);
          clickGain.gain.exponentialRampToValueAtTime(
            Math.max(0.025, Math.min(0.1, (velocity || 900) / 2000)),
            now + 0.006
          );
          clickGain.gain.exponentialRampToValueAtTime(0.0001, now + 0.045);
          clickOsc.connect(clickGain);
          clickGain.connect(ctx.destination);
          clickOsc.start(now);
          clickOsc.stop(now + 0.05);
          setTimeout(function () {
            if (ctx.close) ctx.close();
          }, 130);
          return;
        }
        var notes = kind === "win" ? [440, 554, 659, 880] : [523, 659, 784, 1047, 1319];
        notes.forEach(function (freq, index) {
          var osc = ctx.createOscillator();
          var gain = ctx.createGain();
          osc.type = index % 2 ? "triangle" : "sine";
          osc.frequency.setValueAtTime(freq, now + index * 0.08);
          gain.gain.setValueAtTime(0.0001, now + index * 0.08);
          gain.gain.exponentialRampToValueAtTime(0.08, now + index * 0.08 + 0.02);
          gain.gain.exponentialRampToValueAtTime(0.0001, now + index * 0.08 + 0.16);
          osc.connect(gain);
          gain.connect(ctx.destination);
          osc.start(now + index * 0.08);
          osc.stop(now + index * 0.08 + 0.18);
        });
        setTimeout(function () {
          if (ctx.close) ctx.close();
        }, 900);
      } catch (e) {}
    }

    function winPopupHtml(prize) {
      if (!prize) return "";
      return (
        '<div class="daily-prize-win-pop">' +
        "<h3>🎉 PARABÉNS! 🎉</h3>" +
        "<strong>Você ganhou " +
        esc(prize.label || prize.prize_label || "um prêmio") +
        "</strong>" +
        "<small>Seus giros grátis foram creditados" +
        (prize.game_label || prize.free_spins_game
          ? " no <b>" + esc(prize.game_label || prize.free_spins_game) + "</b>"
          : "") +
        "!</small>" +
        '<button type="button" class="daily-prize-win-ok" data-fm-ok>Entendido</button>' +
        "</div>"
      );
    }

    function limitPopupHtml(seconds) {
      return (
        '<div class="daily-prize-limit-pop">' +
        "<h3>LIMITE DIÁRIO ATINGIDO</h3>" +
        "<strong>Você já girou hoje</strong>" +
        "<small>Uma nova chance será liberada amanhã.</small>" +
        '<div class="daily-prize-countdown" data-seconds="' +
        Math.max(1, Number(seconds) || 1) +
        '">00:00:00</div>' +
        '<button type="button" class="daily-prize-limit-ok" data-fm-ok>ENTENDIDO</button>' +
        "</div>"
      );
    }

    function startCountdown(seconds) {
      if (!resultWrap) return;
      if (countdownTimer) clearInterval(countdownTimer);
      var remaining = Math.max(1, Number(seconds) || 1);
      var el = resultWrap.querySelector(".daily-prize-countdown");
      function render() {
        if (!el) return;
        var h = Math.floor(remaining / 3600);
        var m = Math.floor((remaining % 3600) / 60);
        var s = remaining % 60;
        el.textContent = [h, m, s]
          .map(function (v) {
            return String(v).padStart(2, "0");
          })
          .join(":");
        if (remaining > 0) remaining--;
      }
      render();
      countdownTimer = setInterval(render, 1000);
    }

    function paintPrizes(segs) {
      if (!prizesBox) return;
      prizesBox.innerHTML = (segs || [])
        .map(function (s) {
          return (
            '<div class="fm-prize"><img src="' +
            esc(s.image || "") +
            '" alt="" onerror="this.style.display=\'none\'"><div><b>' +
            esc(s.short_label || s.label || "") +
            "</b><br><span style='color:var(--text-3)'>" +
            esc(s.game_label || "") +
            "</span></div></div>"
          );
        })
        .join("");
    }

    function paintMissions(list) {
      if (!missionsBox) return;
      if (!list || !list.length) {
        missionsBox.innerHTML = '<div class="empty-state" style="padding:16px">Nenhuma missão ativa no momento.</div>';
        return;
      }
      missionsBox.innerHTML = list
        .map(function (m) {
          var st = m.status || "nao_iniciada";
          var pct = Math.min(100, Number(m.progress_percent) || 0);
          var btn = "";
          if (st === "nao_iniciada") {
            btn =
              '<button type="button" class="btn btn--sm btn--register" data-mission-join="' +
              esc(m.missao_chave) +
              '">Participar</button>';
          } else if (st === "disponivel") {
            btn =
              '<button type="button" class="btn btn--sm btn--accent" data-mission-claim="' +
              esc(m.missao_chave) +
              '">Resgatar</button>';
          } else if (st === "em_progresso" || st === "jogando") {
            btn = '<span class="fm-mission__status">' + esc(statusLabel(st)) + "</span>";
          } else {
            btn = '<span class="fm-mission__status is-ok">' + esc(statusLabel(st)) + "</span>";
          }
          return (
            '<article class="fm-mission"><img src="' +
            esc(m.cover || "") +
            '" alt="" onerror="this.src=\'' +
            esc(apiBase + "assets/img/game-placeholder.svg") +
            '\'"><div><p class="fm-mission__title">' +
            esc(m.titulo || m.game_name || "Missão") +
            '</p><p class="fm-mission__meta">' +
            esc(m.requirement_label || m.descricao || "") +
            " · " +
            esc(m.progress_label || "") +
            '</p><div class="fm-mission__bar"><i style="width:' +
            pct +
            '%"></i></div></div><div>' +
            btn +
            "</div></article>"
          );
        })
        .join("");

      missionsBox.querySelectorAll("[data-mission-join]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          missionAction("join_mission", btn.getAttribute("data-mission-join"), btn);
        });
      });
      missionsBox.querySelectorAll("[data-mission-claim]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          missionAction("claim_mission", btn.getAttribute("data-mission-claim"), btn);
        });
      });
    }

    function paintFreeSpins(fs) {
      var box = document.getElementById("fm-freespins");
      if (!box || !fs) return;
      var n = Number(fs.count) || 0;
      if (n <= 0) {
        box.hidden = true;
        return;
      }
      box.hidden = false;
      var c = document.getElementById("fm-fs-count");
      var d = document.getElementById("fm-fs-detail");
      if (c) c.textContent = n + " rodadas grátis";
      if (d) d.textContent = "Aposta " + Number(fs.bet || 0).toFixed(2) + " · jogo " + (fs.game || "all");
    }

    async function missionAction(action, key, btn) {
      if (!logged) return;
      if (btn) btn.disabled = true;
      try {
        var fd = new FormData();
        fd.append("action", action);
        fd.append("mission", key);
        withCsrf(fd);
        var res = await fetch(apiBase + "api/freemoney.php", { method: "POST", body: fd, credentials: "same-origin" });
        var data = await res.json();
        if (spinMsg) spinMsg.textContent = data.message || data.error || (data.ok ? "OK" : "Erro");
        loadStatus();
      } catch (e) {
        if (spinMsg) spinMsg.textContent = "Erro de conexão";
      } finally {
        if (btn) btn.disabled = false;
      }
    }

    function applyStatus(data) {
      if (!data || !data.ok) return;
      segments = data.segments || [];
      canSpin = !!data.can_spin;
      alreadySpun = !!data.already_spun;
      duration = Number((data.config && data.config.duracao_giro) || 6);
      secondsUntilReset = Number(data.seconds_until_reset) || 0;

      var t = document.getElementById("fm-wheel-title");
      var s = document.getElementById("fm-wheel-sub");
      if (t && data.config) t.textContent = data.config.titulo || "Roleta da Sorte";
      if (s && data.config) s.textContent = data.config.subtitulo || "Gire a roleta e ganhe rodadas grátis!";

      if (wheel) {
        wheel.style.transition = "none";
        wheel.style.transform = "rotate(0deg)";
        // force reflow then restore transition
        void wheel.offsetWidth;
        wheel.style.transition = "transform " + duration + "s cubic-bezier(.17, .67, .12, .99)";
      }

      if (stage) {
        stage.classList.toggle("is-already", alreadySpun && !canSpin);
        stage.classList.remove("is-finished");
      }

      if (resultWrap) {
        if (alreadySpun && !canSpin) {
          resultWrap.innerHTML = limitPopupHtml(secondsUntilReset);
          startCountdown(secondsUntilReset);
        } else {
          resultWrap.innerHTML = "";
        }
      }

      paintPrizes(segments);
      paintMissions(data.missions || []);
      paintFreeSpins(data.free_spins);

      if (spinBtn) spinBtn.disabled = spinning || (!canSpin && !alreadySpun && logged);
      if (centerTrigger) centerTrigger.disabled = !canSpin || spinning;
      if (canSpin) setSpinLabel("GIRAR");
      else if (alreadySpun) setSpinLabel("OK");
      else if (!logged) setSpinLabel("LOGIN");
      else setSpinLabel("GIRAR");

      if (data.prize && alreadySpun && spinMsg) {
        spinMsg.textContent =
          "Prêmio de hoje: " + (data.prize.label || data.prize.prize_label || "OK");
      }
    }

    async function loadStatus() {
      if (!logged) {
        try {
          var res = await fetch(apiBase + "api/freemoney.php?action=public&_=" + Date.now(), {
            credentials: "same-origin",
          });
          var data = await res.json();
          if (data.ok) {
            segments = data.segments || [];
            paintPrizes(segments);
            if (data.config) {
              var t = document.getElementById("fm-wheel-title");
              var s = document.getElementById("fm-wheel-sub");
              if (t) t.textContent = data.config.titulo || t.textContent;
              if (s) s.textContent = data.config.subtitulo || s.textContent;
            }
            setSpinLabel("LOGIN");
          }
        } catch (e) {}
        return;
      }
      try {
        var res2 = await fetch(apiBase + "api/freemoney.php?action=status&_=" + Date.now(), {
          credentials: "same-origin",
        });
        var data2 = await res2.json();
        if (data2.need_login) return;
        applyStatus(data2);
      } catch (e) {
        if (missionsBox) missionsBox.innerHTML = '<div class="empty-state">Erro ao carregar Free Money</div>';
      }
    }

    async function doSpin() {
      if (!logged) {
        var loginBtn = document.querySelector("[data-open-login]");
        if (loginBtn) loginBtn.click();
        return;
      }
      if (!canSpin || spinning) {
        if (alreadySpun && resultWrap) {
          // já girou — só mantém popup
          return;
        }
        return;
      }
      spinning = true;
      if (spinBtn) spinBtn.disabled = true;
      if (centerTrigger) centerTrigger.disabled = true;
      setSpinLabel("GIRANDO...");
      if (spinMsg) spinMsg.textContent = "";
      tone("spin");

      var tickTimers = [];
      var elapsedTick = 0;
      for (var tickIndex = 0; tickIndex < 42; tickIndex++) {
        elapsedTick += 52 + Math.pow(tickIndex, 1.45) * 2.35;
        (function (index, delay) {
          tickTimers.push(
            setTimeout(function () {
              tone("tick", Math.max(80, 2000 * (1 - index / 42)));
            }, delay)
          );
        })(tickIndex, elapsedTick);
      }

      try {
        var fd = new FormData();
        fd.append("action", "spin");
        withCsrf(fd);
        var res = await fetch(apiBase + "api/freemoney.php", {
          method: "POST",
          body: fd,
          credentials: "same-origin",
        });
        var data = await res.json();
        if (!data.ok) {
          tickTimers.forEach(clearTimeout);
          spinning = false;
          setSpinLabel("GIRAR");
          if (spinBtn) spinBtn.disabled = false;
          if (centerTrigger) centerTrigger.disabled = false;
          if (spinMsg) spinMsg.textContent = data.message || data.error || "Não foi possível girar";
          loadStatus();
          return;
        }

        segments = data.segments || segments;
        var target = Number(data.winning_index || 0);
        var total = (data.segments || segments).length || 10;
        var targetSlice = 360 / total;
        var targetMargin = targetSlice * 0.1;
        var targetFromTop =
          target * targetSlice + targetMargin + Math.random() * (targetSlice - targetMargin * 2);
        var degrees = 360 * (5 + Math.floor(Math.random() * 3)) + (360 - targetFromTop);

        if (wheel) {
          wheel.style.transitionDuration = duration + "s";
          wheel.style.transform = "rotate(" + degrees + "deg)";
        }

        setTimeout(function () {
          tickTimers.forEach(clearTimeout);
          tone("win");
          spinning = false;
          canSpin = false;
          alreadySpun = true;
          if (stage) {
            stage.classList.add("is-finished");
            stage.classList.add("is-already");
          }
          if (centerTrigger) centerTrigger.disabled = true;
          setSpinLabel("ENTENDIDO");
          if (spinBtn) spinBtn.disabled = false;
          if (resultWrap) {
            resultWrap.innerHTML = winPopupHtml(data.prize || {});
            resultWrap.querySelectorAll("[data-fm-ok]").forEach(function (b) {
              b.addEventListener("click", function () {
                if (stage) stage.classList.remove("is-finished");
                loadStatus();
              });
            });
          }
          if (spinMsg) {
            var prize = data.prize || {};
            spinMsg.textContent =
              "Você ganhou: " + (prize.label || prize.prize_label || prize.short_label || "prêmio!") + " 🎉";
          }
        }, Math.round(duration * 1000 + 100));
      } catch (e) {
        tickTimers.forEach(clearTimeout);
        spinning = false;
        setSpinLabel("GIRAR");
        if (spinBtn) spinBtn.disabled = false;
        if (centerTrigger) centerTrigger.disabled = false;
        if (spinMsg) spinMsg.textContent = "Erro de conexão";
      }
    }

    if (spinBtn) spinBtn.addEventListener("click", doSpin);
    if (centerTrigger) centerTrigger.addEventListener("click", doSpin);

    if (resultWrap) {
      resultWrap.addEventListener("click", function (e) {
        var ok = e.target.closest ? e.target.closest("[data-fm-ok]") : null;
        if (!ok) return;
        if (stage) stage.classList.remove("is-finished");
      });
    }

    loadStatus();
  })();

  /* ---------- Carteira multi-moeda: trocar moeda ativa ---------- */
  function showWalletMsg(text, isErr) {
    var el = document.getElementById("wallet-switch-msg");
    if (!el) return;
    el.hidden = false;
    el.textContent = text || "";
    el.className = "wallet-note" + (isErr ? " wallet-note--err" : " wallet-note--ok");
  }

  document.querySelectorAll("[data-wallet-switch]").forEach(function (btn) {
    btn.addEventListener("click", async function () {
      var cur = (btn.getAttribute("data-wallet-switch") || "").toUpperCase();
      if (!cur) return;
      var prev = btn.textContent;
      btn.disabled = true;
      btn.textContent = "…";
      try {
        var res = await fetch(apiBase + "api/wallet_switch.php", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-Token": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
          },
          body: JSON.stringify(withCsrf({ currency: cur })),
        });
        var data = await res.json().catch(function () {
          return null;
        });
        if (!data || !data.ok) {
          showWalletMsg((data && data.error) || "Erro ao trocar carteira", true);
          btn.disabled = false;
          btn.textContent = prev;
          return;
        }
        applyBalanceToUi(data.balance, data.currency, data.currency_symbol);
        showWalletMsg("Carteira ativa: " + (data.currency || cur), false);
        // recarrega para atualizar cards e histórico
        setTimeout(function () {
          window.location.reload();
        }, 350);
      } catch (e) {
        showWalletMsg("Erro de conexão", true);
        btn.disabled = false;
        btn.textContent = prev;
      }
    });
  });

  // aviso na carteira se veio sem saldo do jogo
  if (document.getElementById("wallet-page") && /[?&]need_balance=1/.test(window.location.search || "")) {
    showWalletMsg("Sem saldo na carteira ativa. Deposite ou escolha outra moeda para jogar.", true);
  }

  /* ---------- PWA: service worker + instalar app (Android / iOS / Desktop) ---------- */
  (function initPwa() {
    var i18n = window.__PWA_I18N__ || {};
    var deferredPrompt = null;
    var isStandalone =
      window.matchMedia("(display-mode: standalone)").matches ||
      window.navigator.standalone === true ||
      document.referrer.indexOf("android-app://") === 0;

    function isIos() {
      return /iphone|ipad|ipod/i.test(navigator.userAgent || "") ||
        (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
    }

    function markInstalled() {
      document.querySelectorAll("[data-pwa-install]").forEach(function (btn) {
        btn.disabled = true;
        btn.classList.add("is-installed");
        var span = btn.querySelector("span");
        if (span) span.textContent = i18n.installed || "App instalado";
      });
      var hint = document.getElementById("pwa-install-hint");
      if (hint) {
        hint.hidden = false;
        hint.textContent = i18n.installed || "App instalado";
      }
    }

    if (isStandalone) {
      markInstalled();
    }

    // registra SW
    if ("serviceWorker" in navigator) {
      var swUrl = window.__SW_URL__ || (apiBase + "sw.js");
      var swScope = window.__SW_SCOPE__ || apiBase;
      window.addEventListener("load", function () {
        navigator.serviceWorker.register(swUrl, { scope: swScope }).catch(function () {});
      });
    }

    window.addEventListener("beforeinstallprompt", function (e) {
      e.preventDefault();
      deferredPrompt = e;
      document.querySelectorAll("[data-pwa-install]").forEach(function (btn) {
        btn.disabled = false;
        btn.classList.remove("is-installed");
      });
    });

    window.addEventListener("appinstalled", function () {
      deferredPrompt = null;
      markInstalled();
    });

    function openIosModal() {
      var modal = document.getElementById("pwa-ios-modal");
      if (!modal) {
        if (typeof showToast === "function") {
          showToast(i18n.iosHint || "Safari → Compartilhar → Adicionar à Tela de Início", "info");
        } else {
          alert(i18n.iosHint || "Safari → Share → Add to Home Screen");
        }
        return;
      }
      modal.hidden = false;
      modal.setAttribute("aria-hidden", "false");
      document.body.classList.add("modal-open");
    }

    function closeIosModal() {
      var modal = document.getElementById("pwa-ios-modal");
      if (!modal) return;
      modal.hidden = true;
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
    }

    document.querySelectorAll("[data-pwa-ios-close]").forEach(function (el) {
      el.addEventListener("click", closeIosModal);
    });

    async function triggerInstall(btn) {
      var platform = (btn && btn.getAttribute("data-pwa-platform")) || "";
      var hint = document.getElementById("pwa-install-hint");

      if (isStandalone) {
        markInstalled();
        return;
      }

      // iOS nunca dispara beforeinstallprompt
      if (platform === "ios" || (isIos() && !deferredPrompt)) {
        openIosModal();
        if (hint) {
          hint.hidden = false;
          hint.textContent = i18n.iosHint || "";
        }
        return;
      }

      if (deferredPrompt) {
        try {
          if (btn) {
            btn.disabled = true;
            var sp = btn.querySelector("span");
            if (sp) sp.textContent = i18n.installing || "…";
          }
          deferredPrompt.prompt();
          var choice = await deferredPrompt.userChoice;
          deferredPrompt = null;
          if (choice && choice.outcome === "accepted") {
            markInstalled();
          } else if (btn) {
            btn.disabled = false;
            var sp2 = btn.querySelector("span");
            if (sp2) {
              sp2.textContent =
                platform === "desktop"
                  ? i18n.installDesktop || i18n.install
                  : i18n.installAndroid || i18n.install;
            }
          }
        } catch (err) {
          if (btn) btn.disabled = false;
          if (hint) {
            hint.hidden = false;
            hint.textContent = i18n.unavailable || "";
          }
        }
        return;
      }

      // fallback: sem evento de install (Firefox, Safari desktop, etc.)
      if (isIos()) {
        openIosModal();
        return;
      }
      if (hint) {
        hint.hidden = false;
        hint.textContent = i18n.unavailable || "";
      } else if (typeof showToast === "function") {
        showToast(i18n.unavailable || "Use the browser menu to install", "info");
      }
    }

    document.querySelectorAll("[data-pwa-install]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        triggerInstall(btn);
      });
    });
  })();
})();
