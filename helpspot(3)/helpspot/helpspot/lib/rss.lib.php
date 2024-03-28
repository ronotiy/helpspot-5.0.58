<?php

function convert_chars($content, $flag = 'obsolete')
{
    // Translation of invalid Unicode references range to valid range
    $wp_htmltranswinuni = [
    '&#128;' => '&#8364;', // the Euro sign
    '&#129;' => '',
    '&#130;' => '&#8218;', // these are Windows CP1252 specific characters
    '&#131;' => '&#402;',  // they would look weird on non-Windows browsers
    '&#132;' => '&#8222;',
    '&#133;' => '&#8230;',
    '&#134;' => '&#8224;',
    '&#135;' => '&#8225;',
    '&#136;' => '&#710;',
    '&#137;' => '&#8240;',
    '&#138;' => '&#352;',
    '&#139;' => '&#8249;',
    '&#140;' => '&#338;',
    '&#141;' => '',
    '&#142;' => '&#382;',
    '&#143;' => '',
    '&#144;' => '',
    '&#145;' => '&#8216;',
    '&#146;' => '&#8217;',
    '&#147;' => '&#8220;',
    '&#148;' => '&#8221;',
    '&#149;' => '&#8226;',
    '&#150;' => '&#8211;',
    '&#151;' => '&#8212;',
    '&#152;' => '&#732;',
    '&#153;' => '&#8482;',
    '&#154;' => '&#353;',
    '&#155;' => '&#8250;',
    '&#156;' => '&#339;',
    '&#157;' => '',
    '&#158;' => '',
    '&#159;' => '&#376;',
    ];

    // Remove metadata tags
    $content = preg_replace('/<title>(.+?)<\/title>/', '', $content);
    $content = preg_replace('/<category>(.+?)<\/category>/', '', $content);

    // Converts lone & characters into &#38; (a.k.a. &amp;)
    $content = preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $content);

    // Fix Word pasting
    $content = strtr($content, $wp_htmltranswinuni);

    // Just a little XHTML help
    $content = str_replace('<br>', '<br />', $content);
    $content = str_replace('<hr>', '<hr />', $content);

    return $content;
}
