<?php

namespace Fahlisaputra\Minify\Core;

use Fahlisaputra\Minify\Core\Obfuscator\JavaScriptObfuscator;

/**
 * JavaScript Processor
 *
 * Core class to process JavaScript content.
 * Supports minification, optional automatic semicolon insertion,
 * and obfuscation.
 */
class JavaScriptProcessor
{
    /**
     * Insert semicolons automatically where necessary.
     *
     * This method attempts to insert semicolons at the end of JavaScript statements
     * to avoid ASI (Automatic Semicolon Insertion) issues during minification.
     *
     * @param string $value JavaScript code
     * @return string JavaScript code with semicolons inserted
     */
    protected function insertSemicolon(string $value): string
    {
        // Remove block and line comments
        $value = preg_replace(
            '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/',
            '',
            $value
        );

        // Remove newlines inside template literals (`...`)
        $value = preg_replace_callback('/(`[\S\s]*?[^\\\`]`)/', function ($m) {
            return preg_replace('/\n+/', '', $m[1]);
        }, $value);

        $result = [];
        $lines = explode("\n", trim($value));

        // Patterns that indicate semicolon should not be inserted
        $patternRegex = [
            '#(?:({|\[|\(|,|;|=>|\:|\?|\.))$#', // Line ends with these characters
            '#^\s*$#',                           // Empty lines
            '#^(do|else)$#',                     // Reserved keywords
        ];

        $loop = 0;

        foreach ($lines as $line) {
            $loop++;
            $insert = false;
            $shouldInsert = true;

            // Check if current line matches any exception patterns
            foreach ($patternRegex as $pattern) {
                $match = preg_match($pattern, trim($line));
                $shouldInsert = $shouldInsert && !$match;
            }

            if ($shouldInsert) {
                $i = $loop;

                while (true) {
                    if ($i >= count($lines)) {
                        $insert = true;
                        break;
                    }

                    $nextLine = trim($lines[$i]);
                    $i++;

                    if (!$nextLine) continue;

                    $insert = true;
                    $regex = ['#^(\?|\:|,|\.|{|}|\)|\])#'];

                    foreach ($regex as $r) {
                        $insert = $insert && !preg_match($r, $nextLine);
                    }

                    // Special case: don't insert semicolon before 'else', 'elseif', 'catch', etc.
                    if (preg_match('#(?:\\})$#', trim($line)) &&
                        preg_match("#^(else|elseif|else\s*if|catch)#", $nextLine)) {
                        $insert = false;
                    }

                    break;
                }
            }

            $result[] = $insert ? sprintf('%s;', $line) : $line;
        }

        return join("\n", $result);
    }

    /**
     * Minify JavaScript content.
     *
     * @param string $value JavaScript code
     * @param bool $allowInsertSemicolon Whether to insert semicolons automatically
     * @return string Minified JavaScript code
     */
    public function replace(string $value, bool $allowInsertSemicolon = true): string
    {
        if ($allowInsertSemicolon) {
            $value = $this->insertSemicolon($value);
        }

        // Apply regex-based minification
        return trim(preg_replace([
            // Remove comments and leading/trailing whitespace
            '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
            // Remove whitespace outside strings and regex
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
            // Remove unnecessary trailing semicolons before }
            '#;+\}#',
            // Minify object attributes (except JSON strings)
            '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
            // Convert array-like access to dot notation
            '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i',
        ], [
            '$1',
            '$1$2',
            '}',
            '$1$3',
            '$1.$3',
        ], $value));
    }

    /**
     * Obfuscate JavaScript code.
     *
     * @param string $value JavaScript code
     * @return string Obfuscated JavaScript code
     */
    public function obfuscate(string $value): string
    {
        $obfuscator = new JavaScriptObfuscator($value);

        return $obfuscator->Obfuscate();
    }
}
