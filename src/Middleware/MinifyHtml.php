<?php

namespace Fahlisaputra\Minify\Middleware;

class MinifyHtml extends Minifier
{
    // Both branches only start where they could first match in a white-space /
    // line-break run: right after the previous match (\G), at the start of the
    // run, or (2nd branch) one char later when the run follows `>`. The old
    // patterns were retried from every position of a run, which is quadratic
    // and allowed ReDoS with user-supplied white-space.
    protected const REGEX_REMOVE_COMMENT = "#(?:\G|(?<!\s))\s*<!--(?!\[if\s).*?-->\s*|(?:\G(?<!\>)|(?<![\n>])|(?<=>\n))\n+(?=\<[^!])#s";

    protected function apply()
    {
        $ignoredCss = $this->getByTagOnlyIgnored('style');
        $ignoredJs = $this->getByTagOnlyIgnored('script');

        if ($this->state->minifyCssHasBeenUsed && $this->state->minifyJavascriptHasBeenUsed) {
            $html = $this->replace($this->state->dom->saveHtml());

            if (empty($ignoredCss) || empty($ignoredCss)) {
                return $html;
            }

            $this->loadDom($html, true);
        } else {
            if (!$this->state->minifyCssHasBeenUsed) {
                $css = $this->getByTag('style');
            }

            if (!$this->state->minifyJavascriptHasBeenUsed) {
                $js = $this->getByTag('script');
            }

            $html = $this->replace($this->state->dom->saveHtml());

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

        return trim($this->state->dom->saveHtml());
    }

    protected function append(string $function, string $tags, array $backup)
    {
        $index = 0;
        foreach ($this->{$function}($tags) as $el) {
            $el->nodeValue = '';
            $el->appendChild($this->state->dom->createTextNode($backup[$index]->nodeValue));
            $index++;
        }
    }

    protected function removeComment($value)
    {
        $result = preg_replace(self::REGEX_REMOVE_COMMENT, '', $value);

        // on a PCRE failure (e.g. backtrack limit) keep the HTML as it is
        return $result === null ? $value : $result;
    }

    protected function replace($value)
    {
        $minified = preg_replace([
            // t = text
            // o = tag open
            // c = tag close
            // Keep important white-space(s) after self-closing HTML tag(s)
            '#<(img|input)(>| .*?>)#s',
            // Remove a line break and two or more white-space(s) between tag(s)
            // The trailing white-space match may only start right after the
            // previous match or at the beginning of a white-space run; `\s*$`
            // retried from every position of a long run is quadratic (ReDoS with
            // user-supplied white-space).
            '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|(?:\G|(?<!\s))\s+$#s',
            // The last branch consumes (and keeps) the rest of a white-space run
            // the first branch could not remove, so the run is not retried from
            // every position, which was quadratic (ReDoS).
            '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)|(?<!\>)(\s++)#s',
            // t+c || o+t
            '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s',
            // o+o || c+c
            // The white-space-before-tag branch is only tried where the old
            // `(?<!\>)` branch could first succeed in a white-space run (right
            // after the previous match, its start, or the 2nd char when the run
            // follows `>`), instead of from every position of the run, which was
            // quadratic (ReDoS).
            '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?:\G(?<!\>)|(?<![\s>])|(?<=>\s))\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s',
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
            '$1$2$3$4',
            '$1$2$3$4$5',
            '$1$2$3$4$5$6$7',
            '$1$2$3',
            '<$1$2',
            '$1 ',
            '$1',
            ' ',
        ], $value);

        // on a PCRE failure (e.g. backtrack limit on a huge page) serve the
        // unminified HTML instead of an empty page
        if ($minified === null) {
            return $value;
        }

        $value = trim($minified);

        $allowRemoveComments = (bool) config('minify.remove_comments', true);

        return $allowRemoveComments == false
            ? $value
            : $this->removeComment($value);
    }
}
