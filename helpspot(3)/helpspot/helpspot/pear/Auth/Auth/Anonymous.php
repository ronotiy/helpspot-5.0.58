<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

/**
 * Anonymous authentication support.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Authentication
 * @author     Yavor Shahpasov <yavo@netsmart.com.cy>
 * @author     Adam Ashley <aashley@php.net>
 * @copyright  2001-2006 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    CVS: $Id: Anonymous.php 289651 2009-10-15 04:39:07Z aashley $
 * @link       http://pear.php.net/package/Auth
 * @since      File available since Release 1.3.0
 */

/**
 * Include Auth package.
 */
require_once 'Auth.php';

/**
 * Anonymous Authentication.
 *
 * This class provides anonymous authentication if username and password
 * were not supplied
 *
 * @category   Authentication
 * @author     Yavor Shahpasov <yavo@netsmart.com.cy>
 * @author     Adam Ashley <aashley@php.net>
 * @copyright  2001-2006 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: 1.6.2  File: $Revision: 289651 $
 * @link       http://pear.php.net/package/Auth
 * @since      Class available since Release 1.3.0
 */
class Auth_Anonymous extends Auth
{
    // {{{ properties

    /**
     * Whether to allow anonymous authentication.
     *
     * @var bool
     */
    public $allow_anonymous = true;

    /**
     * Username to use for anonymous user.
     *
     * @var string
     */
    public $anonymous_username = 'anonymous';

    // }}}
    // {{{ Auth_Anonymous() [constructor]

    /**
     * Pass all parameters to Parent Auth class.
     *
     * Set up the storage driver.
     *
     * @param string    Type of the storage driver
     * @param mixed     Additional options for the storage driver
     *                  (example: if you are using DB as the storage
     *                   driver, you have to pass the dsn string here)
     *
     * @param string    Name of the function that creates the login form
     * @param bool   Should the login form be displayed if necessary?
     * @return void
     * @see Auth::Auth()
     */
    public function __construct($storageDriver, $options = '', $loginFunction = '', $showLogin = true)
    {
        parent::__construct($storageDriver, $options, $loginFunction, $showLogin);
    }

    // }}}
    // {{{ login()

    /**
     * Login function.
     *
     * If no username & password is passed then login as the username
     * provided in $this->anonymous_username else call standard login()
     * function.
     *
     * @return void
     * @see Auth::login()
     */
    public function login()
    {
        if ($this->allow_anonymous
            && empty($this->username)
            && empty($this->password)) {
            $this->setAuth($this->anonymous_username);
            if (is_callable($this->loginCallback)) {
                call_user_func_array($this->loginCallback, [$this->username, $this]);
            }
        } else {
            // Call normal login system
            parent::login();
        }
    }

    // }}}
    // {{{ forceLogin()

    /**
     * Force the user to login.
     *
     * Calling this function forces the user to provide a real username and
     * password before continuing.
     *
     * @return void
     */
    public function forceLogin()
    {
        $this->allow_anonymous = false;
        if (! empty($this->session['username']) && $this->session['username'] == $this->anonymous_username) {
            $this->logout();
        }
    }

    // }}}
}
