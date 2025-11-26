<?php

namespace Fahlisaputra\Minify\Middleware;

use Fahlisaputra\Minify\Core\JavaScriptProcessor;
use Fahlisaputra\Minify\Core\Minifier;

/**
 * Middleware to minify inline JavaScript in HTML responses
 *
 * Extends the base Minifier and handles:
 * - Minification of <script> content
 * - Optional obfuscation of JavaScript
 * - Optional insertion of semicolons at the end of scripts
 * - Skipping LD+JSON scripts from minification (for SEO/structured data)
 *
 * Configuration:
 * - minify.insert_semicolon.js (bool): Add semicolon at the end of JS code
 * - minify.obfuscate (bool): Obfuscate JS code
 * - minify.skip_ld_json (bool): Skip <script type="application/ld+json"> tags
 *
 * Usage:
 * Add this middleware to your HTTP kernel or route middleware group
 * to automatically minify inline JavaScript in Blade or raw HTML responses.
 */
class MinifyJavaScriptMiddleware extends Minifier
{
    /**
     * Whether to allow automatic insertion of semicolons
     */
    protected static bool $allowInsertSemicolon;

    /**
     * Apply JavaScript minification
     *
     * @return string Minified HTML content
     */
    protected function apply(): string
    {
        static::$minifyJavascriptHasBeenUsed = true;
        static::$allowInsertSemicolon = (bool) config('minify.insert_semicolon.js', false);

        $javascript = new JavaScriptProcessor();
        $obfuscate = (bool) config('minify.obfuscate', false);
        $skipLdJson = (bool) config('minify.skip_ld_json', true);

        foreach ($this->getByTag('script') as $el) {
            // Skip LD+JSON scripts if configured
            if ($skipLdJson && $this->isLdJsonScript($el)) {
                continue;
            }

            // Minify JS
            $value = $javascript->replace($el->nodeValue, static::$allowInsertSemicolon);

            // Obfuscate JS if enabled
            if ($obfuscate) {
                $value = $javascript->obfuscate($value);
            }

            // Replace node content
            $el->nodeValue = '';
            $el->appendChild(static::$dom->createTextNode($value));
        }

        return static::$dom->saveHtml();
    }

    /**
     * Check if the <script> element is an LD+JSON script
     *
     * @param \DOMElement $el The <script> element
     *
     * @return bool True if the element is type="application/ld+json"
     */
    protected function isLdJsonScript(\DOMElement $el): bool
    {
        return $el->hasAttribute('type') &&
            strtolower($el->getAttribute('type')) === 'application/ld+json';
    }
}
