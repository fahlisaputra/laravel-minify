<?php

namespace Fahlisaputra\Minify\Middleware;

class MinifyHtml extends Minifier
{
    protected const REGEX_REMOVE_COMMENT = "#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s";

    protected function apply()
    {
        $ignoredCss = $this->getByTagOnlyIgnored('style');
        $ignoredJs = $this->getByTagOnlyIgnored('script');

        if (static::$minifyCssHasBeenUsed && static::$minifyJavascriptHasBeenUsed) {
            $html = $this->replace(static::$dom->saveHtml());

            if (empty($ignoredCss) || empty($ignoredCss)) {
                return $html;
            }

            $this->loadDom($html, true);
        } else {
            if (!static::$minifyCssHasBeenUsed) {
                $css = $this->getByTag('style');
            }

            if (!static::$minifyJavascriptHasBeenUsed) {
                $js = $this->getByTag('script');
            }

            $html = $this->replace(static::$dom->saveHtml());

            $this->loadDom($html, true);

            if (isset($css)) {
                $this->append('getByTag', 'style', $css);
            }
            if (isset($js)) {
                $this->append('getByTag', 'script', $js);
            }
        }

        if (!empty($ignoredCss)) {
            $this->append('getByTagOnlyIgnored', 'style', $ignoredCss);
        }
        if (!empty($ignoredJs)) {
            $this->append('getByTagOnlyIgnored', 'script', $ignoredJs);
        }

        return trim(static::$dom->saveHtml());
    }

    protected function append(string $function, string $tags, array $backup)
    {
        $index = 0;
        foreach ($this->{$function}($tags) as $el) {
            $el->nodeValue = '';
            $el->appendChild(static::$dom->createTextNode($backup[$index]->nodeValue));
            $index++;
        }
    }

    protected function removeComment($value)
    {
        return preg_replace(self::REGEX_REMOVE_COMMENT, '', $value);
    }

    protected function replace($value)
    {
        $value = trim(preg_replace([
            // t = text
            // o = tag open
            // c = tag close
            // Keep important white-space(s) after self-closing HTML tag(s)
            '#<(img|input)(>| .*?>)#s',
            // Remove a line break and two or more white-space(s) between tag(s)
            '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
            '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s',
            // t+c || o+t
            '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s',
            // o+o || c+c
            '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s',
            // c+t || t+o || o+t -- separated by long white-space(s)
            '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s',
            // empty tag
            '#<(img|input)(>| .*?>)<\/\1>#s',
            // reset previous fix
            '#(&nbsp;)&nbsp;(?![<\s])#',
            // clean up ...
            '#(?<=\>)(&nbsp;)(?=\<)#',
            // --ibid
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

        return $allowRemoveComments == false
            ? $value
            : $this->removeComment($value);
    }
}
