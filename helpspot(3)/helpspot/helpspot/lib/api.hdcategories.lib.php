<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/******************************************
 * GET PUBLIC CATEGORIES
 ****************************************
 */
function apiGetPublicCategories()
{
    return $GLOBALS['DB']->Execute('SELECT HS_Category.*
                                  FROM HS_Category
                                  WHERE HS_Category.fDeleted = 0 AND HS_Category.fAllowPublicSubmit = 1
                                  ORDER BY HS_Category.sCategory');
}

/******************************************
ADD CATEGORY
******************************************/
function apiAddEditCategory($cat, $f, $l)
{
    // initialize
    $errors = [];
    $sql = '';
    $cat['mode'] = isset($cat['mode']) ? $cat['mode'] : 'add';
    $cat['resourceid'] = hs_numeric($cat, 'resourceid') ? $cat['resourceid'] : 0;

    $cat['sCategory'] = isset($cat['sCategory']) ? $cat['sCategory'] : '';
    $cat['sCategoryGroup'] = isset($cat['sCategoryGroup']) ? $cat['sCategoryGroup'] : '';
    $cat['fAllowPublicSubmit'] = hs_numeric($cat, 'fAllowPublicSubmit') ? $cat['fAllowPublicSubmit'] : 0;
    $cat['xPersonDefault'] = hs_numeric($cat, 'xPersonDefault') ? $cat['xPersonDefault'] : 0;
    $cat['fAutoAssignTo'] = hs_numeric($cat, 'fAutoAssignTo') ? $cat['fAutoAssignTo'] : 0;
    $cat['sReportingTagList'] = isset($cat['sReportingTagList']) ? $cat['sReportingTagList'] : [];
    $cat['sCustomFieldList'] = isset($cat['sCustomFieldList']) ? $cat['sCustomFieldList'] : [];
    $cat['sPersonList'] = isset($cat['sPersonList']) ? $cat['sPersonList'] : '';

    \Facades\HS\Cache\Manager::forgetGroup('categories');

    // Error checks
    if (hs_empty($cat['sCategory'])) {
        $errors['sCategory'] = lg_admin_categories_er_cat;
    }
    $checkCat = $GLOBALS['DB']->GetRow('SELECT COUNT(*) as cattotal FROM HS_Category WHERE sCategory = ? AND xCategory <> ?', [$cat['sCategory'], $cat['resourceid']]);

    if ($checkCat['cattotal'] != 0) {
        $errors['categoryexists'] = 1;
        $errors['errorBoxText'] = lg_admin_categories_er_catexists;
    }

    //Check that at least one staff person added to list
    if (hs_empty($cat['sPersonList'])) {
        $errors['sPersonList'] = lg_admin_categories_er_staff;
    }

    if (hs_empty($errors)) {
        if ($cat['mode'] == 'add') {
            $CatRes = $GLOBALS['DB']->Execute('INSERT INTO HS_Category(sCategory,sCategoryGroup,fAllowPublicSubmit,xPersonDefault,fAutoAssignTo,sPersonList,sCustomFieldList)
                                                VALUES (?,?,?,?,?,?,?)',
                                                [$cat['sCategory'],
                                                    $cat['sCategoryGroup'],
                                                    $cat['fAllowPublicSubmit'],
                                                    $cat['xPersonDefault'],
                                                    $cat['fAutoAssignTo'],
                                                    $cat['sPersonList'],
                                                    $cat['sCustomFieldList'], ]);

            $cat['resourceid'] = dbLastInsertID('HS_Category', 'xCategory');
        } elseif ($cat['mode'] == 'edit') {
            $GLOBALS['DB']->Execute('UPDATE HS_Category
                                     SET sCategory=?,sCategoryGroup=?,fAllowPublicSubmit=?,xPersonDefault=?,fAutoAssignTo=?,sPersonList=?,sCustomFieldList=?
                                     WHERE xCategory=?',
                                    [$cat['sCategory'],
                                        $cat['sCategoryGroup'],
                                        $cat['fAllowPublicSubmit'],
                                        $cat['xPersonDefault'],
                                        $cat['fAutoAssignTo'],
                                        $cat['sPersonList'],
                                        $cat['sCustomFieldList'],
                                        $cat['resourceid'], ]);
        }

        //Handle tags
        apiAddEditReportingTags($cat['resourceid'], $cat['sReportingTagList'], $f, $l);

        return true;
    } else {
        return $errors;
    }
}

/******************************************
ADD/EDIT REP TAGS
******************************************/
function apiAddEditReportingTags($catid, $newTags, $f, $l)
{
    //Get existing tags and compare to see what's been added or missing
    $existingTags = apiGetReportingTags($catid);

    //Find tags to delete
    $delete = array_diff($existingTags, $newTags);

    //Find tags to add
    $add = array_diff($newTags, $existingTags);

    //Delete
    if (is_array($delete) && ! empty($delete)) {
        foreach ($delete as $k=>$v) {
            $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/
            //Delete from rep rep
            $GLOBALS['DB']->Execute('DELETE FROM HS_Request_ReportingTags WHERE xReportingTag = ?', [$k]);
            //Delete from cat rep
            $GLOBALS['DB']->Execute('DELETE FROM HS_Category_ReportingTags WHERE xReportingTag = ?', [$k]);
            $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/
        }
    }

    //ADD
    if (is_array($add) && ! empty($add)) {
        foreach ($add as $v) {
            $GLOBALS['DB']->Execute('INSERT INTO HS_Category_ReportingTags(xCategory,sReportingTag) VALUES (?,?)', [$catid, $v]);
        }
    }

    //UPDATE ORDER
    $i = 0;
    foreach ($newTags as $k=>$v) {
        $GLOBALS['DB']->Execute('UPDATE HS_Category_ReportingTags SET iOrder=? WHERE sReportingTag=? AND xCategory=?',
                                    [$i, $v, $catid]);
        $i++;
    }
}
