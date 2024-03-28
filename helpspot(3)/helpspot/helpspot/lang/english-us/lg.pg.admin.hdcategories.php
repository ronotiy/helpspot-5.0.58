<?php

// SECURITY: Don't allow direct calls to this file outside the context of HelpSpot
if (! defined('cBASEPATH')) {
    die();
}

/* EDITING NOTES
1. Some strings contain %s symbols, these must be maintained
2. Any single quotes must be preceded by a slash like this:  \'  ex: there\'s
3. If you modify this file, be sure to back it up in case you overwrite it during an upgrade by accident
*/

define('lg_admin_categories_title', 'Categories');
define('lg_admin_categories_showdel', 'Show Inactive Categories');
define('lg_admin_categories_noshowdel', 'Return to Categories');
define('lg_admin_categories_cathelp', 'Categories are used to group customer requests. These groupings streamline workflow management and administrative reporting.');
define('lg_admin_categories_category', 'Category Name');
define('lg_admin_categories_categorygroup', 'Category Grouping');
define('lg_admin_categories_addgroup', 'Add Group');
define('lg_admin_categories_nogroup', 'No Group');
define('lg_admin_categories_staffmem', 'Category Staff Members');
define('lg_admin_categories_staffdesc', 'Staff members available to work requests assigned to this category');
define('lg_admin_categories_defcontact', 'Default Staff Contact');
define('lg_admin_categories_autoassign', 'Auto Assign Requests');
define('lg_admin_categories_autoassignex', 'Any request assigned to the inbox (new or reassigned) will automatically be assigned to a staffer based on the formula choosen.');
define('lg_admin_categories_aaoff', 'Off');
define('lg_admin_categories_visibility', 'Visibility');
define('lg_admin_categories_aadefault', 'To Default Contact');
define('lg_admin_categories_aarandom', 'Random Category Staffer');
define('lg_admin_categories_aarandomboth', 'Random Category Staffer (no administrators)');
define('lg_admin_categories_aaleast', 'Category Staffer with least requests');
define('lg_admin_categories_aaleastboth', 'Category Staffer with least requests (no administrators)');
define('lg_admin_categories_aarr', 'Round Robin (even distribution)');
define('lg_admin_categories_aarrnoadmin', 'Round Robin (even distribution, no administrators)');
define('lg_admin_categories_reportingtags', 'Reporting Tags');
define('lg_admin_categories_customfields', 'Custom Fields');
define('lg_admin_categories_customfieldsdesc', 'Select the custom fields which appear when this category is selected for a request.');
define('lg_admin_categories_customfields_none', 'This installation currently has no custom fields. Create custom fields in Admin->Organize.');
define('lg_admin_categories_websub', 'Will the category appear on the public web form');
define('lg_admin_categories_public', 'Public');
define('lg_admin_categories_private', 'Private');
define('lg_admin_categories_addcat', 'Add a Category');
define('lg_admin_categories_addbutton', 'Add Category');
define('lg_admin_categories_editcat', 'Edit: ');
define('lg_admin_categories_editbutton', 'Save Edits');
define('lg_admin_categories_er_cat', 'Please fill in category');
define('lg_admin_categories_er_staff', 'Please select at least one staff member for this category');
define('lg_admin_categories_er_catexists', 'A category with this name already exists');
define('lg_admin_categories_er_inmailbox', 'This category is assigned to a mailbox and cannot be set inactive');
define('lg_admin_categories_fbadded', 'Category added');
define('lg_admin_categories_fbedited', 'Category edited');
define('lg_admin_categories_fbdeleted', 'Category deleted');
define('lg_admin_categories_fbundeleted', 'Category restored');
define('lg_admin_categories_colid', 'ID');
define('lg_admin_categories_colcat', 'Category');
define('lg_admin_categories_colgrouping', 'Grouping');
define('lg_admin_categories_coldefcon', 'Default Contact');
define('lg_admin_categories_colwf', 'Public');
define('lg_admin_categories_colassign', 'Auto Assign');
define('lg_admin_categories_coldel', 'Make Category Inactive');
define('lg_admin_categories_coldelwarn', 'Are you sure you want to make this category inactive?');
