/**
 * LaraInk SPA Router and Page Manager
 * Handles client-side navigation with AJAX and History API
 * Prevents full page reloads for better UX
 */

(function () {
  "use strict";

  // Initialize configuration
  const config = window.__LARA_INK_CONFIG__ || {
    api_base_url: "",
    login_route: "/login",
    unauthorized_route: "/unauthorized",
    spa_mode: false,
  };

  const pagesPath = window.__LARA_INK_PAGES_PATH__ || "/lara-ink/pages";

  // Initialize lara_ink global object
  window.lara_ink = window.lara_ink || {};

  // Initialize translations (will be populated by lara-ink-lang.js)
  window.lara_ink.translations = window.lara_ink.translations || {};

  // Get saved locale or use document lang or fallback to 'en'
  let currentLocale =
    localStorage.getItem("lara_ink_locale") ||
    document.documentElement.lang ||
    "en";

  // Set document lang to current locale
  if (document.documentElement.lang !== currentLocale) {
    document.documentElement.lang = currentLocale;
  }

  Object.assign(window.lara_ink, {
    routes: window.__LARA_INK_ROUTES__ || {},
    cache_manifest: window.__LARA_INK_CACHE__ || {},
    api_base_url: config.api_base_url,
    token: localStorage.getItem("lara_ink_token") || null,
    request_queue: [],
    spa_enabled: config.spa_mode,
    currentLocale: currentLocale,

    async newReq(endpoint, options = {}) {
      const headers = {
        Accept: "application/json",
        "Content-Type": "application/json",
        ...options.headers,
      };

      if (this.token) {
        headers.Authorization = `Bearer ${this.token}`;
      }

      const response = await fetch(`${this.api_base_url}${endpoint}`, {
        ...options,
        headers,
      });

      if (response.status === 401) {
        this.logout();
        window.location.href = config.login_route || "/login";
        return response;
      }

      if (response.status === 403) {
        window.location.href = config.unauthorized_route || "/unauthorized";
        return response;
      }

      return response;
    },

    async is_authenticated() {
      if (!this.token) return false;

      const lastCheck = localStorage.getItem("lara_ink_last_auth_check");
      const now = Date.now();

      if (lastCheck && now - parseInt(lastCheck) < 60000) {
        return true;
      }

      try {
        const response = await this.newReq("/is-authenticated", {
          method: "GET",
        });

        if (response.status === 401) {
          this.logout();
          return false;
        }

        if (response.ok) {
          const data = await response.json();
          if (data.token) {
            this.token = data.token;
            localStorage.setItem("lara_ink_token", data.token);
          }
          localStorage.setItem("lara_ink_last_auth_check", now.toString());
          return true;
        }
      } catch (error) {
        console.error("Auth check failed:", error);
      }

      return false;
    },

    async logout() {
      if (this.token) {
        await this.newReq("/logout", { method: "POST" });
      }

      this.token = null;
      localStorage.removeItem("lara_ink_token");
      localStorage.removeItem("lara_ink_last_auth_check");
    },

    /**
     * Translate a key to the current locale
     * @param {string} key - Translation key (e.g., 'basic.welcome')
     * @param {object} replacements - Optional replacements for placeholders
     * @returns {string} Translated string or key if not found
     */
    normalizeLocale(locale) {
      if (!locale || typeof locale !== "string") {
        return "";
      }

      return locale.toLowerCase().replace(/-/g, "_");
    },

    resolveLocaleCandidates(locale) {
      const translations = this.translations || {};
      const availableLocales = Object.keys(translations);

      if (availableLocales.length === 0) {
        return [];
      }

      const requestedLocale =
        locale ?? this.currentLocale ?? availableLocales[0];
      const requestedNormalized = this.normalizeLocale(requestedLocale);

      const normalizedMap = availableLocales.map((original) => ({
        original,
        normalized: this.normalizeLocale(original),
      }));

      const candidates = [];
      const pushCandidate = (candidate) => {
        if (candidate && !candidates.includes(candidate)) {
          candidates.push(candidate);
        }
      };

      // 1. Exact match for requested locale
      normalizedMap.forEach(({ original, normalized }) => {
        if (normalized === requestedNormalized) {
          pushCandidate(original);
        }
      });

      // 2. Match by base language (e.g., en -> en_US)
      const requestedBase = requestedNormalized.split("_")[0];
      normalizedMap.forEach(({ original, normalized }) => {
        if (normalized.split("_")[0] === requestedBase) {
          pushCandidate(original);
        }
      });

      // 3. Prefer common fallbacks
      ["en_us", "en"].forEach((fallback) => {
        normalizedMap.forEach(({ original, normalized }) => {
          if (normalized === fallback) {
            pushCandidate(original);
          }
        });
      });

      // 4. Append remaining locales
      normalizedMap.forEach(({ original }) => pushCandidate(original));

      return candidates;
    },

    trans(key, replacements = {}) {
      const translations = this.translations || {};
      const candidateLocales = this.resolveLocaleCandidates();
      let translation = null;

      for (const locale of candidateLocales) {
        const localeTranslations = translations[locale];

        if (
          localeTranslations &&
          Object.prototype.hasOwnProperty.call(localeTranslations, key)
        ) {
          translation = localeTranslations[key];

          if (typeof translation === "string" && translation !== "") {
            break;
          }
        }
      }

      if (typeof translation !== "string" || translation === "") {
        translation = key;
      }

      Object.keys(replacements).forEach((placeholder) => {
        translation = translation.replace(
          `:${placeholder}`,
          replacements[placeholder]
        );
      });

      return translation;
    },

    /**
     * Change the current locale
     * @param {string} locale - New locale (e.g., 'pt-BR', 'en')
     */
    setLocale(locale) {
      const candidates = this.resolveLocaleCandidates(locale);

      if (candidates.length === 0) {
        console.warn(`Locale '${locale}' not found in translations`);
        return;
      }

      const resolvedLocale = candidates[0];

      this.currentLocale = resolvedLocale;
      document.documentElement.lang = resolvedLocale;

      // Store preference
      localStorage.setItem("lara_ink_locale", resolvedLocale);

      // Trigger event for components to update
      window.dispatchEvent(
        new CustomEvent("locale-changed", {
          detail: { locale: resolvedLocale, requested: locale },
        })
      );
    },

    /**
     * Get available locales
     * @returns {array} Array of available locale codes
     */
    getAvailableLocales() {
      return Object.keys(this.translations);
    },
  });

  const resolvedInitialLocale =
    window.lara_ink.resolveLocaleCandidates(currentLocale)[0] || currentLocale;
  window.lara_ink.currentLocale = resolvedInitialLocale;

  if (document.documentElement.lang !== resolvedInitialLocale) {
    document.documentElement.lang = resolvedInitialLocale;
  }

  function laraInkApp() {
    return {
      loading: false,
      cache: new Map(),
      failedRoutes: new Map(),

      setSystemMode(enabled) {
        document.body.classList.toggle("lara-ink-system", Boolean(enabled));
      },

      executeScripts(container) {
        const scripts = container.querySelectorAll("script");
        scripts.forEach((inlineScript) => {
          const newScript = document.createElement("script");

          Array.from(inlineScript.attributes).forEach((attr) => {
            newScript.setAttribute(attr.name, attr.value);
          });

          if (inlineScript.src) {
            newScript.src = inlineScript.src;
          } else {
            newScript.textContent = inlineScript.textContent;
          }

          inlineScript.replaceWith(newScript);
        });
      },

      init() {
        this.handleRoute();
        window.addEventListener("popstate", () => this.handleRoute());
        this.interceptLinks();
        this.cleanOldCache();
      },

      async handleRoute() {
        const path = window.location.pathname;

        if (path.startsWith("/error")) {
          this.setSystemMode(true);
          this.loading = false;
          return;
        }

        // Handle root path
        if (path === "/" || path === "/index.html") {
          const cachedPage = this.getCachedPage("/index");
          if (cachedPage) {
            this.renderPage(cachedPage);
            return;
          }

          try {
            const response = await fetch(`${pagesPath}/index.html`);
            if (response.ok) {
              const html = await response.text();
              this.renderPage(html);
              this.cachePage("/index", html);
              return;
            }
          } catch (error) {
            // No index page found
          }

          this.setSystemMode(true);
          this.$el.innerHTML = this.getWelcomeTemplate();
          return;
        }

        await this.loadPage(path);
      },

      pageExists(path) {
        const normalizedPath = normalizePath(path);

        if (normalizedPath === "/") {
          return true;
        }

        const routes = window.lara_ink.routes || {};
        return Object.prototype.hasOwnProperty.call(routes, normalizedPath);
      },

      async loadPage(path) {
        const normalizedPath = normalizePath(path);
        const cacheKey = normalizedPath;

        if (!this.pageExists(normalizedPath)) {
          this.setSystemMode(true);
          const errorHtml = this.getErrorTemplate(
            "404",
            "Page Not Found",
            "The page you are looking for does not exist."
          );
          this.$el.innerHTML = errorHtml;
          const timestamp = Date.now();
          this.cache.set(cacheKey, {
            html: errorHtml,
            expires: timestamp + 60_000,
          });
          this.failedRoutes.set(cacheKey, {
            html: errorHtml,
            timestamp,
          });
          this.loading = false;
          return;
        }

        const failed = this.failedRoutes.get(cacheKey);
        const now = Date.now();

        if (failed && now - failed.timestamp < 60_000) {
          this.setSystemMode(true);
          this.$el.innerHTML = failed.html;
          this.loading = false;
          return;
        }

        this.loading = true;

        const cachedPage = this.getCachedPage(cacheKey);
        if (cachedPage) {
          this.renderPage(cachedPage);
          this.loading = false;
          return;
        }

        this.setSystemMode(true);
        this.$el.innerHTML = this.getLoadingTemplate();

        try {
          const response = await fetch(`${pagesPath}${normalizedPath}.html`);

          if (!response.ok) {
            throw new Error("Page not found");
          }

          const html = await response.text();
          this.renderPage(html);
          this.cachePage(cacheKey, html);
          this.failedRoutes.delete(cacheKey);
        } catch (error) {
          console.error("Failed to load page:", error);
          this.setSystemMode(true);
          const errorHtml = this.getErrorTemplate(
            "404",
            "Page Not Found",
            "The page you are looking for does not exist."
          );
          this.$el.innerHTML = errorHtml;

          const timestamp = Date.now();
          this.cache.set(cacheKey, {
            html: errorHtml,
            expires: timestamp + 60_000,
          });
          this.failedRoutes.set(cacheKey, {
            html: errorHtml,
            timestamp,
          });
        }

        this.loading = false;
      },

      getErrorTemplate(code, title, message) {
        return `
                <div class="lara-ink-message" data-lara-ink-system-page="inline-error">
                    <div class="lara-ink-message-icon lara-ink-error">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="lara-ink-message-code">${code}</div>
                    <h1 class="lara-ink-message-title">${title}</h1>
                    <p class="lara-ink-message-text">${message}</p>
                    <a href="/" class="lara-ink-button">Go Home</a>
                </div>
            `;
      },

      getWelcomeTemplate() {
        return `
                <div class="lara-ink-message" data-lara-ink-system-page="welcome">
                    <div class="lara-ink-message-icon lara-ink-welcome">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <h1 class="lara-ink-message-title">✒️ Welcome to LaraInk</h1>
                    <p class="lara-ink-message-text">Your SPA is ready. Start building amazing pages!</p>
                    <p class="lara-ink-message-text small">Create your <b>index.php</b> file in the <b>/pages</b> folder to get started.</p>
                    <div class="lara-ink-message-footer">
                        <a href="https://github.com/b7s/lara-ink" target="_blank" class="lara-ink-link">Documentation</a>
                        <span class="lara-ink-separator">•</span>
                        <a href="https://github.com/b7s/lara-ink/issues" target="_blank" class="lara-ink-link">Support</a>
                    </div>
                </div>
            `;
      },

      getLoadingTemplate() {
        return `
                <div class="lara-ink-message" data-lara-ink-system-page="loading">
                    <div class="lara-ink-spinner"></div>
                    <p class="lara-ink-message-text">Loading...</p>
                </div>
            `;
      },

      renderPage(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        let enableSystemMode = false;

        if (doc && doc.body) {
          removeDuplicateScripts(doc);
          const systemMarker = doc.body.querySelector(
            "[data-lara-ink-system-page]"
          );
          enableSystemMode = Boolean(systemMarker);
          const content = doc.body.innerHTML;
          this.$el.innerHTML = content;
          updateMetaTags(doc);

          if (doc.title) {
            document.title = doc.title;
          }
        } else {
          enableSystemMode = /data-lara-ink-system-page/.test(html);
          this.$el.innerHTML = html;
        }

        this.setSystemMode(enableSystemMode);

        this.executeScripts(this.$el);

        if (window.Alpine) {
          Alpine.initTree(this.$el);
        }
      },

      interceptLinks() {
        document.addEventListener("click", (e) => {
          const link = e.target.closest("a");

          if (
            e.defaultPrevented ||
            e.button !== 0 ||
            e.metaKey ||
            e.ctrlKey ||
            e.shiftKey ||
            e.altKey
          ) {
            return;
          }

          if (shouldHandleLink(link)) {
            e.preventDefault();
            const url = new URL(link.href, window.location.origin);
            const normalizedPath = normalizePath(url.pathname);

            window.history.pushState({}, "", url.href);

            if (normalizedPath === "/") {
              this.handleRoute();
              return;
            }

            this.loadPage(normalizedPath + url.search);
          }
        });
      },

      getCachedPage(path) {
        if (!this.cache.has(path)) return null;

        const cached = this.cache.get(path);
        const now = Date.now();

        if (cached.expires && cached.expires < now) {
          this.cache.delete(path);
          return null;
        }

        return cached.html;
      },

      cachePage(path, html) {
        const route = window.lara_ink.routes[path];
        const ttl = route ? window.lara_ink.cache_manifest[path]?.ttl : null;

        if (!ttl) return;

        const expires = Date.now() + ttl * 1000;
        this.cache.set(path, { html, expires });
      },

      cleanOldCache() {
        const now = Date.now();

        for (const [path, data] of this.cache.entries()) {
          if (data.expires && data.expires < now) {
            this.cache.delete(path);
          }
        }
      },
    };
  }

  window.laraInkApp = laraInkApp;

  // Initialize AJAX navigation with History API
  function initAjaxNavigation() {
    // Only enable for pages with user layouts (not SPA mode)
    if (config.spa_mode) {
      return; // SPA mode handles its own routing
    }

    // Intercept all link clicks
    document.addEventListener("click", function (e) {
      if (
        e.defaultPrevented ||
        e.button !== 0 ||
        e.metaKey ||
        e.ctrlKey ||
        e.shiftKey ||
        e.altKey
      ) {
        return;
      }

      const link = e.target.closest("a");
      if (shouldHandleLink(link)) {
        e.preventDefault();

        const url = new URL(link.href, window.location.origin);
        const normalizedPath = normalizePath(url.pathname);

        if (normalizedPath === "/") {
          navigateToPage("/", true);
          return;
        }

        navigateToPage(normalizedPath + url.search + url.hash);
      }
    });

    // Handle browser back/forward buttons
    window.addEventListener("popstate", function (e) {
      if (e.state && e.state.path) {
        navigateToPage(e.state.path, false);
      }
    });

    // Store initial state
    if (window.history.state === null) {
      window.history.replaceState(
        { path: window.location.pathname },
        "",
        window.location.pathname
      );
    }
  }

  // Navigate to a page using AJAX
  async function navigateToPage(path, pushState = true) {
    try {
      // Show loading indicator
      document.body.classList.add("lara-ink-loading");

      // Fetch the new page
      const response = await fetch(path, {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const html = await response.text();

      // Parse the HTML
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");

      // Extract the new content
      const newRoot = doc.getElementById("lara-ink-root");
      const currentRoot = document.getElementById("lara-ink-root");

      if (newRoot && currentRoot) {
        // Replace content
        currentRoot.innerHTML = newRoot.innerHTML;

        // Update title
        if (doc.title) {
          document.title = doc.title;
        }

        // Update meta tags
        updateMetaTags(doc);

        // Execute new scripts
        executeScripts(currentRoot);

        // Reinitialize Alpine.js
        if (window.Alpine) {
          Alpine.initTree(currentRoot);
        }

        // Update history
        if (pushState) {
          window.history.pushState({ path }, "", path);
        }

        // Scroll to top
        window.scrollTo(0, 0);
      }
    } catch (error) {
      console.error("Navigation error:", error);

      // Extract status code from error message if available
      const statusMatch = error.message.match(/HTTP (\d+)/);
      const statusCode = statusMatch ? statusMatch[1] : "404";

      // Check if we're already on the error page to prevent infinite loop
      if (path.includes("/error")) {
        // Show inline error message
        const currentRoot = document.getElementById("lara-ink-root");
        if (currentRoot) {
          currentRoot.innerHTML = getInlineErrorTemplate(statusCode);
        }
      } else {
        // Redirect to error page with status code
        window.location.href = `/error?code=${statusCode}`;
      }
    } finally {
      document.body.classList.remove("lara-ink-loading");
    }
  }

  // Update meta tags from new document
  function updateMetaTags(newDoc) {
    // Get all current and new meta tags
    const currentMetas = document.querySelectorAll(
      "head meta[name], head meta[property]"
    );
    const newMetas = newDoc.querySelectorAll(
      "head meta[name], head meta[property]"
    );

    // Create a map of new meta tags by their identifier (name or property)
    const newMetaMap = new Map();
    newMetas.forEach((meta) => {
      const identifier =
        meta.getAttribute("name") || meta.getAttribute("property");
      if (identifier) {
        newMetaMap.set(identifier, meta);
      }
    });

    // Remove or update existing metas
    currentMetas.forEach((meta) => {
      const name = meta.getAttribute("name");
      const property = meta.getAttribute("property");
      const identifier = name || property;
      const charset = meta.getAttribute("charset");

      // Keep charset and viewport (these are in the shell and should never be touched)
      if (charset || identifier === "viewport") {
        // Also remove from newMetaMap to prevent duplication
        if (identifier) {
          newMetaMap.delete(identifier);
        }
        return;
      }

      // Keep internal lara-ink meta tags
      if (identifier && identifier.startsWith("lara-ink-")) {
        return;
      }

      // If this meta exists in new document, update it
      if (identifier && newMetaMap.has(identifier)) {
        const newMeta = newMetaMap.get(identifier);
        const newContent = newMeta.getAttribute("content");
        const currentContent = meta.getAttribute("content");

        // Only update if content changed
        if (newContent !== currentContent) {
          meta.setAttribute("content", newContent);
        }

        // Mark as processed
        newMetaMap.delete(identifier);
      } else {
        // Meta doesn't exist in new document, remove it
        meta.remove();
      }
    });

    // Add remaining new metas that weren't in the current document
    newMetaMap.forEach((meta) => {
      const clone = meta.cloneNode(true);
      document.head.appendChild(clone);
    });

    // Update canonical link
    const currentCanonical = document.querySelector(
      'head link[rel="canonical"]'
    );
    const newCanonical = newDoc.querySelector('head link[rel="canonical"]');

    if (newCanonical) {
      const newHref = newCanonical.getAttribute("href");

      if (currentCanonical) {
        // Update existing canonical
        const currentHref = currentCanonical.getAttribute("href");
        if (newHref !== currentHref) {
          currentCanonical.setAttribute("href", newHref);
        }
      } else {
        // Add new canonical
        const clone = newCanonical.cloneNode(true);
        document.head.appendChild(clone);
      }
    } else if (currentCanonical) {
      // Remove canonical if not in new document
      currentCanonical.remove();
    }

    // Update structured data (JSON-LD)
    const currentStructuredData = document.querySelectorAll(
      'head script[type="application/ld+json"]'
    );
    const newStructuredData = newDoc.querySelectorAll(
      'head script[type="application/ld+json"]'
    );

    // Remove all current structured data
    currentStructuredData.forEach((script) => {
      script.remove();
    });

    // Add new structured data
    newStructuredData.forEach((script) => {
      const clone = script.cloneNode(true);
      document.head.appendChild(clone);
    });
  }

  function shouldHandleLink(link) {
    if (!link) return false;

    const href = link.getAttribute("href");

    if (!href || href.startsWith("#") || href.startsWith("javascript:")) {
      return false;
    }

    if (link.target && link.target !== "_self") return false;
    if (link.hasAttribute("download")) return false;
    if (link.getAttribute("data-no-ajax") !== null) return false;

    const url = new URL(link.href, window.location.origin);

    if (url.origin !== window.location.origin) {
      return false;
    }

    const normalizedPath = normalizePath(url.pathname);

    if (normalizedPath === "/") {
      return true;
    }

    return Object.prototype.hasOwnProperty.call(
      window.lara_ink.routes || {},
      normalizedPath
    );
  }

  function normalizePath(pathname) {
    if (!pathname || pathname === "/") {
      return "/";
    }

    let path = pathname.startsWith("/") ? pathname : `/${pathname}`;

    // Remove trailing slash (except for root)
    if (path.length > 1 && path.endsWith("/")) {
      path = path.slice(0, -1);
    }

    return path;
  }

  function removeDuplicateScripts(doc) {
    const patterns = [
      /\/build\/app\.js(?:\?.*)?$/,
      /\/build\/lara-ink-lang\.js(?:\?.*)?$/,
      /\/build\/lara-ink-spa\.js(?:\?.*)?$/,
      /\/build\/vendor\/.*\.js(?:\?.*)?$/,
    ];

    doc.querySelectorAll("script[src]").forEach((script) => {
      const src = script.getAttribute("src") || "";

      if (patterns.some((pattern) => pattern.test(src))) {
        script.remove();
      }
    });
  }

  // Execute scripts in the new content
  function executeScripts(container) {
    const scripts = container.querySelectorAll("script");
    scripts.forEach((oldScript) => {
      const newScript = document.createElement("script");

      Array.from(oldScript.attributes).forEach((attr) => {
        newScript.setAttribute(attr.name, attr.value);
      });

      if (oldScript.src) {
        newScript.src = oldScript.src;
      } else {
        newScript.textContent = oldScript.textContent;
      }

      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
  }

  // Get inline error template for fallback
  function getInlineErrorTemplate(code) {
    const errorMessages = {
      400: {
        title: "Bad Request",
        message: "The request could not be understood by the server.",
      },
      401: {
        title: "Unauthorized",
        message: "You need to be authenticated to access this resource.",
      },
      403: {
        title: "Access Denied",
        message: "You don't have permission to access this resource.",
      },
      404: {
        title: "Page Not Found",
        message: "The page you're looking for doesn't exist.",
      },
      500: {
        title: "Internal Server Error",
        message: "Something went wrong on our end.",
      },
      503: {
        title: "Service Unavailable",
        message: "The service is temporarily unavailable.",
      },
    };

    const error = errorMessages[code] || {
      title: "Error",
      message: "An unexpected error occurred.",
    };

    return `
            <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                <div style="background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); padding: 60px 40px; text-align: center; max-width: 600px; width: 100%;">
                    <div style="font-size: 72px; font-weight: 800; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 16px; line-height: 1;">${code}</div>
                    <h1 style="font-size: 32px; font-weight: 700; color: #2d3748; margin-bottom: 12px;">${error.title}</h1>
                    <p style="font-size: 16px; color: #718096; margin-bottom: 40px; line-height: 1.6;">${error.message}</p>
                    <a href="/" style="display: inline-block; padding: 14px 28px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 10px; font-size: 15px; font-weight: 600;">Go Home</a>
                </div>
            </div>
        `;
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAjaxNavigation);
  } else {
    initAjaxNavigation();
  }
})();
