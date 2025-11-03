@php
    $vite = app(\B7s\LaraInk\Services\ViteService::class);
    $appName = config('app.name', ink_config('name', 'LaraInk'));
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $pageTitle ?? $title ?? $appName }}</title>

    @if(!empty($seoMetaTags))
        {!! $seoMetaTags !!}
    @endif

    {!! $pageMeta ?? '' !!}

    <link rel="stylesheet" href="{{ $vite->getAssetUrl('app.css') }}">

    @if(ink_config('styles.others'))
        @foreach(ink_config('styles.others') as $stylePath)
            <link rel="stylesheet" href="/{{ ltrim($stylePath, '/') }}">
        @endforeach
    @endif

    {!! $pageStyles ?? '' !!}

    @if(!empty($pageCss))
        <style>
            {{ $pageCss }}
        </style>
    @endif

    @if(ink_config('scripts.beforeAlpine'))
        @foreach(ink_config('scripts.beforeAlpine') as $scriptUrl)
            <script defer src="{{ ink_cached_script($scriptUrl) }}"></script>
        @endforeach
    @endif

    @if($alpineUrl = ink_config('scripts.alpinejs'))
        <script defer src="{{ ink_cached_script($alpineUrl) }}"></script>
    @endif

    @if(!empty($seoStructuredData))
        {!! $seoStructuredData !!}
    @endif

    {!! $vite->activeHotReload() !!}
</head>

<body id="lara-ink-root">
    @isset($userLayout)
        {!! $userLayout !!}
    @else
        <div x-data="pageData()" x-init="init()">
            @yield('page')
        </div>
    @endisset

    <script src="{{ $vite->getAssetUrl('app.js') }}"></script>
    <script src="/build/lara-ink-lang.js"></script>

    @if(ink_config('scripts.others'))
        @foreach(ink_config('scripts.others') as $scriptPath)
            <script src="/{{ ltrim($scriptPath, '/') }}"></script>
        @endforeach
    @endif

    <script>
        window.__LARA_INK_CONFIG__ = {
            api_base_url: '{{ ink_config("api_base_url") ?: config('app.url') }}{{ ink_config('auth.route.api_prefix', '/api/ink') }}',
            login_route: '{{ ink_config('auth.route.login', '/login') }}',
            unauthorized_route: '{{ ink_config('auth.route.unauthorized', '/unauthorized') }}',
            spa_mode: {{ isset($userLayout) ? 'false' : 'true' }}
        };
    </script>

    <script src="/build/lara-ink-spa.js"></script>

    <script>
        window.lara_ink = window.lara_ink || {};
        Object.assign(window.lara_ink, {
            api_base_url: window.__LARA_INK_CONFIG__.api_base_url,
            token: localStorage.getItem('lara_ink_token') || null,

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
                    window.location.href = window.__LARA_INK_CONFIG__.login_route;
                    return response;
                }
                
                if (response.status === 403) {
                    window.location.href = window.__LARA_INK_CONFIG__.unauthorized_route;
                    return response;
                }

                return response;
            },

            async is_authenticated() {
                if (!this.token) return false;

                try {
                    const response = await this.newReq('/is-authenticated', {
                        method: 'GET',
                    });

                    return response.ok;
                } catch (error) {
                    console.error('Auth check failed:', error);
                    return false;
                }
            },

            async logout() {
                if (this.token) {
                    await this.newReq('/logout', { method: 'POST' });
                }

                this.token = null;
                localStorage.removeItem('lara_ink_token');
            }
        });
    </script>

    @if(!empty($pageJs))
        <script>
            {!! $pageJs !!}
        </script>
    @endif
</body>

</html>