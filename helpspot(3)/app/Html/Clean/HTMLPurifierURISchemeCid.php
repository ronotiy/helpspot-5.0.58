<?php

namespace HS\Html\Clean;

class HTMLPurifierURISchemeCid extends \HTMLPurifier_URIScheme
{
    public $default_port = null;

    public $browsable = true;

    public $hierarchical = false;

    public function validate(&$uri, $config, $context)
    {
        if ($uri->scheme !== 'cid') {
            return false;
        }

        return $this->doValidate($uri, $config, $context);
    }

    /**
     * Validates the components of a URI for a specific scheme.
     * @param HTMLPurifier_URI $uri Reference to a HTMLPurifier_URI object
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return bool success or failure
     */
    public function doValidate(&$uri, $config, $context)
    {
        if ($uri->scheme !== 'cid') {
            return false;
        }

        return true;
    }
}
