<?php

namespace Fahlisaputra\Minify\Core\Obfuscator;

/**
 * JavaScript Obfuscator
 *
 * Core class to obfuscate JavaScript code.
 * Supports optional HTML input conversion, domain restriction, and expiration.
 *
 * This obfuscator was originally based on Hunter PHP JavaScript Obfuscator by nicxlau.
 * @see https://github.com/nicxlau/hunter-php-javascript-obfuscator
 * Thanks to nicxlau for the original implementation.
 *
 * @package Fahlisaputra\Minify\Core\Obfuscator
 */
class JavaScriptObfuscator
{
    private string $code;
    private string $mask;
    private int $interval;
    private int $option = 0;
    private int $expireTime = 0;
    private array $domainNames = [];

    /**
     * Constructor
     *
     * @param string $code JS code or HTML content
     * @param bool $html If true, input is treated as HTML
     */
    public function __construct(string $code, bool $html = false)
    {
        if ($html) {
            $code = $this->cleanHtml($code);
            $this->code = $this->html2Js($code);
        } else {
            $code = $this->cleanJS($code);
            $this->code = $code;
        }

        $this->mask = $this->generateMask();
        $this->interval = rand(1, 50);
        $this->option = rand(2, 8);
    }

    /**
     * Generate a random mask string
     */
    private function generateMask(): string
    {
        $charset = str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        return substr($charset, 0, 9);
    }

    /**
     * Encode a character based on mask
     */
    private function hashIt(string $s): string
    {
        for ($i = 0; $i < strlen($this->mask); $i++) {
            $s = str_replace("$i", $this->mask[$i], $s);
        }

        return $s;
    }

    /**
     * Prepare JS code with optional domain and expiration restrictions
     */
    private function prepare(): void
    {
        if (!empty($this->domainNames)) {
            $code = "if(window.location.hostname==='${this->domainNames[0]}'";
            for ($i = 1; $i < count($this->domainNames); $i++) {
                $code .= " || window.location.hostname==='${this->domainNames[$i]}'";
            }
            $this->code = $code . ') {' . $this->code . '}';
        }

        if ($this->expireTime > 0) {
            $this->code = 'if((Math.round(+new Date()/1000)) < ' . $this->expireTime . ') {' . $this->code . '}';
        }
    }

    /**
     * Encode the JS code into obfuscated format
     */
    private function encodeIt(): string
    {
        $this->prepare();
        $str = '';
        for ($i = 0; $i < strlen($this->code); $i++) {
            $str .= $this->hashIt(base_convert(ord($this->code[$i]) + $this->interval, 10, $this->option)) . $this->mask[$this->option];
        }

        return $str;
    }

    /**
     * Obfuscate JS code
     *
     * @return string Obfuscated JS code
     */
    public function obfuscate(): string
    {
        $rand = rand(0, 99);
        $rand1 = rand(0, 99);

        return "var _0xc{$rand}e=[\"\",\"\x73\x70\x6C\x69\x74\",\"\x30\x31\x32\x33\x34\x35\x36\x37\x38\x39\x61\x62\x63\x64\x65\x66\x67\x68\x69\x6A\x6B\x6C\x6D\x6E\x6F\x70\x71\x72\x73\x74\x75\x76\x77\x78\x79\x7A\x41\x42\x43\x44\x45\x46\x47\x48\x49\x4A\x4B\x4C\x4D\x4E\x4F\x50\x51\x52\x53\x54\x55\x56\x57\x58\x59\x5A\x2B\x2F\",\"\x73\x6C\x69\x63\x65\",\"\x69\x6E\x64\x65\x78\x4F\x66\",\"\",\"\",\"\x2E\",\"\x70\x6F\x77\",\"\x72\x65\x64\x75\x63\x65\",\"\x72\x65\x76\x65\x72\x73\x65\",\"\x30\"];function _0xe{$rand1}c(d,e,f){var g=_0xc{$rand}e[2][_0xc{$rand}e[1]](_0xc{$rand}e[0]);var h=g[_0xc{$rand}e[3]](0,e);var i=g[_0xc{$rand}e[3]](0,f);var j=d[_0xc{$rand}e[1]](_0xc{$rand}e[0])[_0xc{$rand}e[10]]()[_0xc{$rand}e[9]](function(a,b,c){if(h[_0xc{$rand}e[4]](b)!==-1)return a+=h[_0xc{$rand}e[4]](b)*(Math[_0xc{$rand}e[8]](e,c))},0);var k=_0xc{$rand}e[0];while(j>0){k=i[j%f]+k;j=(j-(j%f))/f}return k||_0xc{$rand}e[11]}eval(function(h,u,n,t,e,r){r=\"\";for(var i=0,len=h.length;i<len;i++){var s=\"\";while(h[i]!==n[e]){s+=h[i];i++}for(var j=0;j<n.length;j++)s=s.replace(new RegExp(n[j],\"g\"),j);r+=String.fromCharCode(_0xe{$rand1}c(s,e,10)-t)}return decodeURIComponent(escape(r))}(\"".$this->encodeIt().'",'.rand(1, 100).',"'.$this->mask.'",'.$this->interval.','.$this->option.','.rand(1, 60).'))';
    }

    /**
     * Set expiration time for obfuscated code
     *
     * @param string|int $expireTime Timestamp or date string
     * @return bool
     */
    public function setExpiration($expireTime): bool
    {
        if (strtotime($expireTime)) {
            $this->expireTime = strtotime($expireTime);
            return true;
        }
        return false;
    }

    /**
     * Add domain restriction
     *
     * @param string $domainName
     * @return bool
     */
    public function addDomainName(string $domainName): bool
    {
        if ($this->isValidDomain($domainName)) {
            $this->domainNames[] = $domainName;
            return true;
        }
        return false;
    }

    /**
     * Validate domain name
     */
    private function isValidDomain(string $domain_name): bool
    {
        return preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name)
            && preg_match('/^.{1,253}$/', $domain_name)
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name);
    }

    /**
     * Convert HTML content to JavaScript document.write
     */
    private function html2Js(string $code): string
    {
        $search = [
            '/\>[^\S ]+/s',      // strip whitespaces after tags
            '/[^\S ]+\</s',      // strip whitespaces before tags
            '/(\s)+/s',          // collapse multiple whitespaces
            '/<!--(.|\s)*?-->/', // remove HTML comments
        ];
        $replace = [
            '>',
            '<',
            '\\1',
            '',
        ];

        $code = preg_replace($search, $replace, $code);
        return "document.write('" . addslashes($code . ' ') . "');";
    }

    /**
     * Clean HTML input
     */
    private function cleanHtml(string $code): string
    {
        return preg_replace('/<!--(.|\s)*?-->/', '', $code);
    }

    /**
     * Clean JS input
     */
    private function cleanJS(string $code): string
    {
        $pattern = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/';
        $code = preg_replace($pattern, '', $code);

        $search = [
            '/\>[^\S ]+/s',
            '/[^\S ]+\</s',
            '/(\s)+/s',
            '/<!--(.|\s)*?-->/',
        ];
        $replace = [
            '>',
            '<',
            '\\1',
            '',
        ];

        return preg_replace($search, $replace, $code);
    }
}
