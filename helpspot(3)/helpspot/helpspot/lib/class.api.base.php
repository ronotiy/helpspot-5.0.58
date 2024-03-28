<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

class api
{
    //Is this the public or private API
    public $api = '';

    //Version of the API to use
    public $api_version = '2.0';

    //Minimumn version of the API supported. Clients should check this to confirm they can work.
    public $api_min_version = '1.7';

    //Valid methods, ones which can be publicly called.
    public $valid_methods = [];

    //Method which is being called
    public $method = '';

    //Return type requested
    public $output_type = 'xml';

    //Array of errors, both numbers and descriptions
    public $error_stack = [];

    //Result set
    public $result = [];

    //The user making the request (only using in private api)
    public $user = '';

    //The root tag for XML return types
    public $root_tag = 'results';

    //The default XML tag for numeric index arrays
    public $default_tag = 'default_key';

    //Success header
    public $success_header = '200 OK';

    //The error header to send back with an error
    public $error_header = '400 Bad Request';

    /**
     * Constructor.
     */
    public function __construct()
    {
        //Setup the environment
        $this->_enviro();

        //Set HelpSpot API header
        header('X-HelpSpot-API: '.$this->api_version);

        //Do authentication of user if a private api request
        if ($this->api == 'private') {
            // If bearer auth is attempted
            if (request()->bearerToken()) {
                $attemptedUser = auth('sanctum')->user();

                if (! $attemptedUser) {
                    $this->_error(002);
                } else {
                    $this->user = apiGetUserById($attemptedUser->xPerson);
                    global $user;
                    $user = $this->user;
                }
            } elseif (! $this->authIsSAML()) // Only present basic auth if not using SAML
            {
                // Else present basic auth mechanism
                // Note: This only works with auth: internal, blackbox, ldap
                //       It will not work with SAML/SSO
                list($apiuser, $apipass) = getBasicAuth();

                if (isset($_GET['username']) && isset($_GET['password'])) {	//Passed via URL
                    $apiuser = $_GET['username'];
                    $apipass = $_GET['password'];
                }

                if (! $apiuser && ! $apipass) {
                    $this->_error(001);

                    //Send HTTP basic auth header
                    header('WWW-Authenticate: Basic realm="API Authentication"');
                    $this->error_header = '401 Unauthorized';
                    //header('HTTP/1.0 401 Unauthorized');
                }

                if (auth()->once([$this->authUserField() => $apiuser, 'password' => $apipass])) {
                    $attemptedUser = auth()->user();
                    $this->user = apiGetUserById($attemptedUser->xPerson);
                    global $user;
                    $user = $this->user;
                } else {
                    $this->_error(002);
                }
            } else {
                $this->_error(002);
            }
        }

        //Set the method being called
        $this->method = $_REQUEST['method'];

        //Find output type, if no matches then the default of xml will be used
        switch ($_REQUEST['output']) {
            case 'json':
                $this->output_type = 'json';

                break;
            case 'php':
                $this->output_type = 'php';

                break;
        }
    }

    public function authIsSAML()
    {
        return hs_setting('cAUTHTYPE', 'internal') == 'saml';
    }

    public function authUserField()
    {
        // If we're using internal auth
        if (hs_setting('cAUTHTYPE', 'internal') == 'internal'
            // Or if we're using saml, and debug is enabled, then we'll assume reaching here
            // is when using the /altlogin form to use internal auth as part of debugging SAML auth
            || (config('app.debug') && hs_setting('cAUTHTYPE', 'internal') == 'saml') ) {
            return 'sEmail';
        }

        return 'sUsername';
    }

    /**
     * Process the request, calls the API method.
     */
    public function process()
    {
        //Add base functions to this list
        array_push($this->valid_methods, 'version');

        //Protect private API methods, only call method if not errors previously set
        if (! in_array($this->method, $this->valid_methods)) {
            $this->_error(201);
        } elseif (empty($this->error_stack)) {
            $method = str_replace('.', '_', $this->method);
            $this->$method();
        }

        $this->_output();
    }

