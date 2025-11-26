<?php

namespace Fahlisaputra\Minify\Core;

use Closure;
use DOMDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Abstract Middleware for HTML minification
 *
 * Handles minifying Blade views, HTML output, and optionally assets.
 * Supports both legacy and new configuration keys.
 *
 * Configuration mapping:
 *  - New: 'minify_blade', 'ignore_routes', 'directive_replacement'
 *  - Old: 'enabled', 'ignore', 'directives', 'keep_directives'
 *
 * @package Fahlisaputra\Minify\Core
 */
abstract class Minifier
{
    /**
     * @var DOMDocument|null Loaded DOMDocument instance for processing
     */
    protected static ?DOMDocument $dom = null;

    /**
     * @var bool Flag to track if CSS minification has been applied
     */
    protected static bool $minifyCssHasBeenUsed = false;

    /**
     * @var bool Flag to track if JS minification has been applied
     */
    protected static bool $minifyJavascriptHasBeenUsed = false;

    /**
     * @var bool|null Caches whether minification is enabled
     */
    protected static ?bool $isEnable = null;

    /**
     * @var array|null List of routes to ignore minification
     */
    protected static ?array $ignore = null;

    /**
     * Regex to quickly validate if content is valid HTML for minification
     */
    protected const REGEX_VALID_HTML = "/<html[^>]*>.*<head[^>]*>.*<\/head[^>]*>.*<body[^>]*>.*<\/body[^>]*>.*<\/html[^>]*>/is";

    /**
     * Child classes implement the minification logic
     *
     * @return string Minified HTML
     */
    abstract protected function apply(): string;

    /**
     * Middleware handler
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (!$this->shouldProcessMinify($request, $response)) {
            if (!(static::$dom instanceof DOMDocument)) {
                return $response;
            }
        }

        $html = $response->getContent();
        $html = $this->replaceDirectives($html);
        $this->loadDom($html);

        return $response->setContent($this->apply());
    }

    /**
     * Replace directives in HTML with configured replacements
     * Preserves directives listed in "keep" config
     *
     * @param string $html
     * @return string
     */
    protected function replaceDirectives(string $html): string
    {
        $enabled = $this->getConfig('directive_replacement.enabled', 'enable_directive_replacement', false);
        if (!$enabled) return $html;

        $body = explode('<body', $html);

        // Preserve directives
        $keepKeys = $this->getConfig('directive_replacement.keep', 'keep_directives', []);
        $keepMap = [];
        foreach ($keepKeys as $key) {
            $keepMap[$key] = '____' . uniqid() . '____';
            $body[1] = str_replace($key, $keepMap[$key], $body[1]);
        }

        // Replace configured directives
        $directives = $this->getConfig('directive_replacement.replaces', 'directives', []);
        foreach ($directives as $search => $replace) {
            $body[1] = str_replace($search, $replace, $body[1]);
        }

        // Restore preserved directives
        foreach ($keepMap as $replace => $search) {
            $body[1] = str_replace($search, $replace, $body[1]);
        }

        return $body[0] . '<body' . $body[1];
    }

    /**
     * Determine if a response should be minified
     *
     * @param mixed $request
     * @param mixed $response
     * @return bool
     */
    protected function shouldProcessMinify(mixed $request, mixed $response): bool
    {
        if (!$this->isEnable()) return false;
        if ($response instanceof JsonResponse) return false;
        if ($response instanceof BinaryFileResponse) return false;
        if ($response instanceof StreamedResponse) return false;

        if ($response->original instanceof View) {
            $data = $response->original->getData();
            if (isset($data['ignore_minify']) && $data['ignore_minify'] === true) return false;
        }

        foreach ($this->ignore() as $route) {
            if ($request->is($route)) return false;
        }

        $content = $response->getContent();
        if (empty($content) || !is_string($content) || $this->isEmpty($content)) return false;

        return $this->validHtml($content);
    }

    /**
     * Check if the content is empty (only whitespace)
     *
     * @param string $value
     * @return bool
     */
    protected function isEmpty(string $value): bool
    {
        return (bool) preg_match("/^\s*$/", $value);
    }

    /**
     * Validate if content is HTML
     *
     * @param string $value
     * @return bool
     */
    protected function validHtml(string $value): bool
    {
        return (bool) preg_match(self::REGEX_VALID_HTML, $value);
    }

    /**
     * Determine if minification is enabled
     *
     * @return bool
     */
    protected function isEnable(): bool
    {
        if (is_null(static::$isEnable)) {
            static::$isEnable = $this->getConfig('minify_blade', 'enabled', true);
        }
        return static::$isEnable;
    }

    /**
     * Return an array of routes to ignore minification
     *
     * @return array
     */
    protected function ignore(): array
    {
        if (is_null(static::$ignore)) {
            static::$ignore = $this->getConfig('ignore_routes', 'ignore', []);
        }
        return static::$ignore;
    }

    /**
     * Get configuration value with fallback: new -> old -> default
     * This ensures compatibility with both new and legacy config structures
     *
     * @param string $newKey Config key in the new structure
     * @param string $oldKey Config key in the old structure
     * @param mixed|null $default Default value if both keys missing
     * @return mixed
     */
    protected function getConfig(string $newKey, string $oldKey, mixed $default = null): mixed
    {
        $val = config("minify.$newKey", null);
        if ($val !== null) return $val;

        return config("minify.$oldKey", $default);
    }

    /**
     * Match HTML tags and return matches
     *
     * @param string $value
     * @param string $tags
     * @return array|null
     */
    protected function matchHtmlTag(string $value, string $tags): ?array
    {
        if (!preg_match_all('/<' . $tags . "[^>]*>(.*?)<\/" . $tags . '[^>]*>/is', $value, $matches)) {
            return null;
        }
        return $matches;
    }

    /**
     * Load HTML content into DOMDocument
     *
     * @param string $html
     * @param bool $force Force reload even if DOM already loaded
     * @return void
     */
    protected function loadDom(string $html, bool $force = false): void
    {
        if (static::$dom instanceof DOMDocument && !$force) return;

        static::$dom = new DOMDocument();
        @static::$dom->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_SCHEMA_CREATE);
    }

    /**
     * Get DOM elements by tag name excluding ignored elements
     *
     * @param string $tags
     * @return array
     */
    protected function getByTag(string $tags): array
    {
        $result = [];
        foreach (static::$dom->getElementsByTagName($tags) as $el) {
            if ($this->isEmpty($el->nodeValue)) continue;
            if ($el->hasAttribute('ignore--minify')) continue;
            $result[] = $el;
        }
        return $result;
    }

    /**
     * Get DOM elements by tag name only if they have ignored the attribute
     *
     * @param string $tags
     * @return array
     */
    protected function getByTagOnlyIgnored(string $tags): array
    {
        $result = [];
        foreach (static::$dom->getElementsByTagName($tags) as $el) {
            if ($this->isEmpty($el->nodeValue)) continue;
            if (!$el->hasAttribute('ignore--minify')) continue;
            $result[] = $el;
        }
        return $result;
    }
}
