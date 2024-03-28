<?php

// Comment
// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/******************************************
FORMAT THE TEXT BODY
******************************************/
function formatKBBody($body)
{
    //Add space at front for strpos
    $body = ' '.$body;

    //If HTML found then leave alone, else insert breaks
    if (utf8_strpos($body, '<p>') || utf8_strpos($body, '<br') || utf8_strpos($body, '<div') || utf8_strpos($body, '<h') || utf8_strpos($body, '<ul') || utf8_strpos($body, '<ol') || utf8_strpos($body, '<li') || utf8_strpos($body, '<pre') || utf8_strpos($body, '<table')) {
        return $body;
    } else {
        return nl2br($body);
    }
}

/******************************************
RENDER THE CHAPTER NUMBER
******************************************/
function apiChapNum($isappendix, $reset = false)
{
    static $chapcounter = 0;

    //Allow reseting of chap numbers
    if ($reset) {
        $chapcounter = 0;
    }

    if ($isappendix) {
        return '';
    } else {
        $chapcounter++;

        return $chapcounter.'.';
    }
}

/******************************************
 * BUILDS THE TREE OF CHAPTERS AND PAGES
 *****************************************
 * @param $bookid
 * @param $showhidden
 * @return array
 */
function apiBuildChapPageTree($bookid, $showhidden)
{
    $tree = [];

    //used to make full path url's. Always refer back to primary host for image serving
    $url = parse_url(config('app.url'));

    apiChapNum(true, true);	//only here to reset chap number
    $chaps = apiGetBookChapters($bookid, $showhidden);
    if (hs_rscheck($chaps)) {
        while ($chap = $chaps->FetchRow()) {
            if ($chap['fHidden'] == 0) {
                $chapnum = apiChapNum($chap['fAppendix']);
                $prefix = $chapnum;
                $chapclass = '';
            } else {
                $chapnum = '';
                $prefix = ''; //$prefix  = '('.lg_kb_hidden.') ';
                $chapclass = 'kbhidden';
            }

            //Add chapter to tree
            $hidden = $chap['fHidden'] ? '('.lg_kb_hidden.') ' : '';

            $tc = ['type'=>'chapter', 'name'=>$prefix.' '.$chap['sChapterName'], 'class'=>$chapclass];

            $tc = array_merge($tc, $chap);

            $tree[] = $tc;

            $pages = apiGetChapPages($chap['xChapter'], $showhidden);
            if (hs_rscheck($pages)) {
                $pagenum = 0;
                while ($page = $pages->FetchRow()) {
                    //Adjust tPage to include full paths to images and links
                    $port = isset($url['port']) ? ':'.$url['port'] : '';
                    $page['tPage'] = relToAbs($page['tPage'], $url['scheme'].'://'.$url['host'].$port, true);

                    if ($page['fHidden'] == 0) {
                        $pagenum++;
                        $prefix = $chapnum.$pagenum.'. ';
                        $class = ! empty($chapclass) ? $chapclass : ''; //if chapter is hidden then show pages as hidden also
                    } else {
                        $prefix = ''; //$prefix = '('.lg_kb_hidden.') ';
                        $class = 'kbhidden';
                    }

                    $class .= $page['fHighlight'] ? ' kbhighlight' : '';

                    $tp = ['type'=>'page', 'name'=>$prefix.$page['sPageName'], 'class'=>$class];

                    $tp = array_merge($tp, $page);

                    $tree[] = $tp;
                }
            }
        }
    }

    return $tree;
}

/******************************************
RETURN ARRAY OF CHAPTERS FOR A SPECIFIC BOOK
******************************************/
function apiTocChaps(&$tree)
{
    $out = [];
    foreach ($tree as $v) {
        if ($v['type'] == 'chapter') {
            $out[$v['xChapter']] = $v;
        }
    }

    return empty($out) ? false : $out;
}

/******************************************
RETURN ARRAY OF PAGES FOR A SPECIFIC CHAP
******************************************/
function apiTocPages(&$tree, $chapid)
{
    $out = [];
    foreach ($tree as $v) {
        if ($v['type'] != 'chapter' && $v['xChapter'] == $chapid) {
            $out[$v['xPage']] = $v;
        }
    }

    return empty($out) ? false : $out;
}

