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
 * Configuration (supports new and legacy keys):
 * - JS auto semicolon:  minify.auto_semicolon.js  (fallback minify.insert_semicolon.js)
 * - JS obfuscation:      minify.obfuscate_js       (fallback minify.obfuscate)
 * - Skip LD+JSON:        minify.skip_ld_json
 */
class MinifyJavaScriptMiddleware extends Minifier
{
    /**
     * Whether to allow automatic insertion of semicolons
     *
     * @var bool
     */
    protected static bool $allowInsertSemicolon = false;

    /**
     * Apply JavaScript minification
     *
     * @return string Minified HTML content
     */
    protected function apply(): string
    {
        // mark that JS minification ran (used by HTML minifier to decide pipeline)
        static::$minifyJavascriptHasBeenUsed = true;

        // Backward-compatible configuration resolution:
        // prefer new nested key, fallback to old flat key, then default.
        static::$allowInsertSemicolon = (bool) $this->getConfig(
            'auto_semicolon.js',         // new
            'insert_semicolon.js',       // old
            false                        // default
        );

        $obfuscate = (bool) $this->getConfig(
            'obfuscate_js',              // new
            'obfuscate',                 // old
            false                        // default
        );

        $skipLdJson = (bool) $this->getConfig(
            'skip_ld_json',              // same key in both but keep getConfig for consistency
            'skip_ld_json',
            true
        );

        $javascript = new JavaScriptProcessor();

        foreach ($this->getByTag('script') as $el) {
            // Skip LD+JSON scripts if configured
            if ($skipLdJson && $this->isLdJsonScript($el)) {
                continue;
            }

            // Minify JS (uses processor behavior; keep compat)
            $value = $javascript->replace($el->nodeValue, static::$allowInsertSemicolon);

            // Obfuscate JS if enabled
            if ($obfuscate) {
                $value = $javascript->obfuscate($value);
            }

            // Replace node content safely
            $el->nodeValue = '';
            $el->appendChild(static::$dom->createTextNode($value));
        }

        return static::$dom->saveHtml();
    }

    /**
     * Check if the <script> element is an LD+JSON script
     *
     * @param \DOMElement $el The <script> element
     * @return bool True if the element is type="application/ld+json"
     */
    protected function isLdJsonScript(\DOMElement $el): bool
    {
        return $el->hasAttribute('type') &&
            strtolower($el->getAttribute('type')) === 'application/ld+json';
    }
}
