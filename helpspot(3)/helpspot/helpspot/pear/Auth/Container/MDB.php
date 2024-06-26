<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

/**
 * Storage driver for use against PEAR MDB.
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
 * @author     Lorenzo Alberton <l.alberton@quipo.it>
 * @author     Adam Ashley <aashley@php.net>
 * @copyright  2001-2006 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    CVS: $Id: MDB.php 256753 2008-04-04 07:57:02Z aashley $
 * @link       http://pear.php.net/package/Auth
 * @since      File available since Release 1.2.3
 */

/**
 * Include Auth_Container base class.
 */
require_once 'Auth/Container.php';
/**
 * Include PEAR MDB package.
 */
require_once 'MDB.php';

/**
 * Storage driver for fetching login data from a database.
 *
 * This storage driver can use all databases which are supported
 * by the PEAR MDB abstraction layer to fetch login data.
 *
 * @category   Authentication
 * @author     Lorenzo Alberton <l.alberton@quipo.it>
 * @author     Adam Ashley <aashley@php.net>
 * @copyright  2001-2006 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@  File: $Revision: 256753 $
 * @link       http://pear.php.net/package/Auth
 * @since      Class available since Release 1.2.3
 */
class Auth_Container_MDB extends Auth_Container
{
    // {{{ properties

    /**
     * Additional options for the storage container.
     * @var array
     */
    public $options = [];

    /**
     * MDB object.
     * @var object
     */
    public $db = null;

    public $dsn = '';

    /**
     * User that is currently selected from the DB.
     * @var string
     */
    public $activeUser = '';

    // }}}
    // {{{ Auth_Container_MDB() [constructor]

    /**
     * Constructor of the container class.
     *
     * Initate connection to the database via PEAR::MDB
     *
     * @param  string Connection data or MDB object
     * @return object Returns an error object if something went wrong
     */
    public function Auth_Container_MDB($dsn)
    {
        $this->_setDefaults();

        if (is_array($dsn)) {
            $this->_parseOptions($dsn);
            if (empty($this->options['dsn'])) {
                PEAR::raiseError('No connection parameters specified!');
            }
        } else {
            $this->options['dsn'] = $dsn;
        }
    }

    // }}}
    // {{{ _connect()

    /**
     * Connect to database by using the given DSN string.
     *
     * @param  mixed DSN string | array | mdb object
     * @return mixed  Object on error, otherwise bool
     */
    public function _connect($dsn)
    {
        $this->log('Auth_Container_MDB::_connect() called.', AUTH_LOG_DEBUG);
        if (is_string($dsn) || is_array($dsn)) {
            $this->db = &MDB::connect($dsn, $this->options['db_options']);
        } elseif (is_subclass_of($dsn, 'mdb_common')) {
            $this->db = $dsn;
        } elseif (is_object($dsn) && MDB::isError($dsn)) {
            return PEAR::raiseError($dsn->getMessage(), $dsn->code);
        } else {
            return PEAR::raiseError('The given dsn was not valid in file '.__FILE__.' at line '.__LINE__,
                                    41,
                                    PEAR_ERROR_RETURN,
                                    null,
                                    null
                                    );
        }

        if (MDB::isError($this->db) || PEAR::isError($this->db)) {
            return PEAR::raiseError($this->db->getMessage(), $this->db->code);
        }

        if ($this->options['auto_quote']) {
            if (strpos('.', $this->options['table']) === false) {
                $this->options['final_table'] = $this->db->quoteIdentifier($this->options['table']);
            } else {
                $t = explode('.', $this->options['table']);
                for ($i = 0, $count = count($t); $i < $count; $i++) {
                    $t[$i] = $this->db->quoteIdentifier($t[$i]);
                }
                $this->options['final_table'] = implode('.', $t);
            }
            $this->options['final_usernamecol'] = $this->db->quoteIdentifier($this->options['usernamecol']);
            $this->options['final_passwordcol'] = $this->db->quoteIdentifier($this->options['passwordcol']);
        } else {
            $this->options['final_table'] = $this->options['table'];
            $this->options['final_usernamecol'] = $this->options['usernamecol'];
            $this->options['final_passwordcol'] = $this->options['passwordcol'];
        }

        return true;
    }

    // }}}
    // {{{ _prepare()