/******************************************
RETURN A SPECIFIC CHAPS INFO
******************************************/
function apiChapFromTree(&$tree, $chapid)
{
    foreach ($tree as $v) {
        if ($v['type'] == 'chapter' && $v['xChapter'] == $chapid) {
            return $v;
        }
    }

    return false;
}

/******************************************
RETURN A SPECIFIC PAGES INFO
******************************************/
function apiPageFromTree(&$tree, $pageid)
{
    foreach ($tree as $v) {
        if ($v['type'] != 'chapter' && $v['xPage'] == $pageid) {
            return $v;
        }
    }

    return false;
}

/******************************************
SET ARRAY POINTER TO PROPER PLACE
******************************************/
function apiSetBookTreePointer(&$tree, $chapid = 0, $pageid = 0)
{
    if ($chapid != 0) {
        foreach ($tree as $v) {
            if ($v['type'] == 'chapter' && $v['xChapter'] == $chapid) {
                if (! current($tree)) {
                    end($tree);
                } else {
                    prev($tree);
                }

                break;
            }
        }
    } else {
        foreach ($tree as $v) {
            if ($v['type'] == 'page' && $v['xPage'] == $pageid) {
                if (! current($tree)) {
                    end($tree);
                } else {
                    prev($tree);
                }

                break;
            }
        }
    }
}

/**
 * Private method to just get the order and chapter
 * from the existing page.
 *
 * @param int $id
 * @return array
 */
function _getPageForNextPrev($id)
{
    return $GLOBALS['DB']->GetRow('select iOrder, xChapter from HS_KB_Pages where xPage = ?', [$id]);
}

/**
 * Get the previous kb page.
 * @param $id
 * @return mixed
 */
function getPrevKbPage($id)
{
    $page = _getPageForNextPrev($id);
    return $GLOBALS['DB']->getRow('select * from HS_KB_Pages where xChapter = ? and fHidden = 0 and iOrder < ? ORDER By iOrder DESC LIMIT 1', [$page['xChapter'], $page['iOrder']]);
}

/**
 * Get the next kb page.
 * @param $id
 * @return mixed
 */
function getNextKbPage($id)
{
    $page = _getPageForNextPrev($id);
    return $GLOBALS['DB']->getRow('select * from HS_KB_Pages where xChapter = ? and fHidden = 0 and iOrder > ? ORDER By iOrder ASC LIMIT 1', [$page['xChapter'], $page['iOrder']]);
}

/**
 * Get the previous kb page.
 * @param $id
 * @return mixed
 */
function getPrevKbChapter($id)
{
    return $GLOBALS['DB']->GetRow('select * from HS_KB_Chapters where iOrder < (select iOrder from HS_KB_Chapters where xChapter = ?) ORDER By iOrder DESC LIMIT 1', [$id]);
}

/**
 * Get the previous kb page.
 * @param $id
 * @return mixed
 */
function getNextKbChapter($id)
{
    return $GLOBALS['DB']->getRow('select * from HS_KB_Chapters where iOrder > (select iOrder from HS_KB_Chapters where xChapter = ?) ORDER By iOrder ASC LIMIT 1', [$id]);
}

/******************************************
 * GET ALL BOOK CHAPTERS - list of names
 ****************************************
 */
