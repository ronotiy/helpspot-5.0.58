<?php

/**
 * Helper functions for the template.
 */
class PortalHelper
{
    /**
     * CSS class to make on/off nav items.
     */
    public $navoff = 'navOff';

    public $navon = 'navOn';

    /**
     * Private vars.
     */
    public $_page;

    public $_id;

    public $_time;

    public $_ip;

    public $_currentrow = '';

    /*
    * Visitor cookie info
    */

    public $visitor = [];

    /**
     * Constructor.
     *
     *
     * @param none
     *
     * @return none
     */
    public function __construct($args)
    {
        //PAGE INFO
        $this->_page = $args['page'];
        $this->_id = $args['id'];

        //SPAM Protection
        $this->_time = time();
        $this->_ip = hs_clientIP();
    }

    /**
     * Sets the value of a public property.
     *
     *
     * @param string $key The name of the property to set.
     *
     * @param mixed $val The new value for the property.
     *
     * @return void
     */
    public function set($key, $val)
    {
        if (substr($key, 0, 1) != '_' && isset($this->$key)) {
            $this->$key = $val;
        }
    }

    /**
     * Switch the class between navOff and navOn.
     *
     *
     * @param none
     *
     * @return number count
     */
    public function ns($navItem)
    {
        if ($navItem == $GLOBALS['navOn']) {
            return $this->navon;
        } else {
            return $this->navoff;
        }
    }

    /**
     * Switch between on and off rows.
     *
     *
     * @param none
     *
     * @return number count
     */
    public function altrow($on, $off)
    {
        if ($this->_currentrow == $on) {
            $this->_currentrow = $off;

            return $off;
        } else {
            $this->_currentrow = $on;

            return $on;
        }
    }

    /**
     * Reset altrow flag.
     *
     *
     * @param none
     *
     * @return none
     */
    public function reset_altrow()
    {
        $this->_currentrow = '';
    }

    /**
     * Switch between on and off rows.
     *
     *
     * @param none
     *
     * @return number count
     */
    public function mimeimg($mime)
    {
        return hs_showMimePortal($mime);
    }

    /**
     * Redirect.
     *
     *
     * @param new url
     *
     * @return none
     */
    public function redirect($url)
    {
        hs_redirect('Location: '.$url);
    }

