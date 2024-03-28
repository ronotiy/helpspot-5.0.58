<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('kb');

/*****************************************
LIBS
*****************************************/
include_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'kb.page']);
$hidePageFrame = 0;
$tab = 'nav_kb';
$subtab = '';
$pagetitle = lg_kb_title;
$rsslink = '';
$pageid = isset($_GET['page']) ? $_GET['page'] : '';
$clearvotes = isset($_GET['clearvotes']) ? $_GET['clearvotes'] : '';

$page = apiGetPage($pageid);
$chapter = apiGetChapter($page['xChapter']);
$tree = apiBuildChapPageTree($chapter['xBook'], true);
$book = apiGetBook($chapter['xBook']);
$editors = hs_unserialize($book['tEditors']);
$docs = apiGetPageDocs($pageid);
$related = apiGetRelatedPages($pageid);

$allStaff = apiGetAllUsersComplete();

//$chtree  = apiChapFromTree($tree,$page['xChapter']);
$pgtree = apiPageFromTree($tree, $pageid);

$icon = $book['fPrivate'] == 1 ? 'book_red.gif' : 'public.gif';

//Protect page
if ($book['fPrivate'] == 1 && ! perm('fModuleKbPriv')) {
    return redirect()
        ->route('admin', ['pg' => 'kb']);
}

/*****************************************
CLEAR VOTES
*****************************************/
if (is_array($editors) && in_array($user['xPerson'], $editors) && $clearvotes == 1 && ! empty($pageid)) {
    apiClearHelpfulVotes($pageid);
    return redirect()
        ->route('admin', ['pg' => 'kb.page', 'page' => $pageid]);
}

/*****************************************
JAVASCRIPT
*****************************************/
//Import any custom styles used by the wysiwyg
$headscript = '
<style type="text/css" media="screen">@import "'.route('admin', ['pg' => 'kb.wysiwyg']).'";</style>
<script type="text/javascript" language="JavaScript">
	$jq().ready(function(){
		$jq(".rt").click(function(){
			var txt = $jq(this).find("input").val();
			goPage("admin?pg=search&tags="+txt);
		});
	});

	function viewInPortal(){
		if($jq("#portal-selector").val() != "") window.open($jq("#portal-selector").val()+"/index.php?pg=kb.page&id='.$pageid.'","kbportal");
	}
