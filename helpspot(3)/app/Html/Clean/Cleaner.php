<?php

namespace HS\Html\Clean;

use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_URISchemeRegistry;

class Cleaner implements CleanerInterface
{
    /**
     * Clean HTML via HTML Purifier.
     *
     * @param  string  $input    Raw HTML
     * @param  bool $stripImg Strip images
     * @return string            Clean HTML
     */
    public function clean($input, $stripImg = false)
    {

        // Clean invalid head tags. See #518
        $input = str_replace(['<head/>', '<head />'], '', $input);
        $input = str_replace(['<head>', '</head>'], '', $input);
        $input = str_replace(['<html>', '</html>'], '', $input);
        $input = str_replace(['<body>', '</body>'], '', $input);

        // Clean and remove any hidden RTL stuff. See #301
        $input = str_replace(['&#x202e', '&#8238;'], '', $input);

        $config = HTMLPurifier_Config::createDefault();

        $config->set('HTML.Doctype', 'HTML 4.01 Transitional'); // replace with your doctype
        $config->set('AutoFormat.RemoveEmpty', 1);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', 1);
        $config->set('HTML.TargetBlank', 1);
        $config->set('HTML.ForbiddenAttributes', ['class', 'align']);
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('URI.AllowedSchemes', [
            'cid' => true,
            'http' => true,
            'https' => true,
            'mailto' => true,
            'ftp' => true,
            'nntp' => true,
            'news' => true,
            'data' => true,
            'file' => true,
        ]);
        $config->set('Attr.AllowedFrameTargets', [
           '_blank',
        ]);

        if ($stripImg) {
            $config->set('HTML.ForbiddenElements', 'img');
        }

        HTMLPurifier_URISchemeRegistry::instance()->register('cid', new HTMLPurifierURISchemeCid);

        $purifier = new HTMLPurifier($config);

        return trim($purifier->purify($input));
    }

    /**
     * Test if cleaned email stripped all content.
     * This handles the possibility that HTML still exists,
     * but there's no remaining content within it.
     *
     * @param  string  $content     The content to test
     * @return bool              If there is content left
     */
    public function wasContentStripped($content)
    {
        $remainingContent = trim(strip_tags($content, '<img>'));

        return empty($remainingContent);
    }
}
