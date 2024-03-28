<?php
/*
# Note, this page will not be visible inside HelpSpot unless you rename it

#WHAT ARE THEY
Custom pages allow you to add navigation/pages to HelpSpot's workspace. They're usful for adding
content which is protected by HelpSpot's authentication. The uses are practically limitless, here's a few ideas:
    * A page simply listing links to other help desk resources elsewhere for staff
    * A report from an external system
    * A redirect to an external system
    * A page for AJAX based tools

#NAMING
    * Letters, numbers, underscores and dashes only (no spaces)
    * Must end in .php
    * Navigation link will show underscores as spaces and capitalize the first letter of each word

#LIMITS/ACCESS
Custom pages do not have access to HelpSpot's PHP functions, however, they can make use of jQuery and HelpSpot's CSS.

#REDIRECT EXAMPLE
header('Location: http://www.yahoo.com');exit();
*/

//Secure this file so it can only be run in the context of HelpSpot
if (! defined('cBASEPATH')) {
    die();
}

//Output text, javascript, make database calls, etc
echo '<h2>This is a custom page</h2>';
echo '<p>You should see this text if it is working</p>';

?>
			