    /**
     * Output the results of the API call.
     */
    public function _output()
    {

        //Process if no errors, else return errors
        if (empty($this->error_stack)) {
            header('HTTP/1.1 '.$this->success_header);
            $out = $this->result;
        } else {
            //Set error header and error root tag
            header('HTTP/1.1 '.$this->error_header);
            $this->root_tag = 'errors';
            foreach ($this->error_stack as $k=>$error) {
                $output_array = ['id'=>$k, 'description'=>hs_htmlspecialchars($error['error'])];

                if (is_array($error['fields'])) {
                    // $output_array goes last, to overwrite $error['fields']
                    // in case $error['fields'] contains "id" or "description" array keys
                    $output_array = array_merge($error['fields'], $output_array);
                }

                $out['error'][] = $output_array;
            }
        }

        $output = '_'.$this->output_type;
        echo $this->$output($out);
    }

    /**
     * Render XML output.
     */
    public function _xml($out)
    {

        //xml options
        $options = [
            XML_SERIALIZER_OPTION_INDENT      => "\t",        // indent with tabs
            XML_SERIALIZER_OPTION_LINEBREAKS  => "\n",        // use UNIX line breaks
            XML_SERIALIZER_OPTION_ROOT_NAME   => $this->root_tag,   // root tag
            XML_SERIALIZER_OPTION_DEFAULT_TAG => $this->default_tag,       // tag for values with numeric keys
            XML_SERIALIZER_OPTION_MODE		  => 'simplexml',
            XML_SERIALIZER_OPTION_XML_ENCODING=> 'UTF-8',	//Character set of returned data
        ];

        $serializer = new XML_Serializer($options);

        //Build XML document
        $serializer->serialize($out);

        //Output XML
        header('Content-type: text/xml');

        return XML_Util::getXMLDeclaration('1.0', 'UTF-8')."\n".$serializer->getSerializedData();
    }

    /**
     * Render JSON output.
     */
    public function _json($out)
    {
        header('Content-Type: application/json;');

        //Output JSON
        return json_encode($out);
    }

    /**
     * Render php output.
     */
    public function _php($out)
    {
        header('Content-Type: text/html; charset=UTF-8');

        return serialize($out);
    }

    /**
     * Return a variable passed in via GET.
     */
    public function _GET($var, $default = false)
    {
        //If no default passed in then var is required. If not set then return error
        if (isset($_GET[$var])) {
            return $_GET[$var];
        } elseif (! isset($_GET[$var]) && $default !== false) {
            return $default;
        } else {
            $this->_error(101, $var);

            return false;
        }
    }

    /**
     * Return a variable passed in via POST.
     */
    public function _POST($var, $default = false)
    {
        //If no default passed in then var is required. If not set then return error
        if (isset($_POST[$var]) && ! hs_empty($_POST[$var])) {
            return $_POST[$var];
        } elseif (! isset($_POST[$var]) && $default !== false) {
            return $default;
        } else {
            $this->_error(101, $var);

            return false;
        }
    }

    /**
     * Return an adodb record set as an array, with fields escaped.
     */
    public function _rsToOutputArray(&$rs, $wrapper, $exclude_cols = false, $function = '', $field = '', $params = '')
    {
        $out[$wrapper] = [];
        if (hs_rscheck($rs)) {
            while ($row = $rs->FetchRow()) {
                //If a function is provided then do it
                if (! empty($function)) {
                    $row[$field] = $this->$function($row[$field], $params);
                }

                //Add rows to output array
                if ($exclude_cols == false) {
                    $out[$wrapper][] = $row;
                } else {
                    $out[$wrapper][] = $this->_stripColsFromArray($row, $exclude_cols);
                }
            }
        }

        return $out;
    }

