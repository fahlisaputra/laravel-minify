<?php

namespace Fahlisaputra\Minify\Middleware;

use Fahlisaputra\Minify\Helpers\CSS;

class MinifyCss extends Minifier
{
    protected static $allowInsertSemicolon;

    protected function apply()
    {
        static::$minifyCssHasBeenUsed = true;
        static::$allowInsertSemicolon = (bool) config('minify.insert_semicolon.css', false);
        $css = new CSS();

        foreach ($this->getByTag('style') as $el) {
            $value = $css->replace($el->nodeValue, static::$minifyCssHasBeenUsed);

            $el->nodeValue = '';
            $el->appendChild(static::$dom->createTextNode($value));
        }

        return static::$dom->saveHtml();
    }
}
