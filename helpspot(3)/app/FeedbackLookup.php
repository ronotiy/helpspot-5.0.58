<?php

namespace HS;


class FeedbackLookup
{
    public static $requestPage = [
        '1' => lg_request_fb_reqadded,
        '2' => lg_request_fb_reqerror,
        '3' => lg_request_fb_requpdatenclosed,
        '5' => lg_request_fb_requpdated,
        '6' => lg_request_fb_subscribed,
        '7' => lg_request_fb_unsubscribed,
        '8' => lg_request_fb_unpublic,
        '9' => lg_request_fb_reqsmerged,
        '10' => lg_request_fb_remdeleted,
        '11' => lg_request_fb_public,
        '12' => lg_request_fb_spam,
    ];

    /**
     * Lookup feedback for a page
     * TODO: This assumes proper language file is loaded already
     * @param $page
     * @param $id
     * @return mixed|null
     * @throws \Exception
     */
    public static function byFb($page, $id)
    {
        $pageLookup = $page.'Page';
        if( ! property_exists(self::class, $pageLookup) ) {
            throw new \Exception("No feedback found for page '$page'");
        }

        return static::$requestPage[(string)$id] ?? null;
    }
}
