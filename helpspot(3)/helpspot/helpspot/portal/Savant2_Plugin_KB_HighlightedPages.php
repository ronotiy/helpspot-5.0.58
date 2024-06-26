<?php

/**
 * Base plugin class.
 */
require_once 'Savant2/Plugin.php';

/**
 * HelpSpot Libraries.
 */
require_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';

/**
 * Get the all the highlighted pages from the DB.
 */
class Savant2_Plugin_KB_HighlightedPages extends Savant2_Plugin
{
    /**
     * Store result set here.
     *
     *
     * @var array
     */
    public $rs = [];

    /**
     * Central switcher API for the the various public methods.
     *
     *
     * @param string $method The public method to call from this class; all
     * additional parameters will be passed to the called method
     *
     * @return string XHTML generated by the public method.
     */
    public function plugin($method)
    {
        // only pass calls to public methods (i.e., no leading underscore)
        if (substr($method, 0, 1) != '_' && method_exists($this, $method)) {

            // get all arguments and drop the first one (the method name)
            $args = func_get_args();
            array_shift($args);

            // call the method, then return the tidied-up XHTML results
            return call_user_func_array([&$this, $method], $args);
        } else {
            return false;
        }
    }

    /**
     * Return highlighted pages.
     *
     *
     * @param none
     *
     * @return array of pages
     */
    public function getHighlightedPages()
    {

        //Get RS if needed or else return
        if (empty($this->rs)) {
            $result = apiGetHighlightedPages();
            $this->rs = rsToArray($result, 'xPage');

            //If we're in a remote portal and books have been specified only keep results for specified books
            if (isset($GLOBALS['hs_multiportal'])) {
                foreach ($this->rs as $id=>$page) {
                    if (! in_array($page['xBook'], $GLOBALS['hs_multiportal']->kbs)) {
                        unset($this->rs[$id]);
                    }
                }
            }
        }

        return $this->rs;
    }

    /**
     * Return record count of highlighted pages in DB.
     *
     *
     * @param none
     *
     * @return number count
     */
    public function count()
    {
        if (empty($this->rs)) {
            $this->getHighlightedPages();
        }

        return count($this->rs);
    }
}
