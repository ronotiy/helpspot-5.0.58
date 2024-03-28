<?php

// Require class/code autoloader
require_once '../vendor/autoload.php';

// Search from GET URL parameters
$search = parseQuery($_GET);

// Attempt to search, trapping any errors so we can display them
try {
    $params = [
        'hostname'      => 'ldap.example.com',
        'base_dn'       => 'dc=example,dc=com',
        'port'          => 389,
        'options'       => [LDAP_OPT_PROTOCOL_VERSION => 3],
    ];

    $ldap = new HS\LDAP\Ad($params);
    $ldap->auth('user@example.com', 'some_user_password');

    /**
     * Simple search. Uses default search parameters.
     */
    $results = $ldap->search($search);

    /*
     * Longer Form of Search Function:
     * Parameter: Search Query (filter) - e.g. "mail=*" | "dn=chris" | "uid=15234"
     * Parameter: DN. Uses "base_dn" defined above, but you can over-ride it here
     * Parameter: Attributes returned from search. Default shown below
     */
    // $results = $ldap->search('mail=*', 'ou=Accounting,dc=example.com,dc=com', ['uid','givenname', 'sn', 'mail', 'telephonenumber']);
} catch (\Exception $e) {
    // Output error message and halt script execution
    die($e->getMessage());
}

/* OUTPUT RESULTS */
header('Content-type: text/xml');
echo xmlFromResults($results);

/* HELPER FUNCTIONS BELOW */

/**
 * Build a search term from query parameters.
 * @param array $query
 * @return string
 */
function parseQuery(array $query)
{
    if (! empty($query['customer_id'])) {	//If an ID is passed in use that to make a direct lookup

        $filter = 'uid='.$query['customer_id'].'*';
    } elseif (! empty($query['email'])) {			//If no ID then try email

        $filter = 'mail='.$query['email'].'*';
    } elseif (! empty($query['last_name'])) {	//If no ID or email then search on last name

        $filter = 'sn='.$query['last_name'].'*';
    } elseif (! empty($query['first_name'])) {	//Try first name if no ID,email,last name

        $filter = 'givenname='.$query['first_name'].'*';
    } else {
        $filter = 'sn='.'*';	//Return everyone
    }

    return $filter;
}

/**
 * Build XML output from search results.
 * @param $results
 * @return string
 */
function xmlFromResults($results)
{
    $output = '<?xml version="1.0" encoding="utf-8"?>';
    $output .= '<livelookup version="1.0" columns="first_name,last_name, email">';
    foreach ($results as $result) {
        $output .= '<customer>
    <customer_id>'.htmlspecialchars($result['uid'][0]).'</customer_id>
    <first_name>'.htmlspecialchars($result['givenname'][0]).'</first_name>
    <last_name>'.hmlspecialchars($result['sn'][0]).'</last_name>
    <email>'.htmlspecialchars($result['mail'][0]).'</email>
    <phone>'.htmlspecialchars($result['telephonenumber'][0])."</phone>
    <!-- Add custom elements here. There should be defined with additional attributes. Default attributes: ['uid','givenname', 'sn', 'mail', 'telephonenumber'] -->
</customer>";
    }
    $output .= '</livelookup>';

    return $output;
}
