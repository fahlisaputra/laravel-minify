<?php

namespace Fahlisaputra\Minify\Core;

/**
 * CSS Processor
 *
 * Core class to process CSS content.
 * Supports minification and optional automatic semicolon insertion.
 */
class CssProcessor
{
    /**
     * Insert missing semicolons at the end of CSS rules.
     *
     * @param string $value CSS content
     * @return string CSS content with semicolons inserted
     */
    protected function insertSemicolon(string $value): string
    {
        return preg_replace([
            // Add semicolon if missing at the end of a rule
            '#^[A-Za-z\s\-]+:.+(?<!({|}|;))$#m',
            '#^([A-Za-z\s\-]+):(.+)[;]$(\n+|\s+){#m',
        ], [
            '$0;',
            '$1:$2$3{',
        ], $value);
    }

    /**
     * Minify the given CSS content.
     *
     * @param string $value CSS content
     * @param bool $allowInsertSemicolon Automatically insert missing semicolons if true
     * @return string Minified CSS content
     */
    public function replace(string $value, bool $allowInsertSemicolon = true): string
    {
        // Optionally insert semicolons
        if ($allowInsertSemicolon) {
            $value = $this->insertSemicolon($value);
        }

        // Apply CSS minification using regex replacements
        $value = preg_replace([
            // Remove comments except /*! ... */
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
            // Remove unnecessary whitespace and semicolons
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
            // Replace 0 with unit (0px, 0em, etc) to plain 0
            '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
            // Replace :0 0 0 0 with :0
            '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
            // Replace background-position:0 with background-position:0 0
            '#(background-position):0(?=[;\}])#si',
            // Remove leading zeros in decimals (e.g., 0.6 => .6)
            '#(?<=[\s:,\-])0+\.(\d+)#s',
            // Minify string values (except content)
            '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
            // Minify URLs
            '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
            // Minify hex color codes (e.g., #aabbcc => #abc)
            '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
            // Replace border/outline none with 0
            '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
            // Remove empty selectors
            '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s',
        ], [
            '$1',              // Keep strings intact
            '$1$2$3$4$5$6$7',  // Preserve groups after whitespace removal
            '$1',              // Replace 0 units with 0
            ':0',              // :0 0 0 0 => :0
            '$1:0 0',          // background-position fix
            '.$1',             // Remove leading zero in decimals
            '$1$3',            // Minify string values
            '$1$2$4$5',        // Minify URLs
            '$1$2$3',          // Minify hex codes
            '$1:0',            // border/outline none => 0
            '$1$2',            // Remove empty selectors
        ], $value);

        return trim($value);
    }
}
