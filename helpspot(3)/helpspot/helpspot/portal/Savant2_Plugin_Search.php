<?php

/**
 * Base plugin class.
 */
require_once 'Savant2/Plugin.php';

/**
 * Get the latest forum topics and return as array.
 */
class Savant2_Plugin_Search extends Savant2_Plugin
{
    /**
     * Store result set here.
     *
     *
     * @var ADODB Record Set
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
     * Return rs of results.
     *
     *
     * @param $q
     * @param $area
     * @param string $from
     * @return void of results
     */
    public function _doSearch($q, $area, $from = 'search')
    {
        $args['q'] = $q;
        $args['area'] = 'kb'; // Knowledge Books only

        $search = apiKbSearch($args, $advanced = false);
        $this->rs = $search;

        $this->_recordSearch($args['q'], $from, $area, count($this->rs));

        $this->rs = $this->_filterResults($this->rs, $area);
    }

    public function _recordSearch($q, $from, $area, $totalResults)
    {
        if ($from == 'search' && ! empty($q)) {
            $sFromPage = ($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            $GLOBALS['DB']->Execute('INSERT INTO HS_Search_Queries(dtGMTPerformed,sSearch,sFromPage,sSearchType,iResultCount,xPortal)
                VALUES (?,?,?,?,?,?)', [time(), $q, $sFromPage, $area, $totalResults, (isset($GLOBALS['hs_multiportal']) ? $GLOBALS['hs_multiportal']->xPortal : 0)]);
        }
    }

    public function _filterResults($results, $area)
    {
        //If we're in a remote portal filter off any forums/books not for this portal
        if (isset($GLOBALS['hs_multiportal'])) {
            $compareArray = $GLOBALS['hs_multiportal']->kbs;

            foreach ($results as $key => $row) {
                $id = (isset($row['xBook'])) ? $row['xBook'] : $row['chapter']['book']['xBook'];

                if (! in_array($id, $compareArray)) {
                    unset($results[$key]);
                }
            }
        }

        return $results;
    }

    /**
     * Return array of results.
     *
     *
     * @param none
     *
     * @return array of results
     */
    public function search($q, $area = 'all')
    {
        $this->_doSearch($q, $area);

        return $this->rs;
    }

    /**
     * Return record count of search results.
     *
     *
     * @param none
     *
     * @return number count
     */
    public function count($q, $area = 'all')
    {
        $this->_doSearch($q, $area, 'count');

        return count($this->rs);
    }
}
