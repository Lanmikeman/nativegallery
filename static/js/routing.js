(function () {
  "use strict";

  if (window.__ngRoutingReady) return;
  window.__ngRoutingReady = true;

  const CACHE_TTL = 300000;
  const cache = new Map();
  let loadingTimeout = null;

  const PERMANENT_SCRIPTS = [
    "/static/js/jquery.js",
    "/static/js/music-player.js",
    "/static/js/routing.js",
  ];

  const CONTENT_SELECTORS = ["td.main", "#pmain"];
  const SKIP_PATH_PREFIXES = ["/api/", "/auth/", "/logout"];

  const loadedScriptSrcs = new Set();
  document.querySelectorAll("script[src]").forEach((script) => {
    const src = script.getAttribute("src");
    if (src) loadedScriptSrcs.add(src);
  });

  const executedInlineScripts = new Set();
  let lastPath = location.pathname;

  const loader = createLoader();

  function normalizeNavUrl(url) {
    const u = new URL(url, location.origin);
    return u.pathname + u.search;
  }

  function shouldIntercept(link, event) {
    try {
      if (!link || !link.href) return false;
      if (link.dataset.noAjax) return false;
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

  function updatePage(html, url, isHistoryNavigation) {
    try {
      const doc = new DOMParser().parseFromString(html, "text/html");
      const newTitle = doc.title;
      const newPath = new URL(url, location.origin).pathname;

      let contentUpdated = false;
      CONTENT_SELECTORS.forEach((selector) => {
        const container = document.querySelector(selector);
        const newContent = doc.querySelector(selector);
        if (container && newContent) {
          container.innerHTML = newContent.innerHTML;
          contentUpdated = true;
        }
      });

      if (!contentUpdated) {
        console.warn("SPA: td.main / #pmain not found in response");
        return false;
      }

      const currPmain = document.querySelector("#pmain");
      const newPmain = doc.querySelector("#pmain");
      if (!currPmain && newPmain) {
        document.body.appendChild(newPmain.cloneNode(true));
      } else if (currPmain && !newPmain) {
        currPmain.remove();
      } else if (currPmain && newPmain) {
        currPmain.innerHTML = newPmain.innerHTML;
      }

      const navbar = document.querySelector("#navbard");
      const titleSmall = document.querySelector("#title-small");
      const isPhoto = /\/photo\/\d+/.test(newPath);
      if (navbar) navbar.style.display = isPhoto ? "none" : "";
      if (titleSmall) titleSmall.style.display = isPhoto ? "" : "none";

      const currFooter = document.querySelector("td.footer");
      const newFooter = doc.querySelector("td.footer");
      if (currFooter && newFooter) {
        currFooter.innerHTML = newFooter.innerHTML;
      }

      const footers = Array.from(document.querySelectorAll("footer"));
      if (footers.length > 1) footers.slice(1).forEach((f) => f.remove());

      if (!isHistoryNavigation) {
        window.history.pushState({ ngSpa: true }, "", url);
      }

      if (newTitle) document.title = newTitle;

      reloadExternalScripts(doc);
      reloadInlineScripts();

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

  function reloadInlineScripts() {
    document.querySelectorAll("script:not([src])").forEach((oldScript) => {
      const code = oldScript.textContent.trim();
      if (!code || /^Tracy\.Debug\.init/.test(code)) return;

      const hash = simpleHash(code);
      if (executedInlineScripts.has(hash)) return;

      const newScript = document.createElement("script");
      Array.from(oldScript.attributes).forEach((attr) =>
        newScript.setAttribute(attr.name, attr.value)
      );
      newScript.textContent = code;
      oldScript.parentNode.replaceChild(newScript, oldScript);
      executedInlineScripts.add(hash);
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
    clearTimeout(loadingTimeout);
    loadingTimeout = setTimeout(() => {
      loader.style.display = "block";
    }, 200);
  }

  function hideLoader() {
    clearTimeout(loadingTimeout);
    loader.style.display = "none";
  }

  document.addEventListener("click", handleClick, true);
  window.addEventListener("popstate", handlePopState);
  window.ngSpaNavigate = (url) => navigateTo(url, false);

  if (!window.history.state || !window.history.state.ngSpa) {
    window.history.replaceState({ ngSpa: true }, "", location.href);
  }
})();