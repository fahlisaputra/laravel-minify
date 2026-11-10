<?php

namespace Fahlisaputra\Minify\Middleware;

use Fahlisaputra\Minify\Helpers\CSS;

class MinifyCss extends Minifier
{
    protected static $allowInsertSemicolon;

    protected function apply()
    {
        $this->state->minifyCssHasBeenUsed = true;
        static::$allowInsertSemicolon = (bool) config('minify.insert_semicolon.css', false);
        $css = new CSS();

        foreach ($this->getByTag('style') as $el) {
            $value = $css->replace($el->nodeValue, $this->state->minifyCssHasBeenUsed);

            $el->nodeValue = '';
            $el->appendChild($this->state->dom->createTextNode($value));
        }

        return $this->state->dom->saveHtml();
    }
}
