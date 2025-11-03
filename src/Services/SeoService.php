<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

final class SeoService
{
    /**
     * Generate SEO meta tags HTML from SEO configuration
     * 
     * @param array<string, mixed>|null $seoConfig
     * @param string|null $fallbackTitle
     * @return string
     */
    public function generateMetaTags(?array $seoConfig, ?string $fallbackTitle = null): string
    {
        if ($seoConfig === null || empty($seoConfig)) {
            return '';
        }

        $tags = [];

        // Title tag (handled separately in layout)
        // Description
        if (!empty($seoConfig['description'])) {
            $tags[] = $this->createMetaTag('name', 'description', $seoConfig['description']);
        }

        // Keywords
        if (!empty($seoConfig['keywords'])) {
            $tags[] = $this->createMetaTag('name', 'keywords', $seoConfig['keywords']);
        }

        // Robots
        if (!empty($seoConfig['robots'])) {
            $tags[] = $this->createMetaTag('name', 'robots', $seoConfig['robots']);
        }

        // Canonical URL
        if (!empty($seoConfig['canonical'])) {
            $tags[] = sprintf('<link rel="canonical" href="%s">', htmlspecialchars($seoConfig['canonical'], ENT_QUOTES, 'UTF-8'));
        }

        // Open Graph tags
        if (!empty($seoConfig['og']) && is_array($seoConfig['og'])) {
            foreach ($seoConfig['og'] as $property => $content) {
                if (!empty($content)) {
                    $tags[] = $this->createMetaTag('property', 'og:' . $property, $content);
                }
            }
        } else {
            // Default Open Graph tags from main SEO config
            if (!empty($seoConfig['title'])) {
                $tags[] = $this->createMetaTag('property', 'og:title', $seoConfig['title']);
            }
            if (!empty($seoConfig['description'])) {
                $tags[] = $this->createMetaTag('property', 'og:description', $seoConfig['description']);
            }
            if (!empty($seoConfig['image'])) {
                $tags[] = $this->createMetaTag('property', 'og:image', $seoConfig['image']);
            }
        }

        // Twitter Card tags
        if (!empty($seoConfig['twitter']) && is_array($seoConfig['twitter'])) {
            foreach ($seoConfig['twitter'] as $name => $content) {
                if (!empty($content)) {
                    $tags[] = $this->createMetaTag('name', 'twitter:' . $name, $content);
                }
            }
        } else {
            // Default Twitter Card tags from main SEO config
            $tags[] = $this->createMetaTag('name', 'twitter:card', 'summary_large_image');
            if (!empty($seoConfig['title'])) {
                $tags[] = $this->createMetaTag('name', 'twitter:title', $seoConfig['title']);
            }
            if (!empty($seoConfig['description'])) {
                $tags[] = $this->createMetaTag('name', 'twitter:description', $seoConfig['description']);
            }
            if (!empty($seoConfig['image'])) {
                $tags[] = $this->createMetaTag('name', 'twitter:image', $seoConfig['image']);
            }
        }

        // Additional custom meta tags
        if (!empty($seoConfig['meta']) && is_array($seoConfig['meta'])) {
            foreach ($seoConfig['meta'] as $name => $content) {
                if (!empty($content)) {
                    $tags[] = $this->createMetaTag('name', $name, $content);
                }
            }
        }

        return implode("\n    ", $tags);
    }

    /**
     * Create a meta tag HTML string
     * 
     * @param string $type 'name' or 'property'
     * @param string $identifier
     * @param string $content
     * @return string
     */
    private function createMetaTag(string $type, string $identifier, string $content): string
    {
        $identifier = htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8');
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        return sprintf('<meta %s="%s" content="%s">', $type, $identifier, $content);
    }

    /**
     * Generate JSON-LD structured data for SEO
     * 
     * @param array<string, mixed>|null $seoConfig
     * @param string|null $url
     * @return string
     */
    public function generateStructuredData(?array $seoConfig, ?string $url = null): string
    {
        if ($seoConfig === null || empty($seoConfig)) {
            return '';
        }

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
        ];

        if (!empty($seoConfig['title'])) {
            $structuredData['name'] = $seoConfig['title'];
        }

        if (!empty($seoConfig['description'])) {
            $structuredData['description'] = $seoConfig['description'];
        }

        if (!empty($url)) {
            $structuredData['url'] = $url;
        }

        if (!empty($seoConfig['image'])) {
            $structuredData['image'] = $seoConfig['image'];
        }

        $json = json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($json === false) {
            return '';
        }

        return sprintf('<script type="application/ld+json">%s</script>', $json);
    }

    /**
     * Get SEO title with fallback
     * 
     * @param array<string, mixed>|null $seoConfig
     * @param string|null $fallbackTitle
     * @return string
     */
    public function getTitle(?array $seoConfig, ?string $fallbackTitle = null): string
    {
        if (!empty($seoConfig['title'])) {
            return $seoConfig['title'];
        }

        return $fallbackTitle ?? config('app.name', 'LaraInk');
    }

    /**
     * Generate SEO data for SPA pages (JSON format)
     * 
     * @param array<string, mixed>|null $seoConfig
     * @return array<string, mixed>
     */
    public function generateSpaMetadata(?array $seoConfig): array
    {
        if ($seoConfig === null || empty($seoConfig)) {
            return [];
        }

        $metadata = [];

        if (!empty($seoConfig['title'])) {
            $metadata['title'] = $seoConfig['title'];
        }

        if (!empty($seoConfig['description'])) {
            $metadata['description'] = $seoConfig['description'];
        }

        if (!empty($seoConfig['keywords'])) {
            $metadata['keywords'] = $seoConfig['keywords'];
        }

        if (!empty($seoConfig['robots'])) {
            $metadata['robots'] = $seoConfig['robots'];
        }

        if (!empty($seoConfig['canonical'])) {
            $metadata['canonical'] = $seoConfig['canonical'];
        }

        if (!empty($seoConfig['image'])) {
            $metadata['image'] = $seoConfig['image'];
        }

        if (!empty($seoConfig['og']) && is_array($seoConfig['og'])) {
            $metadata['og'] = $seoConfig['og'];
        }

        if (!empty($seoConfig['twitter']) && is_array($seoConfig['twitter'])) {
            $metadata['twitter'] = $seoConfig['twitter'];
        }

        if (!empty($seoConfig['meta']) && is_array($seoConfig['meta'])) {
            $metadata['meta'] = $seoConfig['meta'];
        }

        return $metadata;
    }
}
