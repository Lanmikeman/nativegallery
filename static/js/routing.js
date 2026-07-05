(function () {
  "use strict";

  if (window.__ngRoutingReady) return;

  const CACHE_TTL = 300000;
  const cache = new Map();
  let loadingTimeout = null;
  let loader = null;

  const PERMANENT_SCRIPTS = [
    "/static/js/jquery.js",
    "/static/js/music-player.js",
    "/static/js/routing.js",
    "/static/js/comments.js",
  ];

  const SKIP_PATH_PREFIXES = ["/api/", "/auth/", "/logout"];

  const loadedScriptSrcs = new Set();
  document.querySelectorAll("script[src]").forEach((script) => {
    const src = script.getAttribute("src");
    if (src) loadedScriptSrcs.add(src);
  });

  const executedInlineScripts = new Set();
  let lastPath = location.pathname;

  function isPhotoPath(path) {
    return /\/photo\/\d+/.test(path);
  }

  function normalizeNavUrl(url) {
    const u = new URL(url, location.origin);
    return u.pathname + u.search;
  }

  function getTmain() {
    return document.querySelector("table.tmain");
  }

  function shouldIntercept(link, event) {
    try {
      if (!link || !link.href) return false;
      if (link.hasAttribute("data-no-ajax")) return false;
      if (event && (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) {
        return false;
      }
      if (link.target === "_blank" || link.hasAttribute("download")) return false;

      const rawHref = link.getAttribute("href");
      if (!rawHref || rawHref === "#" || rawHref.charAt(0) === "#") return false;

      const url = new URL(link.href, location.origin);
      if (url.origin !== location.origin) return false;
      if (url.hash && url.pathname === location.pathname && url.search === location.search) {
        return false;
      }

      const path = url.pathname;
      if (SKIP_PATH_PREFIXES.some((p) => path === p.replace(/\/$/, "") || path.startsWith(p))) {
        return false;
      }

      return true;
    } catch {
      return false;
    }
  }

  function handleClick(e) {
    if (e.target.closest(
      "#ng-music-bar, .ng-music-page, #prev, #next, #photobar, [data-action], [data-play-item], [data-queue-item], [data-play-playlist], [data-play-pl-item]"
    )) {
      return;
    }

    const link = e.target.closest("a");
    if (!link || !shouldIntercept(link, e)) return;
    e.preventDefault();
    e.stopPropagation();
    navigateTo(link.href, false);
  }

  function handlePopState() {
    navigateTo(window.location.href, true);
  }

  async function navigateTo(url, isHistoryNavigation) {
    const normUrl = normalizeNavUrl(url);

    try {
      showLoader();
      window.commentsCleanup?.();
      delete window.commentsInitialized;

      const cached = cache.get(normUrl);
      if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
        if (!updatePage(cached.html, normUrl, isHistoryNavigation)) {
          window.location.href = normUrl;
        }
        return;
      }

      const html = await fetchContent(normUrl);
      cache.set(normUrl, { html, timestamp: Date.now() });

      if (!updatePage(html, normUrl, isHistoryNavigation)) {
        window.location.href = normUrl;
      }
    } catch (error) {
      console.error("Navigation error:", error);
      window.location.href = normUrl;
    } finally {
      hideLoader();
    }
  }

  async function fetchContent(url) {
    const response = await fetch(url, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
    });
    if (!response.ok) {
      throw new Error("HTTP " + response.status);
    }
    return await response.text();
  }

  function removeTmainFooterRow() {
    const tmain = getTmain();
    if (!tmain) return;
    tmain.querySelectorAll("tr").forEach((tr) => {
      if (tr.querySelector("td.footer")) tr.remove();
    });
  }

  function ensureTmainFooterRow(footerHtml) {
    const tmain = getTmain();
    if (!tmain) return;

    let footerTr = Array.from(tmain.querySelectorAll("tr")).find((tr) => tr.querySelector("td.footer"));
    if (!footerTr) {
      footerTr = document.createElement("tr");
      const td = document.createElement("td");
      td.className = "footer";
      footerTr.appendChild(td);
      tmain.appendChild(footerTr);
    }

    const td = footerTr.querySelector("td.footer");
    if (td && footerHtml !== undefined) {
      td.innerHTML = footerHtml;
    }
  }

  function removePmain() {
    const pmain = document.querySelector("#pmain");
    if (pmain) pmain.remove();
  }

  function replacePmain(doc) {
    removePmain();
    const newPmain = doc.querySelector("#pmain");
    if (!newPmain) return null;

    const tmain = getTmain();
    const clone = newPmain.cloneNode(true);
    if (tmain && tmain.parentNode) {
      tmain.parentNode.insertBefore(clone, tmain.nextSibling);
    } else {
      document.body.appendChild(clone);
    }

    return clone;
  }

  function executeScriptsIn(root) {
    if (!root) return;

    Array.from(root.querySelectorAll("script")).forEach((oldScript) => {
      const code = (oldScript.textContent || "").trim();
      if (!oldScript.src && (!code || /^Tracy\.Debug\.init/.test(code))) {
        oldScript.remove();
        return;
      }

      if (!oldScript.src) {
        const forceRestart = oldScript.hasAttribute("data-restart");
        const hash = simpleHash(code);
        if (!forceRestart && executedInlineScripts.has(hash)) {
          oldScript.remove();
          return;
        }
        if (!forceRestart) {
          executedInlineScripts.add(hash);
        }
      }

      const newScript = document.createElement("script");
      Array.from(oldScript.attributes).forEach((attr) => {
        newScript.setAttribute(attr.name, attr.value);
      });

      if (oldScript.src) {
        newScript.src = oldScript.src;
        newScript.async = false;
      } else {
        newScript.textContent = code;
      }

      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
  }

  function cleanupOrphanFooterTables() {
    document.querySelectorAll("body > table").forEach((tbl) => {
      if (tbl.classList.contains("tmain")) return;
      if (tbl.querySelector("td.footer")) tbl.remove();
    });
  }

  function updateMainContent(doc) {
    const currMain = document.querySelector("table.tmain td.main");
    const newMain = doc.querySelector("td.main");
    if (!currMain || !newMain) return false;
    currMain.innerHTML = newMain.innerHTML;
    return true;
  }

  function updatePage(html, url, isHistoryNavigation) {
    try {
      const doc = new DOMParser().parseFromString(html, "text/html");
      const newTitle = doc.title;
      const newPath = new URL(url, location.origin).pathname;
      const photoPage = isPhotoPath(newPath);

      if (!updateMainContent(doc)) {
        console.warn("SPA: td.main not found");
        return false;
      }

      cleanupOrphanFooterTables();

      let pmainEl = null;
      if (photoPage) {
        removeTmainFooterRow();
        pmainEl = replacePmain(doc);
      } else {
        removePmain();
        const newFooter = doc.querySelector("td.footer");
        ensureTmainFooterRow(newFooter ? newFooter.innerHTML : "");
      }

      const navbar = document.querySelector("#navbard");
      const titleSmall = document.querySelector("#title-small");
      if (navbar) navbar.style.display = photoPage ? "none" : "";
      if (titleSmall) titleSmall.style.display = photoPage ? "" : "none";

      if (!isHistoryNavigation) {
        window.history.pushState({ ngSpa: true }, "", url);
      }

      if (newTitle) document.title = newTitle;

      const currMain = document.querySelector("table.tmain td.main");
      executeScriptsIn(currMain);
      executeScriptsIn(pmainEl);

      reloadStylesheets(doc);
      reloadExternalScripts(doc);
      reloadScopedScripts(doc, currMain, pmainEl);

      window.scrollTo({ top: 0, behavior: "smooth" });

      lastPath = newPath;
      window.dispatchEvent(
        new CustomEvent("ng:navigate", { detail: { path: newPath, url: url } })
      );

      return true;
    } catch (err) {
      console.error("SPA updatePage error:", err);
      return false;
    }
  }

  function reloadStylesheets(doc) {
    const loadedPaths = new Set(
      Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map((link) => {
        try {
          return new URL(link.href, location.origin).pathname;
        } catch {
          return link.getAttribute("href") || "";
        }
      })
    );

    doc.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
      const href = link.getAttribute("href");
      if (!href) return;

      let pathname = href;
      try {
        pathname = new URL(href, location.origin).pathname;
      } catch {
        return;
      }

      if (loadedPaths.has(pathname)) return;

      const newLink = document.createElement("link");
      newLink.rel = "stylesheet";
      newLink.href = href;
      document.head.appendChild(newLink);
      loadedPaths.add(pathname);
    });
  }

  function reloadExternalScripts(doc) {
    const scripts = Array.from(doc.querySelectorAll("script[src]"));
    const loadedUrls = new Set(Array.from(document.scripts).map((s) => s.src));

    const loadScript = (index) => {
      if (index >= scripts.length) return;

      const script = scripts[index];
      const src = script.src;
      const srcPath = (() => {
        try {
          return new URL(src).pathname;
        } catch {
          return src;
        }
      })();

      if (PERMANENT_SCRIPTS.some((p) => srcPath.endsWith(p))) {
        loadScript(index + 1);
        return;
      }

      if (!loadedUrls.has(src)) {
        const newScript = document.createElement("script");
        newScript.src = src;
        newScript.async = false;
        Array.from(script.attributes).forEach((attr) => {
          newScript.setAttribute(attr.name, attr.value);
        });
        newScript.onload = () => {
          loadedUrls.add(src);
          loadScript(index + 1);
        };
        newScript.onerror = () => {
          console.error("Failed to load:", src);
          loadScript(index + 1);
        };
        document.body.appendChild(newScript);
      } else {
        loadScript(index + 1);
      }
    };

    loadScript(0);
  }

  function reloadScopedScripts(doc, mainEl, pmainEl) {
    const scopes = [doc.querySelector("td.main"), doc.querySelector("#pmain")].filter(Boolean);
    const loadedUrls = new Set(Array.from(document.scripts).map((s) => s.src));

    scopes.forEach((scope) => {
      scope.querySelectorAll("script[src]").forEach((script) => {
        const src = script.getAttribute("src");
        if (!src) return;

        let absoluteSrc;
        try {
          absoluteSrc = new URL(src, location.origin).href;
        } catch {
          return;
        }

        const srcPath = new URL(absoluteSrc).pathname;
        if (PERMANENT_SCRIPTS.some((p) => srcPath.endsWith(p))) return;
        if (loadedUrls.has(absoluteSrc)) return;

        const newScript = document.createElement("script");
        newScript.src = src;
        newScript.async = false;
        Array.from(script.attributes).forEach((attr) => {
          newScript.setAttribute(attr.name, attr.value);
        });
        (mainEl || pmainEl || document.body).appendChild(newScript);
        loadedUrls.add(absoluteSrc);
      });
    });
  }

  function simpleHash(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      hash = (hash << 5) - hash + str.charCodeAt(i);
      hash |= 0;
    }
    return hash;
  }

  function createLoader() {
    const el = document.createElement("div");
    el.id = "ng-spa-loader";
    el.style.cssText =
      "position:fixed;top:36px;right:12px;padding:6px 12px;background:var(--theme-bg-color,#333);color:var(--theme-fg-color,#fff);border:1px solid var(--theme-border-color,#666);border-radius:4px;display:none;z-index:9999;font-size:13px;font-family:var(--narrow-font,sans-serif);";
    el.textContent = "Загрузка…";
    document.body.appendChild(el);
    return el;
  }

  function showLoader() {
    if (!loader) return;
    clearTimeout(loadingTimeout);
    loadingTimeout = setTimeout(() => {
      loader.style.display = "block";
    }, 200);
  }

  function hideLoader() {
    if (!loader) return;
    clearTimeout(loadingTimeout);
    loader.style.display = "none";
  }

  function boot() {
    if (window.__ngRoutingReady) return;
    window.__ngRoutingReady = true;

    loader = createLoader();
    document.addEventListener("click", handleClick, true);
    window.addEventListener("popstate", handlePopState);
    window.ngSpaNavigate = (url) => navigateTo(url, false);

    if (!window.history.state || !window.history.state.ngSpa) {
      window.history.replaceState({ ngSpa: true }, "", location.href);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();