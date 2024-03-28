<?php
/*

NOTE: THIS FILE MUST BE RENAMED TO   BlackBox.php   IN ORDER TO WORK!

The black box function allows you to use any authentication scheme you like with HelpSpot. Once you enable
black box authentication in the admin settings page, the function below will be used to authenticate users rather than
HelpSpots built in authentication.

In order to work the user who is trying to authenticate must first have been given a HelpSpot account by an administrator
and their username provided to HelpSpot.

In the event this function returns false, HelpSpot fails over to trying to login the user using it's own built in authentication.
This allows users, especially administrators, to still have access to the system in the event that something is wrong with
black box authentication.

NOTE: Instructions for using Active Directory with Black Box authentication are available here: https://support.helpspot.com/index.php?pg=kb.page&id=138

*/

// SECURITY: This prevents this script from being called from outside the context of HelpSpot
if (! defined('cBASEPATH')) {
    die();
}

/*
When using Black Box auth, HelpSpot's login page will ask the user for a username and password.
Authenticate these against your internal auth system using LDAP, Password file, Database call, or whatever system your company uses.
HelpSpot expects this function to return TRUE if the user is valid or FALSE if not.

If FALSE is returned then HelpSpot will attempt to login using it's internal system which expects the username to be
an email and the password to be the users internal HelpSpot password.
*/

function BlackBox($username, $password)
{

    /* DO YOUR AUTHENTICATION HERE */

    return false;
}
