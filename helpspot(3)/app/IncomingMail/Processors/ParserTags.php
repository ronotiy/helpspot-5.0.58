<?php

namespace HS\IncomingMail\Processors;

use HS\IncomingMail\Message;

class ParserTags
{
    private Message $message;

    /**
     * Attachments constructor.
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @param $body
     * @param string $prefix (cHD_EMAILPREFIX)
     * @return bool|mixed
     */
    public function hs_request_id($body, $prefix = '')
    {
        if (utf8_strpos($body, 'hs_request_id') === false) {
            return false;
        }

        if (preg_match('/##hs_request_id:(.*?)##/', $body, $reqId)) {
            $this->message->setParsedBody(str_replace($reqId[0], '', $body));
            //Strip letters which are a prefix
            return str_replace(trim($prefix), '', $reqId[1]);
        }

        return false;
    }

    /**
     * @return bool|string
     */
    public function hs_customer_id()
    {
        return $this->parseTag('hs_customer_id', $this->message->getParsedBody());
    }

    /**
     * @return bool|string
     */
    public function hs_customer_firstname()
    {
        return $this->parseTag('hs_customer_firstname', $this->message->getParsedBody());
    }

    /**
     * @return bool|string
     */
    public function hs_customer_lastname()
    {
        return $this->parseTag('hs_customer_lastname', $this->message->getParsedBody());
    }

    /**
     * @return bool|string
     */
    public function hs_customer_phone()
    {
        return $this->parseTag('hs_customer_phone', $this->message->getParsedBody());
    }

    /**
     * @return bool|string
     */
    public function hs_customer_email()
    {
        if (preg_match('/##hs_customer_email:(.*?)##/', $this->message->getParsedBody(), $found)) {
            $this->removeFromEmailBody($found[0], $this->message->getParsedBody());
            $email = utf8_trim(strip_tags($found[1]));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return false;
    }

    /**
     * @return bool|string
     */
    public function hs_category()
    {
        if (preg_match('/##hs_category:(.*?)##/', $this->message->getParsedBody(), $category)) {
            $this->removeFromEmailBody('hs_category', $this->message->getParsedBody());
            $foundCat = utf8_trim(strip_tags($category[1]));
            if (apiGetCategory($foundCat)) {
                return $foundCat;
            }
        }

        return false;
    }

    /**
     * @return bool|string
     */
    public function hs_assigned_to()
    {
        if (preg_match('/##hs_assigned_to:(.*?)##/', $this->message->getParsedBody(), $staff)) {
            $staffEmail = utf8_trim(strip_tags($staff[1]));
            if (apiGetUser($staffEmail)) {
                return $staffEmail;
            }
            $this->removeFromEmailBody('hs_assigned_to', $this->message->getParsedBody());
        }

        return false;
    }

    /**
     * @param $key
     * @param $body
     * @return bool|string
     */
    public function parseTag($key, $body)
    {
        if (preg_match('/##'.$key.':(.*?)##/', $body, $found)) {
            $this->removeFromEmailBody($found[0], $body);
            return utf8_trim(strip_tags($found[1]));
        }

        return false;
    }

    /**
     * @param $key
     * @param $body
     * @return mixed
     */
    protected function removeFromEmailBody($key, $body)
    {
        return $this->message->setParsedBody(str_replace($key, '', $body));
    }

    /**
     * @return array
     */
    public function customFields()
    {
        if (empty($GLOBALS['customFields'])) {
            return [];
        }

        $customFieldValues = [];
        $body = $this->message->getParsedBody();

        foreach ($GLOBALS['customFields'] as $v) {
            $id = 'Custom'.$v['fieldID'];

            //Set custom field value for this message
            $customFieldValues[$id] = '';

            //Check for this field
            if (preg_match('/##hs_custom'.$v['fieldID'].':(.*?)##/s', $body, $cf)) {
                if (in_array($v['fieldType'], ['date', 'datetime'])) {
                    if (isset($cf[1]) && ! is_numeric($cf[1])) {
                        $cf[1] = strtotime(trim($cf[1]));
                    }
                }
                //Assign value
                $customFieldValues[$id] = trim(hs_html_entity_decode($cf[1]));
                $this->removeFromEmailBody($cf[0], $body);
            }
        }

        return $customFieldValues;
    }
}