function apiGetAllBookChapters()
{
    return $GLOBALS['DB']->Execute('SELECT HS_KB_Books.sBookName,HS_KB_Chapters.sChapterName,HS_KB_Chapters.xChapter
                                           FROM HS_KB_Books,HS_KB_Chapters
                                           WHERE HS_KB_Books.xBook = HS_KB_Chapters.xBook');
}

/******************************************
 * GET All BOOKS
 *****************************************
 * @return mixed
 */
function apiGetAllBooks()
{
    return \Illuminate\Support\Facades\Cache::remember(\HS\Cache\Manager::CACHE_ALLBOOKS_KEY, \HS\Cache\Manager::CACHE_ALLBOOKS_MINUTES, function () {
        return $GLOBALS['DB']->Execute('SELECT HS_KB_Books.*
                                       FROM HS_KB_Books
                                       ORDER BY fPrivate ASC, iOrder ASC, sBookName ASC');
    });
}

/******************************************
 * GET A LIST OF BOOKS
 *****************************************
 * @param $public
 * @return mixed
 */
function apiGetBooks($public)
{
    $cachekey = $public ? \HS\Cache\Manager::CACHE_BOOKS_PUBLIC_KEY : \HS\Cache\Manager::CACHE_BOOKS_PRIVATE_KEY;

    return \Illuminate\Support\Facades\Cache::remember($cachekey, \HS\Cache\Manager::CACHE_BOOKS_MINUTES, function () use ($public) {
        $public = is_numeric($public) ? $public : 0;

        return $GLOBALS['DB']->Execute('SELECT HS_KB_Books.*
                                       FROM HS_KB_Books
                                       WHERE fPrivate = ?
                                       ORDER BY iOrder ASC', [$public]);
    });
}

/******************************************
 * GET BOOK INFO
 *****************************************
 * @param $bookid
 */
function apiGetBook($bookid)
{
    $bookid = is_numeric($bookid) ? $bookid : 0;

    return $GLOBALS['DB']->GetRow('SELECT HS_KB_Books.* FROM HS_KB_Books WHERE xBook = ?', [$bookid]);
}

/******************************************
GET CHAPTER BY PAGE
******************************************/
function apiGetChapterByPage($pageid)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;

    $chap = $GLOBALS['DB']->GetRow('SELECT HS_KB_Chapters.xChapter,HS_KB_Chapters.sChapterName
									 FROM HS_KB_Pages,HS_KB_Chapters
									 WHERE xPage = ? AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter', [$pageid]);

    return $chap;
}

/******************************************
GET BOOK BY PAGE
******************************************/
function apiGetBookByPage($pageid)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;

    $book = $GLOBALS['DB']->GetRow('SELECT HS_KB_Books.xBook,HS_KB_Books.sBookName,HS_KB_Books.fPrivate
									 FROM HS_KB_Pages,HS_KB_Chapters,HS_KB_Books
									 WHERE xPage = ? AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND HS_KB_Chapters.xBook = HS_KB_Books.xBook', [$pageid]);

    return $book;
}

/******************************************
 * GET CHAPTER INFO
 *****************************************
 * @param $chapid
 */
function apiGetChapter($chapid)
{
    $chapid = is_numeric($chapid) ? $chapid : 0;

    return $GLOBALS['DB']->GetRow('SELECT HS_KB_Chapters.*, HS_KB_Books.fPrivate FROM HS_KB_Chapters, HS_KB_Books
                                   WHERE HS_KB_Chapters.xChapter = ? AND HS_KB_Chapters.xBook = HS_KB_Books.xBook', [$chapid]);
}

/******************************************
 * GET PAGE INFO
 *****************************************
 * @param $pageid
 */
function apiGetPage($pageid)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;

    return $GLOBALS['DB']->GetRow('SELECT HS_KB_Pages.*, HS_KB_Books.fPrivate FROM HS_KB_Pages, HS_KB_Chapters, HS_KB_Books
                                   WHERE HS_KB_Pages.xPage = ? AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND
                                         HS_KB_Chapters.xBook = HS_KB_Books.xBook', [$pageid]);
}

/******************************************
 * GET BOOK CHAPTERS
 *****************************************
 * @param $bookid
 * @param $showhidden
 */
function apiGetBookChapters($bookid, $showhidden)
{
    $bookid = is_numeric($bookid) ? $bookid : 0;

    $hidden = $showhidden ? '' : ' AND HS_KB_Chapters.fHidden=0';
    return $GLOBALS['DB']->Execute('SELECT HS_KB_Chapters.*, HS_KB_Books.fPrivate FROM HS_KB_Chapters, HS_KB_Books
                                    WHERE HS_KB_Chapters.xBook = ? '.$hidden.' AND HS_KB_Chapters.xBook = HS_KB_Books.xBook
                                    ORDER BY HS_KB_Chapters.fAppendix ASC, HS_KB_Chapters.iOrder ASC, sChapterName', [$bookid]);
}

/******************************************
 * GET CHAPTER PAGES
 *****************************************
 * @param $chapid
 * @param $showhidden
 */
function apiGetChapPages($chapid, $showhidden)
{
    $chapid = is_numeric($chapid) ? $chapid : 0;

    $hidden = $showhidden ? '' : ' AND HS_KB_Pages.fHidden=0';
    return $GLOBALS['DB']->Execute('SELECT HS_KB_Pages.*, HS_KB_Books.fPrivate FROM HS_KB_Pages, HS_KB_Chapters, HS_KB_Books
                                       WHERE HS_KB_Pages.xChapter=? '.$hidden.' AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND HS_KB_Chapters.xBook = HS_KB_Books.xBook
                                       ORDER BY HS_KB_Pages.iOrder ASC, sPageName', [$chapid]);
}

/******************************************
 * GET ALL DOCUMENTS FOR A PAGE
 *****************************************
 * @param $pageid
 */
function apiGetPageDocs($pageid)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;

    return $GLOBALS['DB']->Execute('SELECT HS_KB_Documents.*,'.dbStrLen('blobFile').' AS file_size FROM HS_KB_Documents WHERE xPage = ? AND fDownload = 1 ORDER BY sFilename ASC', [$pageid]);
}

/******************************************
GET ALL IMAGES FOR A PAGE
******************************************/
function apiGetPageImages($pageid)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;

    return $GLOBALS['DB']->Execute('SELECT HS_KB_Documents.* FROM HS_KB_Documents WHERE xPage = ? AND fDownload = 0 ORDER BY xDocumentId DESC', [$pageid]);
}

/******************************************
 * GET RELATED PAGES FOR A PAGE
 *****************************************
 * @param $pageid
 */
function apiGetRelatedPages($pageid)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;

    return $GLOBALS['DB']->Execute('SELECT HS_KB_RelatedPages.xPage, HS_KB_RelatedPages.xRelatedPage,HS_KB_Pages.sPageName, HS_KB_Chapters.sChapterName, HS_KB_Books.sBookName, HS_KB_Books.xBook
                                     FROM HS_KB_RelatedPages,HS_KB_Pages,HS_KB_Chapters,HS_KB_Books
                                     WHERE HS_KB_RelatedPages.xRelatedPage = HS_KB_Pages.xPage AND HS_KB_Pages.fHidden = 0
                                     AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND HS_KB_Chapters.xBook = HS_KB_Books.xBook
                                     AND HS_KB_RelatedPages.xPage = ?
                                     ORDER BY sBookName,sChapterName,sPageName', [$pageid]);
}

/******************************************
 * GET LEAST HELPFUL
 *****************************************
 * @param $length
 */
function apiGetLeastHelpful($length)
{
    return $GLOBALS['DB']->SelectLimit('SELECT HS_KB_Pages.*, HS_KB_Chapters.sChapterName, HS_KB_Books.sBookName, HS_KB_Books.xBook
                                         FROM HS_KB_Pages, HS_KB_Chapters, HS_KB_Books
                                         WHERE HS_KB_Pages.fHidden = 0 AND HS_KB_Pages.iNotHelpful <> 0
                                                AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND
                                                HS_KB_Chapters.xBook = HS_KB_Books.xBook AND HS_KB_Books.fPrivate = 0
                                         ORDER BY iNotHelpful DESC', $length, 0);
}

/******************************************
 * GET MOST HELPFUL
 *****************************************
 * @param $length
 * @return false
 */
function apiGetMostHelpful($length)
{
    $hidepriv = IN_PORTAL ? ' AND HS_KB_Books.fPrivate = 0 ' : '';	//Don't show private books in portal

    //If we're in a remote portal and books have been specified only keep results for specified books
    $books = '';

    if (isset($GLOBALS['hs_multiportal']) and ! empty($GLOBALS['hs_multiportal']->kbs)) {
        $books = ' AND HS_KB_Books.xBook IN ('.implode(',', $GLOBALS['hs_multiportal']->kbs).')';
    } elseif (isset($GLOBALS['hs_multiportal']) and empty($GLOBALS['hs_multiportal']->kbs)) {
        return false;
    }

    return $GLOBALS['DB']->SelectLimit('SELECT HS_KB_Pages.*, HS_KB_Chapters.sChapterName, HS_KB_Books.sBookName, HS_KB_Books.xBook
                                         FROM HS_KB_Pages, HS_KB_Chapters, HS_KB_Books
                                         WHERE HS_KB_Pages.fHidden = 0 AND HS_KB_Pages.iHelpful <> 0
                                                AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND
                                                HS_KB_Chapters.xBook = HS_KB_Books.xBook '.$hidepriv.'
                                                '.$books.'
                                         ORDER BY iHelpful DESC', $length, 0);
}

/******************************************
 * GET HIGHLIGHTED PAGES
 ****************************************
 */
function apiGetHighlightedPages()
{
    $hidepriv = IN_PORTAL ? ' AND HS_KB_Books.fPrivate = 0 ' : ''; //Don't show private books in portal

    return $GLOBALS['DB']->Execute('SELECT HS_KB_Pages.*, HS_KB_Chapters.sChapterName, HS_KB_Books.sBookName, HS_KB_Books.xBook
                                         FROM HS_KB_Pages, HS_KB_Chapters, HS_KB_Books
                                         WHERE HS_KB_Pages.fHidden = 0 AND HS_KB_Pages.fHighlight = 1 AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter
                                                AND	HS_KB_Chapters.xBook = HS_KB_Books.xBook '.$hidepriv.'
                                         ORDER BY HS_KB_Books.sBookName, HS_KB_Chapters.sChapterName, HS_KB_Pages.sPageName DESC');
}

/******************************************
 * DETERMINES IF A BOOK IS PRIVATE FROM AN ID
 *****************************************
 * @param $args
 */
function apiInPrivateBook($args)
{
    //Call as few functions as possible to get info
    if (isset($args['xBook'])) {
        $b = apiGetBook($args['xBook']);

        return $b['fPrivate'];
    } elseif (isset($args['xChapter'])) {
        $b = $GLOBALS['DB']->GetRow('SELECT HS_KB_Books.fPrivate FROM HS_KB_Books,HS_KB_Chapters
									  WHERE HS_KB_Chapters.xChapter = ? AND HS_KB_Chapters.xBook = HS_KB_Books.xBook', [$args['xChapter']]);

        return $b['fPrivate'];
    } elseif (isset($args['xPage'])) {
        $b = $GLOBALS['DB']->GetRow('SELECT HS_KB_Books.fPrivate
									  FROM HS_KB_Books,HS_KB_Chapters,HS_KB_Pages
									  WHERE HS_KB_Pages.xPage = ? AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND HS_KB_Chapters.xBook = HS_KB_Books.xBook', [$args['xPage']]);

        return $b['fPrivate'];
    }
}

/******************************************
 * ADD A CHAPTER
 *****************************************
 * @param $chap
 * @return false
 */
function apiAddChapter($chap)
{
    $fm['xBook'] = hs_numeric($chap, 'xBook') ? $chap['xBook'] : 0;
    $fm['sChapterName'] = isset($chap['sChapterName']) ? $chap['sChapterName'] : '';
    $fm['iOrder'] = hs_numeric($chap, 'iOrder') ? $chap['iOrder'] : 0;
    $fm['fAppendix'] = hs_numeric($chap, 'fAppendix') ? $chap['fAppendix'] : 0;
    $fm['fHidden'] = hs_numeric($chap, 'fHidden') ? $chap['fHidden'] : 0;
    $fm['orderafter'] = isset($chap['orderafter']) ? $chap['orderafter'] : '';

    if ($fm['xBook'] != 0) {
        $GLOBALS['DB']->Execute('INSERT INTO HS_KB_Chapters(xBook,sChapterName,iOrder,fAppendix,fHidden) VALUES (?,?,?,?,?)',
                                                                                                 [$fm['xBook'],
                                                                                                 $fm['sChapterName'],
                                                                                                 $fm['iOrder'],
                                                                                                 $fm['fAppendix'],
                                                                                                 $fm['fHidden'], ]);

        $chapid = dbLastInsertID('HS_KB_Chapters', 'xChapter');

        //reorder
        apiRebuildChapOrder($fm['xBook'], $chapid, $fm['orderafter']);

        return $chapid;
    }

    return false;
}

/******************************************
 * UPDATE A CHAPTER
 *****************************************
 * @param $chap
 * @return bool
 */
function apiUpdateChapter($chap)
{
    $fm['xBook'] = hs_numeric($chap, 'xBook') ? $chap['xBook'] : 0;
    $fm['xChapter'] = hs_numeric($chap, 'xChapter') ? $chap['xChapter'] : 0;
    $fm['sChapterName'] = isset($chap['sChapterName']) ? $chap['sChapterName'] : '';
    $fm['fAppendix'] = hs_numeric($chap, 'fAppendix') ? $chap['fAppendix'] : 0;
    $fm['fHidden'] = hs_numeric($chap, 'fHidden') ? $chap['fHidden'] : 0;
    $fm['orderafter'] = isset($chap['orderafter']) ? $chap['orderafter'] : '';

    if ($fm['xChapter'] != 0) {
        $GLOBALS['DB']->Execute('UPDATE HS_KB_Chapters SET xBook=?,sChapterName=?,fAppendix=?,fHidden=? WHERE xChapter = ?',
                                                             [$fm['xBook'],
                                                             $fm['sChapterName'],
                                                             $fm['fAppendix'],
                                                             $fm['fHidden'],
                                                             $fm['xChapter'], ]);

        apiRebuildChapOrder($fm['xBook'], $fm['xChapter'], $fm['orderafter']);

        return true;
    }

    return false;
}

/******************************************
ADD A PAGE
******************************************/
function apiAddPage($page, $f, $l)
{
    global $user;

    $fm['xChapter'] = hs_numeric($page, 'xChapter') ? $page['xChapter'] : 0;
    $fm['xBook'] = hs_numeric($page, 'xBook') ? $page['xBook'] : 0;
    $fm['sPageName'] = isset($page['sPageName']) ? formCleanHtml($page['sPageName']) : '';
    $fm['tPage'] = isset($page['tPage']) ? $page['tPage'] : '';
    $fm['tKeywords'] = isset($page['tKeywords']) ? $page['tKeywords'] : '';
    $fm['iOrder'] = hs_numeric($page, 'iOrder') ? $page['iOrder'] : 0;
    $fm['fHidden'] = hs_numeric($page, 'fHidden') ? $page['fHidden'] : 0;
    $fm['fHighlight'] = hs_numeric($page, 'fHighlight') ? $page['fHighlight'] : 0;
    $fm['iHelpful'] = hs_numeric($page, 'iHelpful') ? $page['iHelpful'] : 0;
    $fm['iNotHelpful'] = hs_numeric($page, 'iNotHelpful') ? $page['iNotHelpful'] : 0;
    $fm['orderafter'] = isset($page['orderafter']) ? $page['orderafter'] : '';
    $fm['relatedpages'] = isset($page['relatedpages']) ? $page['relatedpages'] : '';
    $fm['tags'] = isset($page['tags']) ? $page['tags'] : [];

    if ($fm['xChapter'] != 0) {
        return \Illuminate\Support\Facades\DB::transaction(function() use($user, $page, $fm, $f, $l) {
            $GLOBALS['DB']->Execute('INSERT INTO HS_KB_Pages(xChapter,sPageName,tPage,iOrder,fHidden,fHighlight,xPersonCreator,xPersonLastUpdate,dtCreatedOn,dtUpdatedOn)
                                            VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$fm['xChapter'],
                    hs_strip_tags($fm['sPageName']),
                    $fm['tPage'],
                    $fm['iOrder'],
                    $fm['fHidden'],
                    $fm['fHighlight'],
                    $user['xPerson'],
                    0,
                    time(),
                    time(), ]);

            $pageid = dbLastInsertID('HS_KB_Pages', 'xPage');

            //Add documents
            apiAddKBDocuments($pageid, 1, $f, $l);

            //Add related pages
            apiAddRelatedPages($pageid, $page['relatedpages'], $f, $l);

            //reorder
            apiRebuildPageOrder($fm['xChapter'], $pageid, $fm['orderafter'], $f, $l);

            //add tags
            apiAddTags($fm['tags'], $pageid);

            event('knowledgebooks.page.create', [$pageid]);
            return $pageid;
        });
    }

    return false;
}

/******************************************
UPDATE A PAGE
******************************************/
function apiUpdatePage($page, $f, $l)
{
    global $user;

    $fm['xChapter'] = hs_numeric($page, 'xChapter') ? $page['xChapter'] : 0;
    $fm['xBook'] = hs_numeric($page, 'xBook') ? $page['xBook'] : 0;
    $fm['xPage'] = hs_numeric($page, 'xPage') ? $page['xPage'] : 0;
    $fm['sPageName'] = isset($page['sPageName']) ? formCleanHtml($page['sPageName']) : '';
    $fm['tPage'] = isset($page['tPage']) ? $page['tPage'] : '';
    $fm['tKeywords'] = isset($page['tKeywords']) ? $page['tKeywords'] : '';
    $fm['iOrder'] = hs_numeric($page, 'iOrder') ? $page['iOrder'] : 0;
    $fm['fHidden'] = hs_numeric($page, 'fHidden') ? $page['fHidden'] : 0;
    $fm['fHighlight'] = hs_numeric($page, 'fHighlight') ? $page['fHighlight'] : 0;
    $fm['iHelpful'] = hs_numeric($page, 'iHelpful') ? $page['iHelpful'] : 0;
    $fm['iNotHelpful'] = hs_numeric($page, 'iNotHelpful') ? $page['iNotHelpful'] : 0;
    $fm['orderafter'] = isset($page['orderafter']) ? $page['orderafter'] : '';
    $fm['relatedpages'] = isset($page['relatedpages']) ? $page['relatedpages'] : '';
    $fm['tags'] = isset($page['tags']) ? $page['tags'] : [];

    return \Illuminate\Support\Facades\DB::transaction(function () use($page, $user, $fm, $f, $l) {
        $GLOBALS['DB']->Execute('UPDATE HS_KB_Pages SET xChapter=?,sPageName=?,tPage=?,iOrder=?,fHidden=?,fHighlight=?,xPersonLastUpdate=?,dtUpdatedOn=?
                                        WHERE xPage = ?',
            [$fm['xChapter'],
                hs_strip_tags($fm['sPageName']),
                $fm['tPage'],
                $fm['iOrder'],
                $fm['fHidden'],
                $fm['fHighlight'],
                $user['xPerson'],
                time(),
                $fm['xPage'], ]);

        //Add documents
        apiAddKBDocuments($fm['xPage'], 1, $f, $l);

        //Add related pages
        apiAddRelatedPages($fm['xPage'], $page['relatedpages'], $f, $l);

        //reorder
        apiRebuildPageOrder($fm['xChapter'], $fm['xPage'], $fm['orderafter'], $f, $l);

        //add tags
        apiAddTags($fm['tags'], $fm['xPage']);

        event('knowledgebooks.page.update', [$fm['xPage']]);

        return true;
    });
}

/******************************************
DELETE KB PAGE
******************************************/
function apiDeletePage($pageid)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;

    return \Illuminate\Support\Facades\DB::transaction(function() use($pageid) {
        // delete related pages
        \DB::statement('DELETE FROM HS_KB_RelatedPages WHERE xPage = ?', [$pageid]);

        // delete docs
        \DB::statement('DELETE FROM HS_KB_Documents WHERE xPage = ?', [$pageid]);

        // delete page
        \DB::statement('DELETE FROM HS_KB_Pages WHERE xPage = ?', [$pageid]);
        event('knowledgebooks.page.delete', [$pageid]);

        //Delete tags
        apiDeleteTags($pageid);

        return true;
    });
}

/******************************************
ADD A KB doc
******************************************/
function apiAddKBDocuments($pageid, $isdownload, $f, $l)
{
    set_time_limit(120); //Increase time alloted for operation

    if (isset($_FILES['doc']) && ! empty($_FILES['doc']) && $pageid != 0) {
        foreach ($_FILES['doc']['error'] as $key => $error) {
            if (! empty($_FILES['doc']['name'][$key])) {
                if ($error == UPLOAD_ERR_OK) {
                    $body = file_get_contents($_FILES['doc']['tmp_name'][$key]);
                    if (! empty($_FILES['doc']['type']) && ! empty($body)) {
                        $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

                        $GLOBALS['DB']->Execute('INSERT INTO HS_KB_Documents(xPage,fDownload,sFilename,sFileMimeType)
                                                           VALUES(?,?,?,?)',
                                                            [$pageid,
                                                            $isdownload, //this is a download
                                                            $_FILES['doc']['name'][$key],
                                                            $_FILES['doc']['type'][$key], ]);

                        $id = dbLastInsertID('HS_KB_Documents', 'xDocumentId');
                        $GLOBALS['DB']->UpdateBlob('HS_KB_Documents', 'blobFile', $body, ' xDocumentId = '.$id);

                        $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/
                    }
                } else {
                    errorLog(hs_imageerror($error), 'KB Image Upload', $f, $l);
                }
            }
        }
    }
}

/******************************************
REBUILD LIST OF RELATED PAGES
******************************************/
function apiAddRelatedPages($pageid, $relatedpages, $f, $l)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;

    if (! empty($relatedpages)) {
        return \Illuminate\Support\Facades\DB::transaction(function() use($pageid, $relatedpages, $f, $l) {
            // Remove old related pages
            $GLOBALS['DB']->Execute('DELETE FROM HS_KB_RelatedPages WHERE xPage = ?', [$pageid]);

            $rp = explode(',', $relatedpages);
            if (is_array($rp) && count($rp) > 0) {
                foreach ($rp as $v) {
                    $GLOBALS['DB']->Execute('INSERT INTO HS_KB_RelatedPages(xPage,xRelatedPage) VALUES (?,?)', [$pageid, $v]);
                }
            }
            return true;
        });
    }

    return false;
}

/******************************************
 * REBUILD ORDER OF CHAPTERS
 *****************************************
 * @param $bookid
 * @param $chapid
 * @param $afterid
 */
function apiRebuildChapOrder($bookid, $chapid, $afterid)
{
    $chapid = is_numeric($chapid) ? $chapid : 0;

    //Setup order array. If the new chap should be the first in the book then insert it right here
    $chapsorder = ($afterid == 0) ? [$chapid] : [];
    $allchaps = apiGetBookChapters($bookid, true);

    //build order array
    if (hs_rscheck($allchaps)) {
        while ($r = $allchaps->FetchRow()) {
            //don't add to array if it's the chapter we're trying to move
            if ($r['xChapter'] != $chapid) {
                $chapsorder[] = $r['xChapter'];
            }

            //If this is the chapter we're trying to move after then insert the moving chap
            if ($r['xChapter'] == $afterid) {
                $chapsorder[] = $chapid;
            }
        }

        //update orders
        $i = 0;
        foreach ($chapsorder as $v) {
            $i++;
            $update = $GLOBALS['DB']->Execute('UPDATE HS_KB_Chapters SET iOrder = ? WHERE xChapter = ?', [$i, $v]);
        }
    }
}

/******************************************
REBUILD ORDER OF PAGES
******************************************/
function apiRebuildPageOrder($chapid, $pageid, $afterid, $f, $l)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;
    $chapid = is_numeric($chapid) ? $chapid : 0;

    //Setup order array. If the new page should be the first in the chapter then insert it right here
    $pagesorder = ($afterid == 0) ? [$pageid] : [];
    $allpages = apiGetChapPages($chapid, true);

    //build order array
    if (hs_rscheck($allpages)) {
        while ($r = $allpages->FetchRow()) {
            //don't add to array if it's the page we're trying to move
            if ($r['xPage'] != $pageid) {
                $pagesorder[] = $r['xPage'];
            }

            //If this is the page we're trying to move after then insert the moving page
            if ($r['xPage'] == $afterid) {
                $pagesorder[] = $pageid;
            }
        }

        //update orders
        $i = 0;
        foreach ($pagesorder as $v) {
            $i++;
            $GLOBALS['DB']->Execute('UPDATE HS_KB_Pages SET iOrder = ? WHERE xPage = ?', [$i, $v]);
        }
    }
}

/******************************************
 * CLEAR PAGE VOTES
 *****************************************
 * @param $pageid
 */
function apiClearHelpfulVotes($pageid)
{
    $pageid = is_numeric($pageid) ? $pageid : 0;

    return $GLOBALS['DB']->Execute('UPDATE HS_KB_Pages SET iHelpful=0, iNotHelpful=0 WHERE xPage = ?', [$pageid]);
}
