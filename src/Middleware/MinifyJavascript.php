<?php

namespace Fahlisaputra\Minify\Middleware;

use Fahlisaputra\Minify\Helpers\Javascript;

class MinifyJavascript extends Minifier
{
    protected static $allowInsertSemicolon;
    protected static $javascript;

    protected function apply()
    {
        static::$javascript = new Javascript();
        static::$minifyJavascriptHasBeenUsed = true;
        $obfuscate = (bool) config("minify.obfuscate", false);
        static::$allowInsertSemicolon = (bool) config("minify.insert_semicolon.js", true);
        foreach ($this->getByTag("script") as $el)
        {
            $value = $this->javascript->replace($el->nodeValue, $this->allowInsertSemicolon);
            if ($obfuscate)
            {
                $value = $this->javascript->obfuscate($value);
            }
            $el->nodeValue = "";
            $el->appendChild(static::$dom->createTextNode($value));
        }

        return static::$dom->saveHtml();
    }
}