</script>
';
$onload = '';

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody .= renderPageheader('<b class="breadcrumb">
				<a href="'.route('admin', ['pg' => 'kb']).'">'.lg_kb_home.'</a>  &nbsp; / &nbsp;
				<a href="'.route('admin', ['pg' => 'kb.book', 'book' => $book['xBook']]).'">'.$book['sBookName'].'</a>  &nbsp; / &nbsp;
				<a href="'.route('admin', ['pg' => 'kb.book', 'book' => $book['xBook']]).'#chapter'.$page['xChapter'].'">'.$chapter['sChapterName'].'</a>  &nbsp; / &nbsp;
				'.$page['sPageName'].'</b>');

//Editor Controls
$topbar = '';
if (is_array($editors) && in_array($user['xPerson'], $editors)) {
    $topbar .= '<a href="'.route('admin', ['pg' => 'kb.modpage', 'chapter' => $page['xChapter'], 'page' => $pageid]).'" class="btn full" style="margin-bottom:14px;">'.lg_kb_editpage.'</a>';
    $reset = '(<a href="'.$basepgurl.'&page='.$pageid.'&clearvotes=1" onClick="return hs_confirm(\''.hs_jshtmlentities(lg_kb_clearhelpfulcheck).'\',this.href);">'.lg_kb_clearhelpful.'</a>)';
}

//View in portal
$view = '';
if (! $page['fHidden'] && ! $chapter['fHidden'] && ! $book['fPrivate']) {
    $portals = apiGetAllPortals(0);

    // Show the portal links in a Tipped dropdown. If they only have one
    // then have it on the button click. Otherwise show the tipped.
    if ($portals->RecordCount() == 0) {
        $view .= '<a id="show_in_portal" href="'.cHOST.'/index.php?pg=kb.page&id='.$pageid.'" target="_blank" class="btn full">View in portal</a>';
    } else {
        $view .= '<a id="show_in_portal" href="#" class="btn full">View in portal</a>';
        $view .= '
		<div id="show_in_portal_menu" style="display:none;">
			<ul class="tooltip-menu">
				<li><a href="'.cHOST.'/index.php?pg=kb.page&id='.$pageid.'" target="_blank" class=""><span class="tooltip-menu-maintext">'.lg_primaryportal.'</span></a></li>
			';

        if (hs_rscheck($portals)) {
            while ($row = $portals->FetchRow()) {
                $inportal = hs_unserialize($row['tDisplayKBs']);
                if (in_array($chapter['xBook'], $inportal)) {
                    $view .= '<li><a href="'.$row['sHost'].'/index.php?pg=kb.page&id='.$pageid.'" target="_blank" class=""><span class="tooltip-menu-maintext">'.hs_htmlspecialchars($row['sPortalName']).'</span></a></li>';
                }
            }
        }
        $view .= '</ul>
		</div>';

        $view .= '<script type="text/javascript">
				$jq(document).ready(function(){
					new Tip("show_in_portal", $("show_in_portal_menu"),{
							title: "",
							border: 0,
							radius: 0,
                            className: "hstinytipfat",
                            stem: "topMiddle",
							showOn: "click",
							hideOn: false,
							hideAfter: 1,
							hook: { target: "bottomMiddle", tip: "topMiddle" }
						});
				});
			</script>';
    }
}

    $pagebody .= '<table class="kb-table"><tr valign="top"><td class="card kb-body" width="66%">';

        if ($page['fHidden'] == 1) {
            $pagebody .= displaySystemBox(lg_kb_pagehidden);
        }

        //Replace pre's with formatted ones
        $page['tPage'] = str_replace('<pre', '<pre  class="brush: html" ', $page['tPage']);
        $pagebody .= formatKBBody($page['tPage']);

    $pagebody .= '</td><td class="kb-sidebar">';
        $pagebody .= '<div style="">'.$topbar.$view.'</div>';

        $pagebody .= '
		<div class="sectionhead">'.lg_kb_pagedata.' <div style="float:right;">'.lg_kb_pageid.': '.$pgtree['xPage'].'</div></div>
		<div class="yui-g">
			<div class="yui-u first">
				<label class="datalabel">'.lg_kb_helpfulness.' '.$reset.'</label>
			</div>
			<div class="yui-u kb-pagedata" align="right">
				<span class="kb-helpful">'.$page['iHelpful'].'</span> / <span class="kb-nothelpful">'.$page['iNotHelpful'].'</span>
			</div>
		</div>
		<div class="yui-g kb-pagedata-spacer">
			<div class="yui-u first">
				<label class="datalabel">'.lg_kb_highlightedlabel.'</label>
			</div>
			<div class="yui-u kb-pagedata" align="right">
				'.($page['fHighlight'] ? '<span class="kb-highlighted">'.lg_yes.'</span>' : lg_no).'
			</div>
		</div>
		<div class="yui-g kb-pagedata-spacer">
			<div class="yui-u first">
				<label class="datalabel">'.lg_kb_createdon.'</label>
			</div>
			<div class="yui-u kb-pagedata" align="right">
				'.($page['dtCreatedOn'] == 0 ? '&mdash;' : hs_showDate($page['dtCreatedOn'])).'
			</div>
		</div>
		<div class="yui-g kb-pagedata-spacer">
			<div class="yui-u first">
				<label class="datalabel">'.lg_kb_createdby.'</label>
			</div>
			<div class="yui-u kb-pagedata" align="right">
				'.($page['xPersonCreator'] == 0 ? '&mdash;' : $allStaff[$page['xPersonCreator']]['sFname'].' '.$allStaff[$page['xPersonCreator']]['sLname']).'
			</div>
		</div>
		<div class="yui-g kb-pagedata-spacer">
			<div class="yui-u first">
				<label class="datalabel">'.lg_kb_updatedon.'</label>
			</div>
			<div class="yui-u kb-pagedata" align="right">
				'.($page['dtUpdatedOn'] == 0 ? '&mdash;' : hs_showDate($page['dtUpdatedOn'])).'
			</div>
		</div>
		<div class="yui-g kb-pagedata-spacer">
			<div class="yui-u first">
				<label class="datalabel">'.lg_kb_lastupdateby.'</label>
			</div>
			<div class="yui-u kb-pagedata" align="right">
				'.($page['xPersonLastUpdate'] == 0 ? '&mdash;' : $allStaff[$page['xPersonLastUpdate']]['sFname'].' '.$allStaff[$page['xPersonLastUpdate']]['sLname']).'
			</div>
		</div>
		';

        $tags = apiGetTags($pageid);
        if ($tags) {
            $pagebody .= '<div class="section-wrap"><div class="sectionhead">'.lg_tags_knowledgetags.'</div>';
            foreach ($tags as $k=>$t) {
                $pagebody .= renderTag(md5($t), $t, '');
            }
            $pagebody .= '</div>';
        }

        if (hs_rscheck($docs)) {
            $pagebody .= '<div class="sectionhead">'.lg_kb_downloads.'</div>';
            while ($v = $docs->FetchRow()) {
                $pagebody .= '
					<div class="note-stream-item-attachment-detail">
						<div class="note-stream-item-attachment-icon" onclick="window.location = $jq(\'#download_link_'.$v['xDocumentId'].'\').attr(\'href\');">
							'.hs_showMime($v['sFilename']).'
						</div>
						<strong onclick="window.location = $jq(\'#download_link_'.$v['xDocumentId'].'\').attr(\'href\');">'.hs_htmlspecialchars($v['sFilename']).'</strong>
						<a href="'.route('admin', ['pg' => 'file', 'from' => 2, 'id' => $v['xDocumentId'], 'showfullsize' => 1, 'download' => 1]).'" class="btn inline-action tiny" id="download_link_'.$v['xDocumentId'].'">'.lg_download.'</a>
						<span class="note-stream-item-attachment-filesize">'.decodeSize($v['file_size']).'</span>
					</div>
				';
            }
        }

        if (hs_rscheck($related)) {
            $pagebody .= '<div class="sectionhead" style="">'.lg_kb_related.'</div><ul class="kb-sidebar-ul">';
            while ($r = $related->FetchRow()) {
                $pagebody .= '<li><a href="'.route('admin', ['pg' => 'kb.page', 'page' => $r['xRelatedPage']]).'" class="kb-related-link">'.$r['sPageName'].'</a></li>';
            }
            $pagebody .= '</ul>';
        }

    $pagebody .= '</td></tr></table>';

$pagebody .= syntaxHighligherJS();
