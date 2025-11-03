<!DOCTYPE html>
<html lang="__APP_LOCALE__">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>__APP_TITLE__</title>

    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="robots" content="index, follow">

    <meta property="og:title" content="__APP_TITLE__">
    <meta property="og:description" content="">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="__APP_TITLE__">
    <meta name="twitter:description" content="">
    <meta name="twitter:image" content="">

    <link rel="stylesheet" href="__APP_CSS_URL__">
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 0.75rem 2rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body.lara-ink-system .lara-ink-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        body.lara-ink-system .lara-ink-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        body.lara-ink-system .lara-ink-button-secondary {
            background: #edf2f7;
            color: #4a5568;
            box-shadow: none;
        }

        body.lara-ink-system .lara-ink-button-secondary:hover {
            box-shadow: none;
            background: #e2e8f0;
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

        body.lara-ink-system .lara-ink-auth,
        body.lara-ink-system .lara-ink-error-page {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
        }

        body.lara-ink-system .lara-ink-auth-card,
        body.lara-ink-system .lara-ink-error-card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.18);
            padding: 2.5rem;
            animation: fadeInUp 0.45s ease-out;
        }

        body.lara-ink-system .lara-ink-error-card {
            max-width: 620px;
            text-align: center;
        }

        body.lara-ink-system .lara-ink-auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        body.lara-ink-system .lara-ink-auth-title {
            font-size: 1.85rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        body.lara-ink-system .lara-ink-auth-subtitle {
            font-size: 0.95rem;
            color: #6b7280;
        }

        body.lara-ink-system .lara-ink-auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        body.lara-ink-system .lara-ink-field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        body.lara-ink-system .lara-ink-field-label {
            font-size: 0.95rem;
            font-weight: 500;
            color: #374151;
        }

        body.lara-ink-system .lara-ink-input {
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
            background: #ffffff;
            color: #1f2937;
        }

        body.lara-ink-system .lara-ink-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        body.lara-ink-system .lara-ink-field.lara-ink-field-error .lara-ink-input {
            border-color: #f56565;
        }

        body.lara-ink-system .lara-ink-field-error {
            font-size: 0.82rem;
            color: #f56565;
        }

        body.lara-ink-system .lara-ink-checkbox-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #4b5563;
        }

        body.lara-ink-system .lara-ink-checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        body.lara-ink-system .lara-ink-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
            cursor: pointer;
        }

        body.lara-ink-system .lara-ink-alert {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.9rem;
        }

        body.lara-ink-system .lara-ink-alert-error {
            background-color: #fee2e2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
        }

        body.lara-ink-system .lara-ink-auth-footer {
            margin-top: 1.5rem;
            text-align: center;
        }

        body.lara-ink-system .lara-ink-auth-footer .lara-ink-link {
            font-size: 0.95rem;
        }

        body.lara-ink-system .lara-ink-auth-loader {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        body.lara-ink-system .lara-ink-auth-loader svg {
            animation: spin 1s linear infinite;
        }

        body.lara-ink-system .lara-ink-error-icon {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
            animation: bounce 2s ease-in-out infinite;
        }

        body.lara-ink-system .lara-ink-error-icon svg {
            width: 120px;
            height: 120px;
        }

        body.lara-ink-system .lara-ink-error-icon.lara-ink-error-404 svg {
            color: #ed8936;
        }

        body.lara-ink-system .lara-ink-error-icon.lara-ink-error-401 svg,
        body.lara-ink-system .lara-ink-error-icon.lara-ink-error-403 svg {
            color: #f56565;
        }

        body.lara-ink-system .lara-ink-error-icon.lara-ink-error-500 svg,
        body.lara-ink-system .lara-ink-error-icon.lara-ink-error-503 svg {
            color: #e53e3e;
        }

        body.lara-ink-system .lara-ink-error-code {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }

        body.lara-ink-system .lara-ink-error-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        body.lara-ink-system .lara-ink-error-message {
            font-size: 1.05rem;
            color: #6b7280;
            line-height: 1.7;
        }

        body.lara-ink-system .lara-ink-error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        body.lara-ink-system .lara-ink-error-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            color: #a0aec0;
            font-size: 0.9rem;
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

            body.lara-ink-system .lara-ink-auth-card,
            body.lara-ink-system .lara-ink-error-card {
                padding: 2.25rem 1.75rem;
            }

            body.lara-ink-system .lara-ink-error-code {
                font-size: 3.25rem;
            }

            body.lara-ink-system .lara-ink-error-title {
                font-size: 1.65rem;
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

    __VITE_HOT_CLIENT__
</head>

<body>
    <div id="lara-ink-root" x-data="laraInkApp()" x-init="init()"></div>

    <script>
        window.__LARA_INK_ROUTES__ = __ROUTES_JSON__;
        window.__LARA_INK_CACHE__ = __CACHE_MANIFEST__;
        window.__LARA_INK_API_URL__ = '__API_BASE_URL__';
        window.__LARA_INK_LOGIN_ROUTE__ = '__LOGIN_ROUTE__';
        window.__LARA_INK_PAGES_PATH__ = '__PAGES_PATH__';
    </script>

    <script src="__BUILD_PATH__/lara-ink-lang.js"></script>
    <script src="__BUILD_PATH__/lara-ink-spa.js"></script>

    __SCRIPT_TAGS__
    <script src="__APP_JS_URL__"></script>
</body>

</html>