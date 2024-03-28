<?php

return [
    // Mandatory Configuration Options
    'hosts'            => [],
    'base_dn'          => 'dc=ad,dc=example,dc=org',
    'username'         => '',
    'password'         => '',

    // Optional Configuration Options
    'schema'           => Adldap\Schemas\ActiveDirectory::class,
    'account_prefix'   => '',
    'account_suffix'   => '@ad.example.org',
    'port'             => 389,
    'follow_referrals' => false,
    'use_ssl'          => false,
    'use_tls'          => false,
    'version'          => 3,
    'timeout'          => 5,

    // Custom LDAP Options
    'custom_options'   => [
        // See: http://php.net/ldap_set_option
        // LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_HARD
    ],
];
