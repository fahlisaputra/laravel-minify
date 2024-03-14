<?php

namespace Fahlisaputra\Minify\Middleware;

use Closure;
use DOMDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class Minifier
{
    protected static $dom;
    protected static $minifyCssHasBeenUsed = false;
    protected static $minifyJavascriptHasBeenUsed = false;

    protected static $isEnable;
    protected static $ignore;

    protected const REGEX_VALID_HTML = "/<html[^>]*>.*<head[^>]*>.*<\/head[^>]*>.*<body[^>]*>.*<\/body[^>]*>.*<\/html[^>]*>/is";

    abstract protected function apply();

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$this->shouldProcessMinify($request, $response)) {
            if (!(static::$dom instanceof DOMDocument)) {
                return $response;
            }
        }

        $html = $response->getContent();

        $html = self::replaceDirectives($html);

        $this->loadDom($html);

        return $response->setContent($this->apply());
    }

    protected function replaceDirectives($html): string
    {
        if (!config('minify.enable_directive_replacement', false)) {
            return $html;
        }

        // split the html into head and body
        $body = explode('<body', $html);

        // mask the directive that want to keep, solution by @SaeedHeydari #12
        $keepDirectivesKeys = config('minify.keep_directives', []);
        $keepDirectives = [];
        foreach ($keepDirectivesKeys as $key) {
            $keepDirectives[$key] = '____'.uniqid().'____';
            $body[1] = str_replace($key, $keepDirectives[$key], $body[1]);
        }

        // replace custom directives, issue #12
        $directives = config('minify.directives', []);
        foreach ($directives as $search => $replace) {
            $body[1] = str_replace($search, $replace, $body[1]);
        }

        // unmask the directive that want to keep
        foreach ($keepDirectives as $replace => $search) {
            $body[1] = str_replace($search, $replace, $body[1]);
        }

        // rejoin the html
        $html = $body[0].'<body'.$body[1];

        return $html;
    }

    protected function shouldProcessMinify($request, $response): bool
    {
        if (!$this->isEnable()) {
            return false;
        }

        if ($response instanceof JsonResponse) {
            return false;
        }

        if ($response instanceof BinaryFileResponse) {
            return false;
        }

        if ($response instanceof StreamedResponse) {
            return false;
        }

        if ($response->original instanceof View) {
            $data = $response->original->getData();
            if (isset($data['ignore_minify']) && $data['ignore_minify'] === true) {
                return false;
            }
        }

        foreach ($this->ignore() as $route) {
            if ($request->is($route)) {
                return false;
            }
        }

        $response = $response->getContent();

        if (empty($response) or !is_string($response) or $this->isEmpty($response)) {
            return false;
        }

        return $this->validHtml($response);
    }

    protected function isEmpty(string $value): bool
    {
        return (bool) preg_match("/^\s*$/", $value);
    }

    protected function validHtml(string $value): bool
    {
        return (bool) preg_match(self::REGEX_VALID_HTML, $value);
    }

    protected function isEnable(): bool
    {
        if (is_null(static::$isEnable)) {
            static::$isEnable = (bool) config('minify.enabled', true);
        }

        return static::$isEnable;
    }

    protected function ignore(): array
    {
        if (is_null(static::$ignore)) {
            static::$ignore = (array) config('minify.ignore', []);
        }

        return static::$ignore;
    }

    protected function matchHtmlTag(string $value, string $tags)
    {
        if (!preg_match_all('/<'.$tags."[^>]*>(.*?)<\/".$tags.'[^>]*>/is', $value, $matches)) {
            return null;
        }

        return $matches;
    }

    protected function loadDom(string $html, bool $force = false)
    {
        if (static::$dom instanceof DOMDocument) {
            if ($force) {
            } else {
                return;
            }
        }

        static::$dom = new DOMDocument();
        @static::$dom->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_SCHEMA_CREATE);
    }

    protected function getByTag(string $tags): array
    {
        $result = [];
        $element = static::$dom->getElementsByTagName($tags);

        foreach ($element as $el) {
            $value = $el->nodeValue;
            if ($this->isEmpty($value)) {
                continue;
            }
            if ($el->hasAttribute('ignore--minify')) {
                continue;
            }

            $result[] = $el;
        }

        return $result;
    }

    protected function getByTagOnlyIgnored(string $tags): array
    {
        $result = [];
        $element = static::$dom->getElementsByTagName($tags);

        foreach ($element as $el) {
            $value = $el->nodeValue;

            if ($this->isEmpty($value)) {
                continue;
            }

            if (!$el->hasAttribute('ignore--minify')) {
                continue;
            }

            $result[] = $el;
        }

        return $result;
    }
}