    /**
     * Prepare database connection.
     *
     * This function checks if we have already opened a connection to
     * the database. If that's not the case, a new connection is opened.
     *
     * @return mixed True or a MDB error object.
     */
    public function _prepare()
    {
        if (is_subclass_of($this->db, 'mdb_common')) {
            return true;
        }

        return $this->_connect($this->options['dsn']);
    }

    // }}}
    // {{{ query()

    /**
     * Prepare query to the database.
     *
     * This function checks if we have already opened a connection to
     * the database. If that's not the case, a new connection is opened.
     * After that the query is passed to the database.
     *
     * @param  string Query string
     * @return mixed  a MDB_result object or MDB_OK on success, a MDB
     *                or PEAR error on failure
     */
    public function query($query)
    {
        $this->log('Auth_Container_MDB::query() called.', AUTH_LOG_DEBUG);
        $err = $this->_prepare();
        if ($err !== true) {
            return $err;
        }

        return $this->db->query($query);
    }

    // }}}
    // {{{ _setDefaults()

    /**
     * Set some default options.
     *
     * @return void
     */
    public function _setDefaults()
    {
        $this->options['table'] = 'auth';
        $this->options['usernamecol'] = 'username';
        $this->options['passwordcol'] = 'password';
        $this->options['dsn'] = '';
        $this->options['db_fields'] = '';
        $this->options['cryptType'] = 'md5';
        $this->options['db_options'] = [];
        $this->options['db_where'] = '';
        $this->options['auto_quote'] = true;
    }

    // }}}
    // {{{ _parseOptions()

    /**
     * Parse options passed to the container class.
     *
     * @param  array
     */
    public function _parseOptions($array)
    {
        foreach ($array as $key => $value) {
            if (isset($this->options[$key])) {
                $this->options[$key] = $value;
            }
        }
    }

    // }}}
    // {{{ _quoteDBFields()

    /**
     * Quote the db_fields option to avoid the possibility of SQL injection.
     *
     * @return string A properly quoted string that can be concatenated into a
     * SELECT clause.
     */
    public function _quoteDBFields()
    {
        if (isset($this->options['db_fields'])) {
            if (is_array($this->options['db_fields'])) {
                if ($this->options['auto_quote']) {
                    $fields = [];
                    foreach ($this->options['db_fields'] as $field) {
                        $fields[] = $this->db->quoteIdentifier($field);
                    }

                    return implode(', ', $fields);
                } else {
                    return implode(', ', $this->options['db_fields']);
                }
            } else {
                if (strlen($this->options['db_fields']) > 0) {
                    if ($this->options['auto_quote']) {
                        return $this->db->quoteIdentifier($this->options['db_fields']);
                    } else {
                        return $this->options['db_fields'];
                    }
                }
            }
        }

        return '';
    }

    // }}}
    // {{{ fetchData()

