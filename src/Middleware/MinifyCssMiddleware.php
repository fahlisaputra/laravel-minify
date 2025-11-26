<?php

namespace Fahlisaputra\Minify\Middleware;

use Fahlisaputra\Minify\Core\CssProcessor;
use Fahlisaputra\Minify\Core\Minifier;

/**
 * Middleware to minify inline CSS in HTML <style> tags
 *
 * Extends the base Minifier to process <style> elements
 * and optionally insert semicolons at the end of CSS rules.
 *
 * Configuration (backward compatible):
 * - New: minify.auto_semicolon.css
 * - Old: minify.insert_semicolon.css
 *
 * Usage:
 * Add this middleware to your HTTP kernel or route middleware group
 * to automatically minify CSS output in Blade or HTML responses.
 */
class MinifyCssMiddleware extends Minifier
{
    /**
     * @var bool Flag to allow automatic insertion of semicolons in CSS
     */
    protected static bool $allowInsertSemicolon;

    /**
     * Apply CSS minification to all <style> tags in the HTML
     *
     * @return string Minified HTML content
     */
    protected function apply(): string
    {
        // Mark CSS minification as used
        static::$minifyCssHasBeenUsed = true;

        // Determine if auto-semicolon is enabled (new config -> fallback to old)
        static::$allowInsertSemicolon = (bool) $this->getConfig(
            'auto_semicolon.css',      // new config
            'insert_semicolon.css',    // old config
            false                      // default
        );

        $cssProcessor = new CssProcessor();

        // Process each <style> element in the DOM
        foreach ($this->getByTag('style') as $el) {
            $minifiedCss = $cssProcessor->replace(
                $el->nodeValue,
                static::$minifyCssHasBeenUsed,
                static::$allowInsertSemicolon
            );

            // Replace content of <style> tag with minified CSS
            $el->nodeValue = '';
            $el->appendChild(static::$dom->createTextNode($minifiedCss));
        }

        // Return the modified HTML
        return static::$dom->saveHtml();
    }
}
