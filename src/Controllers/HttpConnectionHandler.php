<?php

namespace Fahlisaputra\Minify\Controllers;

use Fahlisaputra\Minify\Core\CssProcessor;
use Fahlisaputra\Minify\Core\JavaScriptProcessor;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Class HttpConnectionHandler
 *
 * Handles HTTP requests to serve minified CSS and JS assets.
 * Supports caching, automatic semicolon insertion, and JS obfuscation.
 * Backward compatible with old config keys.
 */
class HttpConnectionHandler
{
    /**
     * Handle the incoming request to minify and serve a file.
     *
     * @param string $file The relative path of the asset file requested.
     * @return Response
     */
    public function __invoke(string $file): Response
    {
        // Load configuration with fallback to old keys for backward compatibility
        $config = [
            'js_insert_semicolon'  => (bool) config('minify.auto_semicolon.js', config('minify.insert_semicolon.js', true)),
            'css_insert_semicolon' => (bool) config('minify.auto_semicolon.css', config('minify.insert_semicolon.css', true)),
            'obfuscate'            => (bool) config('minify.obfuscate_js', config('minify.obfuscate', false)),
            'enabled'              => (bool) config('minify.minify_assets.enabled', config('minify.assets_enabled', true)),
            'storage'              => config('minify.minify_assets.assets_path', config('minify.assets_storage', 'resources')),
            'route'                => config('minify.minify_assets.assets_route', config('minify.assets_route', '_minify')),
            'buildStorage'         => config('minify.minify_assets.cache.cache_path', config('minify.assets_build_storage', 'assets/_minify')),
        ];

        $css = new CssProcessor();
        $js  = new JavaScriptProcessor();

        $file = ltrim($file, '/\\');

        $cacheFile = storage_path('framework/cache/minify.php');
        $this->ensureCacheFileExists($cacheFile);

        $cache = require $cacheFile;
        $cachedFile = $cache[$file] ?? null;

        $realFilePath  = base_path(rtrim($config['storage'], '/').'/'.$file);
        $buildFilePath = $cachedFile;

        if (!file_exists($realFilePath)) {
            return abort(404);
        }

        $this->createNestedDirectories($config['buildStorage']);

        if ($this->needsRebuild($realFilePath, $buildFilePath, $cachedFile)) {
            $content = file_get_contents($realFilePath);
            $mime = 'text/plain';

            if ($config['enabled']) {
                [$content, $mime] = $this->minifyContent($file, $content, $css, $js, $config);
            }

            $ext = $this->detectExtension($mime);
            $newFileName = Str::random(24).$ext;

            if ($buildFilePath && file_exists($buildFilePath)) {
                unlink($buildFilePath);
            }

            $cachedFile = rtrim($config['buildStorage'], '/').'/'.$newFileName;
            file_put_contents($cachedFile, $content);

            $cache[$file] = $cachedFile;
            file_put_contents($cacheFile, $this->exportCache($cache));
        } else {
            $ext  = pathinfo($file, PATHINFO_EXTENSION);
            $mime = $this->detectMime($ext);
            $content = file_get_contents($buildFilePath);
        }

        // Generate caching headers
        $lastModified = gmdate('D, d M Y H:i:s', filemtime($cachedFile)) . ' GMT';
        $etag = md5_file($cachedFile);

        // Check conditional GET headers
        $requestHeaders = request()->headers;
        if (($requestHeaders->get('If-Modified-Since') === $lastModified) ||
            ($requestHeaders->get('If-None-Match') === $etag)) {
            return response('', 304);
        }

        return response($content, 200, [
            'Content-Type' => $mime.'; charset=UTF-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Last-Modified' => $lastModified,
            'ETag' => $etag,
        ]);
    }

    /**
     * Ensure the cache file exists; create if missing.
     *
     * @param string $cacheFile Path to the cache file.
     * @return void
     */
    private function ensureCacheFileExists(string $cacheFile): void
    {
        if (!file_exists($cacheFile)) {
            file_put_contents($cacheFile, "<?php\nreturn ".var_export([], true).";\n");
        }
    }

    /**
     * Create nested directories if they do not exist.
     *
     * @param string $path Path to create.
     * @return void
     */
    private function createNestedDirectories(string $path): void
    {
        $path = rtrim($path, '/');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Determine whether the file needs to be minified or rebuilt.
     *
     * @param string $realFilePath Path to the original file.
     * @param string|null $buildFilePath Path to the cached minified file.
     * @param string|null $cachedFile Cached file path.
     * @return bool
     */
    private function needsRebuild(string $realFilePath, ?string $buildFilePath, ?string $cachedFile): bool
    {
        if (!file_exists($buildFilePath) || !$cachedFile) {
            return true;
        }

        return filemtime($realFilePath) > filemtime($buildFilePath);
    }

    /**
     * Minify the content based on file type (CSS or JS).
     *
     * @param string $file Filename.
     * @param string $content Original content.
     * @param CssProcessor $css CSS helper.
     * @param JavaScriptProcessor $js JS helper.
     * @param array $config Configuration array.
     * @return array [minified content, MIME type]
     */
    private function minifyContent(string $file, string $content, CssProcessor $css, JavaScriptProcessor $js, array $config): array
    {
        $mime = 'text/plain';

        if (preg_match("/\.css$/", $file)) {
            $content = $css->replace($content, $config['css_insert_semicolon']);
            $mime = 'text/css';
        } elseif (preg_match("/\.js$/", $file)) {
            $content = $js->replace($content, $config['js_insert_semicolon']);
            if ($config['obfuscate']) {
                $content = $js->obfuscate($content);
            }
            $mime = 'application/javascript';
        }

        return [$content, $mime];
    }

    /**
     * Detect MIME type based on file extension.
     *
     * @param string $ext File extension.
     * @return string MIME type.
     */
    private function detectMime(string $ext): string
    {
        return match(strtolower($ext)) {
            'css' => 'text/css',
            'js'  => 'application/javascript',
            default => 'text/plain',
        };
    }

    /**
     * Determine the minified file extension based on the MIME type.
     *
     * @param string $mime MIME type.
     * @return string File extension.
     */
    private function detectExtension(string $mime): string
    {
        return match($mime) {
            'text/css' => '.min.css',
            'application/javascript' => '.min.js',
            default => '',
        };
    }

    /**
     * Export the cache array to PHP code for storage.
     *
     * @param array $cache Cache array.
     * @return string PHP code string.
     */
    private function exportCache(array $cache): string
    {
        return "<?php\nreturn ".var_export($cache, true).";\n";
    }
}