    /**
     * Get user information from database.
     *
     * This function uses the given username to fetch
     * the corresponding login data from the database
     * table. If an account that matches the passed username
     * and password is found, the function returns true.
     * Otherwise it returns false.
     *
     * @param   string Username
     * @param   string Password
     * @param   bool If true password is secured using a md5 hash
     *                  the frontend and auth are responsible for making sure the container supports
     *                  challenge response password authentication
     * @return  mixed  Error object or boolean
     */
    public function fetchData($username, $password, $isChallengeResponse = false)
    {
        $this->log('Auth_Container_MDB::fetchData() called.', AUTH_LOG_DEBUG);
        // Prepare for a database query
        $err = $this->_prepare();
        if ($err !== true) {
            return PEAR::raiseError($err->getMessage(), $err->getCode());
        }

        //Check if db_fields contains a *, if so assume all columns are selected
        if (is_string($this->options['db_fields'])
            && strstr($this->options['db_fields'], '*')) {
            $sql_from = '*';
        } else {
            $sql_from = $this->options['final_usernamecol'].
                ', '.$this->options['final_passwordcol'];

            if (strlen($fields = $this->_quoteDBFields()) > 0) {
                $sql_from .= ', '.$fields;
            }
        }

        $query = sprintf('SELECT %s FROM %s WHERE %s = %s',
                         $sql_from,
                         $this->options['final_table'],
                         $this->options['final_usernamecol'],
                         $this->db->getTextValue($username)
                         );

        // check if there is an optional parameter db_where
        if ($this->options['db_where'] != '') {
            // there is one, so add it to the query
            $query .= ' AND '.$this->options['db_where'];
        }

        $this->log('Running SQL against MDB: '.$query, AUTH_LOG_DEBUG);

        $res = $this->db->getRow($query, null, null, null, MDB_FETCHMODE_ASSOC);

        if (MDB::isError($res) || PEAR::isError($res)) {
            return PEAR::raiseError($res->getMessage(), $res->getCode());
        }
        if (! is_array($res)) {
            $this->activeUser = '';

            return false;
        }

        // Perform trimming here before the hashing
        $password = trim($password, "\r\n");
        $res[$this->options['passwordcol']] = trim($res[$this->options['passwordcol']], "\r\n");

        // If using Challenge Response md5 the pass with the secret
        if ($isChallengeResponse) {
            $res[$this->options['passwordcol']] =
                md5($res[$this->options['passwordcol']].$this->_auth_obj->session['loginchallenege']);
            // UGLY cannot avoid without modifying verifyPassword
            if ($this->options['cryptType'] == 'md5') {
                $res[$this->options['passwordcol']] = md5($res[$this->options['passwordcol']]);
            }
        }

        if ($this->verifyPassword($password,
                                  $res[$this->options['passwordcol']],
                                  $this->options['cryptType'])) {
            // Store additional field values in the session
            foreach ($res as $key => $value) {
                if ($key == $this->options['passwordcol'] ||
                    $key == $this->options['usernamecol']) {
                    continue;
                }

                $this->log('Storing additional field: '.$key, AUTH_LOG_DEBUG);
                // Use reference to the auth object if exists
                // This is because the auth session variable can change so a static
                // call to setAuthData does not make sense
                $this->_auth_obj->setAuthData($key, $value);
            }

            return true;
        }

        $this->activeUser = $res[$this->options['usernamecol']];

        return false;
    }

    // }}}
    // {{{ listUsers()

    /**
     * Returns a list of users from the container.
     *
     * @return mixed array|PEAR_Error
     */
    public function listUsers()
    {
        $this->log('Auth_Container_MDB::listUsers() called.', AUTH_LOG_DEBUG);
        $err = $this->_prepare();
        if ($err !== true) {
            return PEAR::raiseError($err->getMessage(), $err->getCode());
        }

        $retVal = [];

        //Check if db_fields contains a *, if so assume all columns are selected
        if (is_string($this->options['db_fields'])
            && strstr($this->options['db_fields'], '*')) {
            $sql_from = '*';
        } else {
            $sql_from = $this->options['final_usernamecol']
                .', '.$this->options['final_passwordcol'];

            if (strlen($fields = $this->_quoteDBFields()) > 0) {
                $sql_from .= ', '.$fields;
            }
        }

        $query = sprintf('SELECT %s FROM %s',
                         $sql_from,
                         $this->options['final_table']
                         );

        // check if there is an optional parameter db_where
        if ($this->options['db_where'] != '') {
            // there is one, so add it to the query
            $query .= ' WHERE '.$this->options['db_where'];
        }

        $this->log('Running SQL against MDB: '.$query, AUTH_LOG_DEBUG);

        $res = $this->db->getAll($query, null, null, null, MDB_FETCHMODE_ASSOC);

        if (MDB::isError($res)) {
            return PEAR::raiseError($res->getMessage(), $res->getCode());
        } else {
            foreach ($res as $user) {
                $user['username'] = $user[$this->options['usernamecol']];
                $retVal[] = $user;
            }
        }
        $this->log('Found '.count($retVal).' users.', AUTH_LOG_DEBUG);

        return $retVal;
    }

    // }}}
    // {{{ addUser()

