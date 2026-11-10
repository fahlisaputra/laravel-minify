<?php

namespace Fahlisaputra\Minify\Middleware;

use Fahlisaputra\Minify\Helpers\Javascript;

class MinifyJavascript extends Minifier
{
    protected static $allowInsertSemicolon;

    protected function apply()
    {
        $this->state->minifyJavascriptHasBeenUsed = true;
        static::$allowInsertSemicolon = (bool) config('minify.insert_semicolon.js', false);
        $javascript = new Javascript();
        $obfuscate = (bool) config('minify.obfuscate', false);
        $skipLdJson = (bool) config('minify.skip_ld_json', true);

        foreach ($this->getByTag('script') as $el) {
            // Skip minification for LD+JSON scripts if option is enabled
            if ($skipLdJson && $this->isLdJsonScript($el)) {
                continue;
            }

            $value = $javascript->replace($el->nodeValue, static::$allowInsertSemicolon);
            if ($obfuscate) {
                $value = $javascript->obfuscate($value);
            }
            $el->nodeValue = '';
            $el->appendChild($this->state->dom->createTextNode($value));
        }

        return $this->state->dom->saveHtml();
    }

    /**
     * Check if the script element is an LD+JSON script.
     *
     * @param \DOMElement $el
     *
     * @return bool
     */
    protected function isLdJsonScript($el): bool
    {
        return $el->hasAttribute('type') &&
               strtolower($el->getAttribute('type')) === 'application/ld+json';
    }
}
