<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

class string_sort
{
    //Array of unsorted strings
    public $strings = [];

    //Sorted strings
    public $sorted = [];

    //Number of strings to return
    public $size = 10;

    //Minimum score to accept
    public $minscore = 0;

    public function __construct($strings)
    {
        //Set strings to sort
        $this->strings = $strings;
    }

    //Return sorted strings as compared to search
    public function dosort($search)
    {

        //Get score for each string and rank
        foreach ($this->strings as $k=>$string) {
            $score = $this->_score(strip_tags($string), $search); //strip tags so spans in title or other tags like <b> from person search don't interfere

            if ($score > $this->minscore) {
                $this->sorted[$score.' '.$string] = $string;
            }
        }

        //Sort
        krsort($this->sorted);

        //Chop any over return size amount
        $this->sorted = array_slice($this->sorted, 0, $this->size);

        return $this->sorted;
    }

    // Quicksilver Score
    //
    // A port of the Quicksilver string ranking algorithm
    // (re-ported from Javascript to PHP by Kenzie Campbell)
    // http://route19.com/logbook/view/quicksilver-score-in-php
    //
    // score("hello world","axl") //=> 0.0
    // score("hello world","ow") //=> 0.6
    // score("hello world","hello world") //=> 1.0
    //
    // The Javascript code is available here
    // http://orderedlist.com/articles/live-search-with-quicksilver-style/
    // http://orderedlist.com/demos/quicksilverjs/javascripts/quicksilver.js
    //
    // The Quicksilver code is available here
    // http://code.google.com/p/blacktree-alchemy/
    // http://blacktree-alchemy.googlecode.com/svn/trunk/Crucible/Code/NSString+BLTRRanking.m
    //
    // The MIT License
    //
    // Copyright (c) 2008 Lachie Cox
    //
    // Permission is hereby granted, free of charge, to any person obtaining a copy
    // of this software and associated documentation files (the "Software"), to deal
    // in the Software without restriction, including without limitation the rights
    // to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    // copies of the Software, and to permit persons to whom the Software is
    // furnished to do so, subject to the following conditions:
    //
    // The above copyright notice and this permission notice shall be included in
    // all copies or substantial portions of the Software.
    //
    // THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    // IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    // FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    // AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    // LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    // OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    // THE SOFTWARE.
    //
    //MODIFIED BY USERSCAPE
    public function _score($string, $abbreviation, $offset = 0)
    {
        $string = utf8_strtolower($string);
        $abbreviation = utf8_strtolower($abbreviation);
        if (utf8_strlen($abbreviation) == 0) {
            return 0.9;
        }
        if (utf8_strlen($abbreviation) > utf8_strlen($string)) {
            return 0.0;
        }
        for ($i = utf8_strlen($abbreviation); $i > 0; $i--) {
            $sub_abbreviation = utf8_substr($abbreviation, 0, $i);
            $index = utf8_strpos($string, $sub_abbreviation);
            if ($index < 0 or $index === false) {
                continue;
            }
            if ($index + utf8_strlen($abbreviation) > utf8_strlen($string) + $offset) {
                continue;
            }
            $next_string = utf8_substr($string, $index + utf8_strlen($sub_abbreviation));
            $next_abbreviation = null;
            if ($i >= utf8_strlen($abbreviation)) {
                $next_abbreviation = '';
            } else {
                $next_abbreviation = utf8_substr($abbreviation, $i);
            }
            $remaining_score = $this->_score($next_string, $next_abbreviation, $offset + $index);
            if ($remaining_score > 0) {
                $score = utf8_strlen($string) - utf8_strlen($next_string);
                if ($index != 0) {
                    $j = 0;
                    $c = ord(utf8_substr($string, $index - 1));
                    if ($c == 32 || $c == 9) {
                        for ($j = ($index - 2); $j >= 0; $j--) {
                            $c = ord(utf8_substr($string, $j, 1));
                            $score -= (($c == 32 || $c == 9) ? 1 : 0.15);
                        }
                    } else {
                        $score -= $index;
                    }
                }
                $score += $remaining_score * utf8_strlen($next_string);
                $score /= utf8_strlen($string);

                return $score;
            }
        }

        return 0.0;
    }
}
