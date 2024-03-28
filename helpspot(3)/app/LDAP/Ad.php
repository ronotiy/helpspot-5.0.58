<?php

namespace HS\LDAP;

use Toyota\Component\Ldap\Core\Manager;
use Toyota\Component\Ldap\Platform\Native\Driver;
use Toyota\Component\Ldap\Exception\BindException;
use Toyota\Component\Ldap\Exception\ConnectionException;

class Ad
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Toyota\Component\Ldap\Core\Manager
     */
    protected $manager;

    protected $authenticated = false;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->manager = new Manager($options, new Driver());
    }

    /**
     * @param $user
     * @param $password
     * @return bool
     * @throws @throws Toyota\Component\Ldap\Exception\BindException if binding fails
     */
    public function auth($user, $password)
    {
        $this->manager->connect();
        $this->manager->bind($user, $password);
        $this->authenticated = true;

        return true;
    }

    /**
     * @param $query Of format appropriate for AD search, e.g. "mail=*"
     * @param null|string $dn
     * @param array $fields
     * @param bool $authFirst
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return \Toyota\Component\Ldap\Core\SearchResult
     */
    public function search($query, $dn = null, $fields = ['uid', 'givenname', 'sn', 'mail', 'telephonenumber'], $authFirst = true)
    {
        if ($authFirst && ! $this->authenticated) {
            throw new \Exception("You must authenticate before searching. If you wish to search anonymously, set \$authFirst = false, e.g. -> \$ldap->search(\$query, null, ['uid','givenname', 'sn', 'mail', 'telephonenumber'], \$authFirst = false)");
        }

        if (is_null($dn) && isset($this->options['base_dn'])) {
            $dn = $this->options['base_dn'];
        } elseif (is_null($dn)) {
            throw new \InvalidArgumentException('Base DN not found in options array');
        }

        return $this->manager->search($dn, $query, true, $fields);
    }
}
