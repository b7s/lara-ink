<?php

declare(strict_types=1);

use B7s\LaraInk\Services\SeoService;

beforeEach(function () {
    $this->seoService = new SeoService();
});

describe('SeoService', function () {
    it('generates basic meta tags', function () {
        $seoConfig = [
            'title' => 'Test Page',
            'description' => 'This is a test page',
            'keywords' => 'test, page, seo',
            'robots' => 'index, follow',
        ];

        $result = $this->seoService->generateMetaTags($seoConfig);

        expect($result)
            ->toContain('name="description" content="This is a test page"')
            ->toContain('name="keywords" content="test, page, seo"')
            ->toContain('name="robots" content="index, follow"');
    });

    it('generates Open Graph tags', function () {
        $seoConfig = [
            'title' => 'Test Page',
            'description' => 'This is a test page',
            'image' => 'https://example.com/image.jpg',
        ];

        $result = $this->seoService->generateMetaTags($seoConfig);

        expect($result)
            ->toContain('property="og:title" content="Test Page"')
            ->toContain('property="og:description" content="This is a test page"')
            ->toContain('property="og:image" content="https://example.com/image.jpg"');
    });

    it('generates Twitter Card tags', function () {
        $seoConfig = [
            'title' => 'Test Page',
            'description' => 'This is a test page',
            'image' => 'https://example.com/image.jpg',
        ];

        $result = $this->seoService->generateMetaTags($seoConfig);

        expect($result)
            ->toContain('name="twitter:card" content="summary_large_image"')
            ->toContain('name="twitter:title" content="Test Page"')
            ->toContain('name="twitter:description" content="This is a test page"')
            ->toContain('name="twitter:image" content="https://example.com/image.jpg"');
    });

    it('generates canonical link', function () {
        $seoConfig = [
            'canonical' => 'https://example.com/page',
        ];

        $result = $this->seoService->generateMetaTags($seoConfig);

        expect($result)->toContain('<link rel="canonical" href="https://example.com/page">');
    });

    it('generates custom meta tags', function () {
        $seoConfig = [
            'meta' => [
                'author' => 'John Doe',
                'copyright' => '2024',
            ],
        ];

        $result = $this->seoService->generateMetaTags($seoConfig);

        expect($result)
            ->toContain('name="author" content="John Doe"')
            ->toContain('name="copyright" content="2024"');
    });

    it('generates custom Open Graph tags', function () {
        $seoConfig = [
            'og' => [
                'type' => 'article',
                'site_name' => 'My Site',
            ],
        ];

        $result = $this->seoService->generateMetaTags($seoConfig);

        expect($result)
            ->toContain('property="og:type" content="article"')
            ->toContain('property="og:site_name" content="My Site"');
    });

    it('generates custom Twitter Card tags', function () {
        $seoConfig = [
            'twitter' => [
                'card' => 'summary',
                'site' => '@mysite',
            ],
        ];

        $result = $this->seoService->generateMetaTags($seoConfig);

        expect($result)
            ->toContain('name="twitter:card" content="summary"')
            ->toContain('name="twitter:site" content="@mysite"');
    });

    it('returns empty string for null config', function () {
        $result = $this->seoService->generateMetaTags(null);

        expect($result)->toBe('');
    });

    it('returns empty string for empty config', function () {
        $result = $this->seoService->generateMetaTags([]);

        expect($result)->toBe('');
    });

    it('escapes HTML in meta tags', function () {
        $seoConfig = [
            'title' => 'Test <script>alert("xss")</script>',
            'description' => 'Description with "quotes" and <tags>',
        ];

        $result = $this->seoService->generateMetaTags($seoConfig);

        expect($result)
            ->not->toContain('<script>')
            ->toContain('&lt;script&gt;')
            ->toContain('&quot;');
    });

    it('generates structured data', function () {
        $seoConfig = [
            'title' => 'Test Page',
            'description' => 'This is a test page',
            'image' => 'https://example.com/image.jpg',
        ];

        $result = $this->seoService->generateStructuredData($seoConfig, 'https://example.com/page');

        expect($result)
            ->toContain('<script type="application/ld+json">')
            ->toContain('"@context": "https://schema.org"')
            ->toContain('"@type": "WebPage"')
            ->toContain('"name": "Test Page"')
            ->toContain('"description": "This is a test page"')
            ->toContain('"url": "https://example.com/page"')
            ->toContain('"image": "https://example.com/image.jpg"');
    });

    it('returns empty string for structured data with null config', function () {
        $result = $this->seoService->generateStructuredData(null);

        expect($result)->toBe('');
    });

    it('gets title from SEO config', function () {
        $seoConfig = [
            'title' => 'SEO Title',
        ];

        $result = $this->seoService->getTitle($seoConfig);

        expect($result)->toBe('SEO Title');
    });

    it('uses fallback title when SEO title is not set', function () {
        $seoConfig = [];

        $result = $this->seoService->getTitle($seoConfig, 'Fallback Title');

        expect($result)->toBe('Fallback Title');
    });

    it('uses default title when no title is provided', function () {
        config(['app.name' => 'My App']);

        $result = $this->seoService->getTitle(null);

        expect($result)->toBe('My App');
    });

    it('generates SPA metadata', function () {
        $seoConfig = [
            'title' => 'Test Page',
            'description' => 'This is a test page',
            'keywords' => 'test, page',
            'robots' => 'index, follow',
            'canonical' => 'https://example.com/page',
            'image' => 'https://example.com/image.jpg',
            'og' => [
                'type' => 'article',
            ],
            'twitter' => [
                'card' => 'summary',
            ],
            'meta' => [
                'author' => 'John Doe',
            ],
        ];

        $result = $this->seoService->generateSpaMetadata($seoConfig);

        expect($result)
            ->toHaveKey('title', 'Test Page')
            ->toHaveKey('description', 'This is a test page')
            ->toHaveKey('keywords', 'test, page')
            ->toHaveKey('robots', 'index, follow')
            ->toHaveKey('canonical', 'https://example.com/page')
            ->toHaveKey('image', 'https://example.com/image.jpg')
            ->toHaveKey('og')
            ->toHaveKey('twitter')
            ->toHaveKey('meta');
    });

    it('returns empty array for SPA metadata with null config', function () {
        $result = $this->seoService->generateSpaMetadata(null);

        expect($result)->toBe([]);
    });
});
