<?php

declare(strict_types=1);

namespace B7s\LaraInk\Support;

use B7s\LaraInk\DTOs\PageConfig;
use B7s\LaraInk\DTOs\SeoConfig;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateInterval;
use Illuminate\Support\Carbon as BaseCarbon;

final class LaraInk
{
    /**
     * Cache TTL in seconds
     * @var int|bool|null
     */
    private int|bool|null $cache = null;
    private ?string $layout = null;
    private ?string $title = null;
    private bool $auth = false;
    private ?string $middleware = null;
    private ?SeoConfig $seo = null;

    /**
     * Enables or disables page caching.
     * 
     * @param null|bool|int|Carbon|CarbonInterface|DateInterval|BaseCarbon $time  Set to true to enable caching with default TTL, false to disable caching, or provide a specific TTL, or a Carbon instance for a specific time, or a DateInterval for a relative time.
     * @return LaraInk
     */
    public function cache(null|bool|int|Carbon|CarbonInterface|DateInterval|BaseCarbon $time): self
    {
        if (is_int($time)) {
            $this->cache = $time;
        }
        elseif (is_bool($time)) {
            if ($time && config('lara-ink.cache.enable', false)) {
                $this->cache = config('lara-ink.cache.ttl', 300);
            }
            else {
                $this->cache = null;
            }
        }
        elseif ($time instanceof Carbon || $time instanceof CarbonInterface) {
            $now = Carbon::now();
            $diff = $time->getTimestamp() - $now->getTimestamp();
            $this->cache = $diff > 0 ? $diff : 0;
        }
        elseif ($time instanceof DateInterval) {
            $now = Carbon::now();
            $future = (clone $now)->add($time);
            $diff = $future->getTimestamp() - $now->getTimestamp();
            $this->cache = $diff > 0 ? $diff : 0;
        }

        return $this;
    }

    /**
     * Sets the layout for the page.
     * 
     * @param string $layout  The layout name.
     * @return LaraInk
     */
    public function layout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Sets the title for the page.
     * 
     * @param string $title  The title of the page.
     * @return LaraInk
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Sets the authentication requirement for the page.
     * 
     * @param bool $auth  Whether the page requires authentication.
     * @return LaraInk
     */
    public function auth(bool $auth): self
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Sets the middleware for the page.
     * 
     * @param string $middleware  The middleware name.
     * @return LaraInk
     */
    public function middleware(string $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Sets the SEO metadata for the page.
     *
     * @param string|SeoConfig $titleOrConfig Either the page title or a SeoConfig instance
     * @param string|null $description The description of the page (required if first parameter is string)
     * @param string|null $keywords The keywords of the page (required if first parameter is string)
     * @param string|null $image The image of the page (required if first parameter is string)
     * @param string|null $canonical The canonical URL of the page
     * @param string $robots The robots meta tag content (default: 'index, follow')
     * @param array $meta Additional meta tags
     * @param array $og Open Graph meta tags
     * @param array $twitter Twitter Card meta tags
     * @return LaraInk
     */
    public function seo(
        string|SeoConfig $titleOrConfig,
        ?string $description = null,
        ?string $keywords = null,
        ?string $image = null,
        ?string $canonical = null,
        string $robots = 'index, follow',
        array $meta = [],
        array $og = [],
        array $twitter = []
    ): self {
        if ($titleOrConfig instanceof SeoConfig) {
            $this->seo = $titleOrConfig;
        } else {
            $this->seo = new SeoConfig(
                title: $titleOrConfig,
                description: $description,
                keywords: $keywords,
                image: $image,
                canonical: $canonical,
                robots: $robots,
                meta: $meta,
                og: $og,
                twitter: $twitter
            );
        }
        
        return $this;
    }

    /**
     * Builds the page configuration.
     * 
     * @return PageConfig  The page configuration.
     */
    public function build(): PageConfig
    {
        return new PageConfig(
            cache: $this->cache,
            layout: $this->layout,
            title: $this->title,
            auth: $this->auth,
            middleware: $this->middleware,
            seo: $this->seo?->toArray()
        );
    }
}