    /**
     * Strip columns from an array.
     */
    public function _stripColsFromArray($array, $exclude_cols)
    {
        foreach ($array as $key=>$value) {
            if (in_array($key, $exclude_cols)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Take a PHP serialized array, unserialize and turn into a comma separated list.
     */
    public function _serializedToList(&$data, $tag)
    {
        return [$tag=>hs_unserialize($data)];
    }

    /**
     * Error codes, set an error.
     */
    public function _error($code, $text = '', $extraFields = [])
    {
        $errors = [];

        //Authentication errors
        $errors[001] = 'Authentication information not sent';
        $errors[002] = 'User authentication failed';
        $errors[003] = 'Too many failed login attempts. Please wait a minute and try again.';

        //Parameter errors
        $errors[101] = sprintf('Required parameter %s not supplied', $text);
        $errors[102] = 'Valid access key required';
        $errors[103] = 'Valid request ID required';
        $errors[104] = 'Email or password not valid';
        $errors[105] = 'Valid email required';
        $errors[106] = 'Request history limit reached for this request';

        //Processing errors
        $errors[201] = 'Invalid method provided';
        $errors[202] = 'Could not create topic';
        $errors[203] = 'Forum is closed';
        $errors[204] = 'Could not create post';
        $errors[205] = 'Topic ID does not exist';
        $errors[206] = 'Could not create request';
        $errors[207] = sprintf('Could not create request: %s', $text);
        $errors[208] = 'Request has been merged';
        $errors[209] = 'Time event could not be added';
        $errors[210] = 'Request could not be updated';
        $errors[211] = 'Password could not be updated';
        $errors[212] = sprintf('Could not update request: %s', $text);
        $errors[213] = 'Could not merge requests';
        $errors[214] = 'Could not subscribe to request';
        $errors[214] = 'Could not unsubscribe from request';
        $errors[215] = 'Could not change read state';
        $errors[216] = 'Could not trash request';
        $errors[217] = 'Could not spam the request';

        //System errors
        $errors[301] = 'Trial expired';
        $errors[303] = 'private.customer.getPasswordByEmail has been removed as passwords are no longer stored in a way that are accessible by the system.';

        if (in_array($code, array_keys($errors))) {
            $this->error_stack[$code] = ['error' => $errors[$code], 'fields' => $extraFields];
        }
    }

    /**
     * Return if any errors have been set yest.
     */
    public function in_error()
    {
        return ! empty($this->error_stack);
    }

    /**
     * Setup all the includes and variables needed for base HelpSpot use.
     */
    public function _enviro()
    {
        //Not in portal
        define('IN_PORTAL', ($this->api == 'public' ? 'true' : 'false'));

        //Set for use in public API calls
        $user = [];
        $user['xPerson'] = 0;
        $user['sFname'] = '';
        $user['sLname'] = '';
        $user['sEmail'] = '';

        error_reporting(E_ERROR | E_PARSE);
        set_include_path(cBASEPATH.'/helpspot/pear');

        include_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        include_once cBASEPATH.'/helpspot/lib/util.lib.php';
        include_once cBASEPATH.'/helpspot/lib/error.lib.php';
        include_once cBASEPATH.'/helpspot/lib/platforms.lib.php';
        include_once cBASEPATH.'/helpspot/lib/display.lib.php';
        include_once cBASEPATH.'/helpspot/pear/Crypt_RC4/Rc4.php';
        include_once cBASEPATH.'/helpspot/lib/api.lib.php';
        include_once cBASEPATH.'/helpspot/lib/class.notify.php';
        include_once cBASEPATH.'/helpspot/lib/class.filter.php';
        include_once cBASEPATH.'/helpspot/lib/class.userscape.bayesian.classifier.php';
        include_once cBASEPATH.'/helpspot/lib/class.license.php';
        include_once cBASEPATH.'/helpspot/lib/class.array2recordset.php';
        include_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';

        include_once cBASEPATH.'/helpspot/pear/Serializer.php';
        include_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        include_once cBASEPATH.'/helpspot/lib/class.requestupdate.php';

        include_once cBASEPATH.'/helpspot/adodb/adodb.inc.php';

        include_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';
        include_once cBASEPATH.'/helpspot/lib/api.hdcategories.lib.php';

        include_once cBASEPATH.'/helpspot/lib/class.triggers.php';
        include_once cBASEPATH.'/helpspot/lib/class.business_hours.php';
        include_once cBASEPATH.'/helpspot/lib/class.language.php';

        clean_data();

        //language
        $GLOBALS['lang'] = new language('request');

        //Get License
        $licenseObj = new usLicense(hs_setting('cHD_CUSTOMER_ID'), hs_setting('cHD_LICENSE'), hs_setting('SSKEY'));
        $GLOBALS['license'] = $licenseObj->getLicense();

        //Check for trial expiration
        if (isset($GLOBALS['license']['trial']) && $GLOBALS['license']['trial'] < time()) {
            $this->_error(301);
        }

        $GLOBALS['lang'] = new language($page);
        include_once cBASEPATH.'/helpspot/lib/lookup.lib.php';
    }

    /**
     * Return API version information.
     */
    public function version()
    {
        $this->result = ['version'=>$this->api_version,
                              'min_version'=>$this->api_min_version, ];
    }

    /**
     * Return API version information in private API.
     */
    public function private_version()
    {
        $this->version();
    }
}
