<?php

namespace HS\View;

use Illuminate\View\Compilers\BladeCompiler;

class BaseBladeCompiler extends BladeCompiler
{
    /**
     * Compile the "raw" echo statements.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileRawEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->rawTags[0], $this->rawTags[1]);

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

            return $matches[1] ? substr($matches[0], 1) : "<?php echo {$this->onlyAllowVariables($matches[2])}; ?>{$whitespace}";
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the "regular" echo statements.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileRegularEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

            $wrapped = sprintf($this->echoFormat, $this->onlyAllowVariables($matches[2]));

            return $matches[1] ? substr($matches[0], 1) : "<?php echo {$wrapped}; ?>{$whitespace}";
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the escaped echo statements.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileEscapedEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

            return $matches[1] ? $matches[0] : "<?php echo e({$this->onlyAllowVariables($matches[2])}); ?>{$whitespace}";
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Only allow blade to echo variables that include `$`
     * Allowed: echo  $foo;
     * Disallowed: echo foo;
     *
     * This prevents many potential errors that HelpSpot users may accidentally see if messages contain:
     * {{ and }} - which is a syntax error because "and" is a reserved keyword
     *             @link https://www.php.net/manual/en/reserved.keywords.php
     * {{ foobar }} - which is interpreted as an undefined constant
     * @param $match
     * @return string
     */
    protected function onlyAllowVariables($match)
    {
        if(strpos($match, '$') === false) {
            return hs_setting($match, "'$match'");
        }

        return $match;
    }
}