    /**
     * Add user to the storage container.
     *
     * @param  string Username
     * @param  string Password
     * @param  mixed  Additional information that are stored in the DB
     *
     * @return mixed True on success, otherwise error object
     */
    public function addUser($username, $password, $additional = '')
    {
        $this->log('Auth_Container_MDB::addUser() called.', AUTH_LOG_DEBUG);
        $err = $this->_prepare();
        if ($err !== true) {
            return PEAR::raiseError($err->getMessage(), $err->getCode());
        }

        if (isset($this->options['cryptType']) && $this->options['cryptType'] == 'none') {
            $cryptFunction = 'strval';
        } elseif (isset($this->options['cryptType']) && function_exists($this->options['cryptType'])) {
            $cryptFunction = $this->options['cryptType'];
        } else {
            $cryptFunction = 'md5';
        }

        $password = $cryptFunction($password);

        $additional_key = '';
        $additional_value = '';

        if (is_array($additional)) {
            foreach ($additional as $key => $value) {
                if ($this->options['auto_quote']) {
                    $additional_key .= ', '.$this->db->quoteIdentifier($key);
                } else {
                    $additional_key .= ', '.$key;
                }
                $additional_value .= ', '.$this->db->getTextValue($value);
            }
        }

        $query = sprintf('INSERT INTO %s (%s, %s%s) VALUES (%s, %s%s)',
                         $this->options['final_table'],
                         $this->options['final_usernamecol'],
                         $this->options['final_passwordcol'],
                         $additional_key,
                         $this->db->getTextValue($username),
                         $this->db->getTextValue($password),
                         $additional_value
                         );

        $this->log('Running SQL against MDB: '.$query, AUTH_LOG_DEBUG);

        $res = $this->query($query);

        if (MDB::isError($res)) {
            return PEAR::raiseError($res->getMessage(), $res->code);
        }

        return true;
    }

    // }}}
    // {{{ removeUser()

    /**
     * Remove user from the storage container.
     *
     * @param  string Username
     *
     * @return mixed True on success, otherwise error object
     */
    public function removeUser($username)
    {
        $this->log('Auth_Container_MDB::removeUser() called.', AUTH_LOG_DEBUG);
        $err = $this->_prepare();
        if ($err !== true) {
            return PEAR::raiseError($err->getMessage(), $err->getCode());
        }

        $query = sprintf('DELETE FROM %s WHERE %s = %s',
                         $this->options['final_table'],
                         $this->options['final_usernamecol'],
                         $this->db->getTextValue($username)
                         );

        // check if there is an optional parameter db_where
        if ($this->options['db_where'] != '') {
            // there is one, so add it to the query
            $query .= ' AND '.$this->options['db_where'];
        }

        $this->log('Running SQL against MDB: '.$query, AUTH_LOG_DEBUG);

        $res = $this->query($query);

        if (MDB::isError($res)) {
            return PEAR::raiseError($res->getMessage(), $res->code);
        }

        return true;
    }

    // }}}
    // {{{ changePassword()

    /**
     * Change password for user in the storage container.
     *
     * @param string Username
     * @param string The new password (plain text)
     */
    public function changePassword($username, $password)
    {
        $this->log('Auth_Container_MDB::changePassword() called.', AUTH_LOG_DEBUG);
        $err = $this->_prepare();
        if ($err !== true) {
            return PEAR::raiseError($err->getMessage(), $err->getCode());
        }

        if (isset($this->options['cryptType']) && $this->options['cryptType'] == 'none') {
            $cryptFunction = 'strval';
        } elseif (isset($this->options['cryptType']) && function_exists($this->options['cryptType'])) {
            $cryptFunction = $this->options['cryptType'];
        } else {
            $cryptFunction = 'md5';
        }

        $password = $cryptFunction($password);

        $query = sprintf('UPDATE %s SET %s = %s WHERE %s = %s',
                         $this->options['final_table'],
                         $this->options['final_passwordcol'],
                         $this->db->getTextValue($password),
                         $this->options['final_usernamecol'],
                         $this->db->getTextValue($username)
                         );

        // check if there is an optional parameter db_where
        if ($this->options['db_where'] != '') {
            // there is one, so add it to the query
            $query .= ' AND '.$this->options['db_where'];
        }

        $this->log('Running SQL against MDB: '.$query, AUTH_LOG_DEBUG);

        $res = $this->query($query);

        if (MDB::isError($res)) {
            return PEAR::raiseError($res->getMessage(), $res->code);
        }

        return true;
    }

    // }}}
    // {{{ supportsChallengeResponse()

    /**
     * Determine if this container supports
     * password authentication with challenge response.
     *
     * @return bool
     */
    public function supportsChallengeResponse()
    {
        return in_array($this->options['cryptType'], ['md5', 'none', '']);
    }

    // }}}
    // {{{ getCryptType()

    /**
     * Returns the selected crypt type for this container.
     *
     * @return string Function used to crypt the password
     */
    public function getCryptType()
    {
        return $this->options['cryptType'];
    }

    // }}}
}
