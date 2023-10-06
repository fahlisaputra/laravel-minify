<?php

namespace Fahlisaputra\Minify\Helpers;

class Javascript
{
    protected function insertSemicolon($value)
    {
        $value = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $value);
        $value = preg_replace_callback('/(`[\S\s]*?[^\\\`]`)/', function ($m) {
            return preg_replace('/\n+/', '', $m[1]);
        }, $value);

        $result = [];
        $code = explode("\n", trim($value));

        $patternRegex = [
            '#(?:({|\[|\(|,|;|=>|\:|\?|\.))$#',
            '#^\s*$#',
            '#^(do|else)$#',
        ];

        $loop = 0;

        foreach ($code as $line) {
            $loop++;
            $insert = false;
            $shouldInsert = true;

            foreach ($patternRegex as $pattern) {
                $match = preg_match($pattern, trim($line));
                $shouldInsert = $shouldInsert && (bool) !$match;
            }

            if ($shouldInsert) {
                $i = $loop;

                while (true) {
                    if ($i >= count($code)) {
                        $insert = true;
                        break;
                    }

                    $c = trim($code[$i]);
                    $i++;

                    if (!$c) {
                        continue;
                    }

                    $insert = true;
                    $regex = ['#^(\?|\:|,|\.|{|}|\)|\])#'];

                    foreach ($regex as $r) {
                        $insert = $insert && (bool) !preg_match($r, $c);
                    }

                    if ($insert) {
                        if (preg_match('#(?:\\})$#', trim($line)) && preg_match("#^(else|elseif|else\s*if|catch)#", $c)) {
                            $insert = false;
                        }
                    }

                    break;
                }
            }

            if ($insert) {
                $result[] = sprintf('%s;', $line);
            } else {
                $result[] = $line;
            }
        }

        return join("\n", $result);
    }

    public function replace($value, $allowInsertSemicolon = true)
    {
        if ($allowInsertSemicolon) {
            $value = $this->insertSemicolon($value);
        }

        return trim(preg_replace([
            '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
            // Remove white-space(s) outside the string and regex
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
            // Remove the last semicolon
            '#;+\}#',
            // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
            '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
            // --ibid. From `foo['bar']` to `foo.bar`
            '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i',
        ], [
            '$1',
            '$1$2',
            '}',
            '$1$3',
            '$1.$3',
        ], $value));
    }

    public function obfuscate($value)
    {
        $obfuscator = new JsObfuscator($value);

        return $obfuscator->Obfuscate();
    }
}