    /**
     * Determine if visitor has voted on a page.
     *
     *
     * @param none
     *
     * @return bool
     */
    public function hasvoted($pageid)
    {
        $votehistory = isset($_COOKIE['votehistory']) ? hs_unserialize($_COOKIE['votehistory']) : [];
        if (in_array($pageid, $votehistory)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Format date.
     *
     *
     * @param timestamp
     *
     * @return string
     */
    public function shortDateFormat($ts)
    {
        return hs_showShortDate($ts);
    }

    /**
     * Format date.
     *
     *
     * @param timestamp
     *
     * @return string
     */
    public function longDateFormat($ts)
    {
        return hs_showDate($ts);
    }

    public function RSSDate($ts)
    {
        return RFCDate($ts);
    }

    /**
     * Show an error.
     *
     *
     * @param string field name
     *
     * @return string
     */
    public function showError($field, $append = '<br />')
    {
        if (session()->has('errors-'.$field)) {
            return '<span class="error">'.session('errors-'.$field).'</span>'.$append;
        } elseif (isset($GLOBALS['errors'][$field])) {
            return '<span class="error">'.$GLOBALS['errors'][$field].'</span>'.$append;
        } else {
            return '';
        }
    }

    /**
     * Return HTML for spam protection hidden form fields.
     *
     *
     * @param none
     *
     * @return string
     */
    public function getSPAMCheckFields()
    {
        $out = '<input type="hidden" name="hs_fv_timestamp" value="'.$this->getTime().'" />
				<input type="hidden" name="hs_fv_ip" value="'.$this->getVisitorIP().'" />
				<input type="hidden" name="hs_fv_hash" value="'.$this->getFormHash().'" />';

        return $out;
    }

    /**
     * Return HTML for drill down custom fields.
     *
     *
     * @param none
     *
     * @return string
     */
    public function getDrillDownField($field, $sep = ' ')
    {
        //$out = renderCustomFields($_REQUEST, array($field), false, false, false, $sep);
        $fid = 'Custom'.$field['fieldID'];
        $fm = $_REQUEST[$fid];
        $drilldown_array = $field['listItems'];

        //Create array of selected values
        if (! hs_empty($fm)) {
            $keys = [];
            $depth = find_max_array_depth($drilldown_array);	//Find number of select boxes
            $values = explode('#-#', $fm);						//Create array out of selected values string
            $values = array_pad($values, $depth, '');				//Fill values array full to start, this is important since values are stored in a way that only the selected values are kept. So a 4 tier list if only the first 2 are selected only they are stored so the array would be short if we didn't fill it
            for ($i = 1; $i <= $depth; $i++) {
                $keys[] = $fid.'_'.$i;
            }	//Create keys array with name of each select box
            $values = array_combine($keys, $values);				//Combine keys with selected values
        } else {
            $values = [];
        }

        $out .= RenderDrillDownList($field['fieldID'], $drilldown_array, $values, $sep);

        return $out;
    }

    /**
     * Return text of drill down custom fields.
     *
     *
     * @param string
     *
     * @return string
     */
    public function showDrillDownField($string)
    {
        return cfDrillDownFormat($string);
    }

    /**
     * Return HTML for regex custom fields.
     *
     *
     * @param none
     *
     * @return string
     */
    public function getRegexField($field)
    {
        $fid = 'Custom'.$field['fieldID'];
        $out = '<input name="'.$fid.'" id="'.$fid.'" type="text" size="30" value="'.formClean($_REQUEST[$fid]).'">
				 <img src="'.static_url().'/static/img5/'.($field['isRequired'] ? 'remove.svg' : 'match.svg').'" style="height:28px;width:28px;" id="regex_img_'.$fid.'" align="top" border="0" alt="" />
				 <script type="text/javascript">
				 Event.observe("'.$fid.'", "keyup", function(event){ if('.hs_jshtmlentities($field['sRegex']).'.test($("'.$fid.'").value)){ $("regex_img_'.$fid.'").src="'.static_url().'/static/img5/match.svg"; }else{ $("regex_img_'.$fid.'").src="'.static_url().'/static/img5/remove.svg"; } });
				 </script>';

        return $out;
    }

    /**
     * Return HTML for date fields.
     *
     *
     * @param none
     *
     * @return string
     */
    public function getDateField($field)
    {
        $fid = 'Custom'.$field['fieldID'];
        $date = (isset($_REQUEST[$fid]) && is_numeric($_REQUEST[$fid]) ? $_REQUEST[$fid] : '');

        $out = calinput($fid, $date);

        return $out;
    }

    /**
     * Return HTML for date time fields.
     *
     *
     * @param none
     *
     * @return string
     */
    public function getDateTimeField($field)
    {
        $fid = 'Custom'.$field['fieldID'];
        $date = (isset($_REQUEST[$fid]) && is_numeric($_REQUEST[$fid]) ? $_REQUEST[$fid] : '');

        $out = calinput($fid, $date, true);

        return $out;
    }

    /**
     * Return visitor IP.
     *
     *
     * @param none
     *
     * @return string
     */
    public function getVisitorIP()
    {
        return $this->_ip;
    }

    /**
     * Return time.
     *
     *
     * @param none
     *
     * @return int
     */
    public function getTime()
    {
        return $this->_time;
    }

    /**
     * Return hash of time and IP for spam protection.
     *
     *
     * @param none
     *
     * @return string
     */
    public function getFormHash()
    {
        return md5($this->_ip.$this->_time.'R5 4239a aASf fasd');
    }

    /**
     * Return if the visitor is logged in to the request history area.
     *
     *
     * @param none
     *
     * @return string
     */
    public function isLoggedIntoRequestHistory()
    {
        return session()->has('login_sEmail');
    }

    /**
     * Return the reCaptcha HTML.
     *
     *
     * @param none
     *
     * @return string
     */
    public function reCaptcha($lang = 'en')
    {
        $output = '';

        //Find errors if any
        $error = isset($GLOBALS['errors']['recaptcha']) ? $GLOBALS['errors']['recaptcha'] : null;

        if ($error) {
            $output .= '<div class="hs_portal_captcha_error error"><p>'.$error.'</p></div>';
        }

        $output .= '<div class="g-recaptcha" data-sitekey="'.hs_setting('cHD_RECAPTCHA_PUBLICKEY').'"></div>
<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl='.$lang.'" ></script>';

        return $output;
    }

    /**
     * Encode text to make it hard on spam bots. Mostly used to gaurd captcha but could have other uses.
     *
     *
     * @param content to encode
     * @param character adjustment
     *
     * @return string
     */
    public function encodeText($content, $chr_adjust = -1)
    {
        $encoded_content = '';
        for ($n = 0; $n < strlen($content); $n++) {
            $encoded_content .= dechex(ord(substr($content, $n, 1)) + $chr_adjust); // + $chr_adjust
        }

        $rdm_function_name = '';
        while (strlen($rdm_function_name) < 8) {
            $tmp = rand(65, 122);
            if ($tmp > 96 || $tmp < 91) {
                $rdm_function_name .= chr($tmp);
            }
        }

        $js_function = 'function '.$rdm_function_name."(e) { for (i = 0; i <= e.length; i+=2) { document.write(String.fromCharCode((parseInt((('0x') + e.substring(i,i+2)),16)) - (".$chr_adjust.')));}}';

        return '<script>'.$js_function."\n".$rdm_function_name."('".$encoded_content."');</script>";
    }

    /**
     * Footer Credit Link.
     */
    public function footerCredit()
    {
        return '<a href="https://www.helpspot.com/help-desk-software">Help Desk Software</a> by HelpSpot';
    }
}
