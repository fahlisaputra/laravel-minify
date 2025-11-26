<?php

namespace Fahlisaputra\Minify\Middleware;

use Fahlisaputra\Minify\Core\Minifier;

/**
 * Middleware to minify full HTML output
 *
 * Extends the base Minifier and handles:
 * - Minification of HTML content
 * - Preserving inline CSS and JS marked as "ignore--minify"
 * - Removing unnecessary whitespaces and comments
 *
 * Configuration:
 * - minify.remove_comments (bool): Remove HTML comments, default true
 *
 * Usage:
 * Add this middleware to your HTTP kernel or route middleware group
 * to automatically minify HTML output of Blade or raw HTML responses.
 */
class MinifyHtmlMiddleware extends Minifier
{
    /**
     * Regex to remove HTML comments (except conditional IE comments)
     */
    protected const REGEX_REMOVE_COMMENT = "#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s";

    /**
     * Apply HTML minification
     *
     * @return string Minified HTML content
     */
    protected function apply(): string
    {
        // Backup ignored <style> and <script> elements
        $ignoredCss = $this->getByTagOnlyIgnored('style');
        $ignoredJs  = $this->getByTagOnlyIgnored('script');

        // If both CSS and JS have been minified already
        if (static::$minifyCssHasBeenUsed && static::$minifyJavascriptHasBeenUsed) {
            $html = $this->replace(static::$dom->saveHtml());

            if (empty($ignoredCss) && empty($ignoredJs)) {
                return $html;
            }

            $this->loadDom($html, true);
        } else {
            // Backup normal <style> and <script> if not yet minified
            if (!static::$minifyCssHasBeenUsed) {
                $css = $this->getByTag('style');
            }
            if (!static::$minifyJavascriptHasBeenUsed) {
                $js = $this->getByTag('script');
            }

            $html = $this->replace(static::$dom->saveHtml());
            $this->loadDom($html, true);

            if (isset($css)) $this->append('getByTag', 'style', $css);
            if (isset($js))  $this->append('getByTag', 'script', $js);
        }

        // Restore ignored elements
        if (!empty($ignoredCss)) $this->append('getByTagOnlyIgnored', 'style', $ignoredCss);
        if (!empty($ignoredJs))  $this->append('getByTagOnlyIgnored', 'script', $ignoredJs);

        return trim(static::$dom->saveHtml());
    }

    /**
     * Replace the content of elements with original backed-up content
     *
     * @param string $function Function to get elements (getByTag / getByTagOnlyIgnored)
     * @param string $tags HTML tag to process
     * @param array  $backup Backup of DOM elements
     */
    protected function append(string $function, string $tags, array $backup): void
    {
        $index = 0;
        foreach ($this->{$function}($tags) as $el) {
            $el->nodeValue = '';
            $el->appendChild(static::$dom->createTextNode($backup[$index]->nodeValue));
            $index++;
        }
    }

    /**
     * Remove HTML comments except conditional comments
     *
     * @param string $value HTML content
     * @return string
     */
    protected function removeComment(string $value): string
    {
        return preg_replace(self::REGEX_REMOVE_COMMENT, '', $value);
    }

    /**
     * Perform main replacements and whitespace cleanup on HTML
     *
     * @param string $value HTML content
     * @return string Minified HTML
     */
    protected function replace(string $value): string
    {
        $value = trim(preg_replace([
            '#<(img|input)(>| .*?>)#s',
            '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
            '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s',
            '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s',
            '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s',
            '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s',
            '#<(img|input)(>| .*?>)<\/\1>#s',
            '#(&nbsp;)&nbsp;(?![<\s])#',
            '#(?<=\>)(&nbsp;)(?=\<)#',
            '/\s+/',
        ], [
            '<$1$2</$1>',
            '$1$2$3',
            '$1$2$3',
            '$1$2$3$4$5',
            '$1$2$3$4$5$6$7',
            '$1$2$3',
            '<$1$2',
            '$1 ',
            '$1',
            ' ',
        ], $value));

        $allowRemoveComments = (bool) config('minify.remove_comments', true);

        return $allowRemoveComments ? $this->removeComment($value) : $value;
    }
}
