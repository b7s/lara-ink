<!DOCTYPE html>
<html lang="__APP_LOCALE__">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>__APP_TITLE__</title>
    <link rel="stylesheet" href="build/app.css">
    __STYLE_TAGS__
    <style>
        body.lara-ink-system {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        body.lara-ink-system #lara-ink-root {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        body.lara-ink-system .small {
            font-size: 0.875rem;
        }

        body.lara-ink-system .lara-ink-message {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 3rem 2rem;
            text-align: center;
            max-width: 500px;
            width: 100%;
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        body.lara-ink-system .lara-ink-message-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.lara-ink-system .lara-ink-error {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        body.lara-ink-system .lara-ink-welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        body.lara-ink-system .lara-ink-message-code {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }

        body.lara-ink-system .lara-ink-message-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        body.lara-ink-system .lara-ink-message-text {
            font-size: 1.125rem;
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.75;
        }

        body.lara-ink-system .lara-ink-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body.lara-ink-system .lara-ink-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        body.lara-ink-system .lara-ink-message-footer {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        body.lara-ink-system .lara-ink-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        body.lara-ink-system .lara-ink-link:hover {
            color: #764ba2;
        }

        body.lara-ink-system .lara-ink-separator {
            color: #d1d5db;
        }

        body.lara-ink-system .lara-ink-spinner {
            width: 48px;
            height: 48px;
            margin: 0 auto 1.5rem;
            border: 4px solid #e5e7eb;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 640px) {
            body.lara-ink-system .lara-ink-message {
                padding: 2rem 1.5rem;
            }

            body.lara-ink-system .lara-ink-message-code {
                font-size: 3rem;
            }

            body.lara-ink-system .lara-ink-message-title {
                font-size: 1.5rem;
            }

            body.lara-ink-system .lara-ink-message-text {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div id="lara-ink-root" x-data="laraInkApp()"></div>

    <script>
        window.lara_ink = {
            routes: __ROUTES_JSON__,
            cache_manifest: __CACHE_MANIFEST__,
            api_base_url: '__API_BASE_URL__',
            token: localStorage.getItem('lara_ink_token') || null,
            request_queue: [],
            async newReq(endpoint, options = {}) {
                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
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
                    window.location.href = '__LOGIN_ROUTE__';
                }

                return response;
            },

            async is_authenticated() {
                if (!this.token) return false;

                const lastCheck = localStorage.getItem('lara_ink_last_auth_check');
                const now = Date.now();

                if (lastCheck && (now - parseInt(lastCheck)) < 60000) {
                    return true;
                }

                try {
                    const response = await this.newReq('/is-authenticated', {
                        method: 'GET'
                    });

                    if (response.status === 401) {
                        this.logout();
                        return false;
                    }

                    if (response.ok) {
                        const data = await response.json();
                        if (data.token) {
                            this.token = data.token;
                            localStorage.setItem('lara_ink_token', data.token);
                        }
                        localStorage.setItem('lara_ink_last_auth_check', now.toString());
                        return true;
                    }
                } catch (error) {
                    console.error('Auth check failed:', error);
                }

                return false;
            },

            async logout() {
                if (this.token) {
                    await this.newReq('/logout', { method: 'POST' });
                }

                this.token = null;
                localStorage.removeItem('lara_ink_token');
                localStorage.removeItem('lara_ink_last_auth_check');
            }
        };

        function laraInkApp() {
            return {
                loading: false,
                cache: new Map(),

                setSystemMode(enabled) {
                    document.body.classList.toggle('lara-ink-system', enabled);
                },

                executeScripts(container) {
                    const scripts = container.querySelectorAll('script');
                    scripts.forEach((inlineScript) => {
                        const newScript = document.createElement('script');

                        // Copy attributes
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
                    window.addEventListener('popstate', () => this.handleRoute());
                    this.interceptLinks();
                    this.cleanOldCache();
                },

                async handleRoute() {
                    const path = window.location.pathname;
                    
                    // Show user's index.php for home, or welcome if not exists
                    if (path === '/' || path === '/index.html') {
                        const cachedPage = this.getCachedPage('/');
                        if (cachedPage) {
                            this.renderPage(cachedPage);
                            return;
                        }
                        
                        try {
                            const response = await fetch('/pages/index.html');
                            if (response.ok) {
                                const html = await response.text();
                                this.renderPage(html);
                                this.cachePage('/', html);
                                return;
                            }
                        } catch (error) {
                            // No index page found, show welcome
                        }
                        
                        this.setSystemMode(true);
                        this.$el.innerHTML = this.getWelcomeTemplate();
                        return;
                    }
                    
                    await this.loadPage(path);
                },

                async loadPage(path) {
                    this.loading = true;

                    const cachedPage = this.getCachedPage(path);
                    if (cachedPage) {
                        this.renderPage(cachedPage);
                        this.loading = false;
                        return;
                    }

                    this.setSystemMode(true);
                    this.$el.innerHTML = this.getLoadingTemplate();

                    try {
                        const response = await fetch(`/pages${path}.html`);

                        if (!response.ok) {
                            throw new Error('Page not found');
                        }

                        const html = await response.text();
                        this.renderPage(html);
                        this.cachePage(path, html);
                    } catch (error) {
                        console.error('Failed to load page:', error);
                        this.setSystemMode(true);
                        this.$el.innerHTML = this.getErrorTemplate('404', 'Page Not Found', 'The page you are looking for does not exist.');
                    }

                    this.loading = false;
                },

                getErrorTemplate(code, title, message) {
                    return `
                        <div class="lara-ink-message">
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
                        <div class="lara-ink-message">
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
                        <div class="lara-ink-message">
                            <div class="lara-ink-spinner"></div>
                            <p class="lara-ink-message-text">Loading...</p>
                        </div>
                    `;
                },

                renderPage(html) {
                    this.setSystemMode(false);
                    this.$el.innerHTML = html;
                    this.executeScripts(this.$el);
                    Alpine.initTree(this.$el);
                },

                interceptLinks() {
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('a');

                        if (link && link.href && link.origin === window.location.origin) {
                            e.preventDefault();
                            const path = new URL(link.href).pathname;
                            window.history.pushState({}, '', link.href);
                            this.loadPage(path);
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

                    const expires = Date.now() + (ttl * 1000);
                    this.cache.set(path, { html, expires });
                },

                cleanOldCache() {
                    const now = Date.now();

                    for (const [path, data] of this.cache.entries()) {
                        if (data.expires && data.expires < now) {
                            this.cache.delete(path);
                        }
                    }
                }
            };
        }
    </script>

    __SCRIPT_TAGS__
    <script src="build/app.js"></script>
    <script src="build/lara-ink-lang.js"></script>
</body>

</html>