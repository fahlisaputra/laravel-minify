<?php

namespace Fahlisaputra\Minify\Middleware;

use Fahlisaputra\Minify\Helpers\CSS;

class MinifyCss extends Minifier
{
    protected static $allowInsertSemicolon;
    protected static $css;

    protected function apply()
    {
        static::$css = new CSS();
        static::$minifyCssHasBeenUsed = true;
        static::$allowInsertSemicolon = (bool) config("minify.insert_semicolon.css", true);

        foreach ($this->getByTag("style") as $el)
        {
            $value = $this->css->replace($el->nodeValue, $this->allowInsertSemicolon);

            $el->nodeValue = "";
            $el->appendChild(static::$dom->createTextNode($value));
        }

        return static::$dom->saveHtml();
    }
}
