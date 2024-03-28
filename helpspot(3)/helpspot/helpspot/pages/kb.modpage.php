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
include cBASEPATH.'/helpspot/lib/api.kb.lib.php';
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'kb.modpage']);
$hidePageFrame = 0;
$tab = 'nav_kb';
$subtab = '';
$pagetitle = lg_kb_title;
$chapid = isset($_GET['chapter']) ? $_GET['chapter'] : die();
$pageid = isset($_GET['page']) ? $_GET['page'] : '';
$topiccrumb = '';
$feedback = '';
$relar = [];
$relnamear = [];
$vmode = isset($_GET['vmode']) ? $_GET['vmode'] : 'add';

$allStaff = apiGetAllUsersComplete();

$fm['xChapter'] = isset($_POST['xChapter']) ? $_POST['xChapter'] : 0;
$fm['xBook'] = isset($_POST['xBook']) ? $_POST['xBook'] : 0;
$fm['xPage'] = isset($_POST['xPage']) ? $_POST['xPage'] : 0;
$fm['sPageName'] = isset($_POST['sPageName']) ? $_POST['sPageName'] : '';
$fm['tPage'] = isset($_POST['tPage']) ? $_POST['tPage'] : '';
$fm['tKeywords'] = isset($_POST['tKeywords']) ? $_POST['tKeywords'] : '';
$fm['iOrder'] = isset($_POST['iOrder']) ? $_POST['iOrder'] : 0;
$fm['fHidden'] = isset($_POST['fHidden']) ? $_POST['fHidden'] : 1;
$fm['fHighlight'] = isset($_POST['fHighlight']) ? $_POST['fHighlight'] : 0;
$fm['iHelpful'] = isset($_POST['iHelpful']) ? $_POST['iHelpful'] : 0;
$fm['iNotHelpful'] = isset($_POST['iNotHelpful']) ? $_POST['iNotHelpful'] : 0;
$fm['orderafter'] = isset($_POST['orderafter']) ? $_POST['orderafter'] : '';
$fm['relatedpages'] = isset($_POST['relatedpages']) ? $_POST['relatedpages'] : '';
$fm['tags'] = isset($_POST['tags']) ? $_POST['tags'] : [];
$fm['vmode'] = isset($_POST['vmode']) ? $_POST['vmode'] : '';

$chapter = apiGetChapter($chapid);

$book = apiGetBook($chapter['xBook']);
$editors = hs_unserialize($book['tEditors']);

$tree = apiBuildChapPageTree($chapter['xBook'], true);
$pages = apiTocPages($tree, $chapid);

//Editors only
if (is_array($editors) && ! in_array($user['xPerson'], $editors)) {
    die();
}

//Protect page
if ($book['fPrivate'] == 1 && ! perm('fModuleKbPriv')) {
    return redirect()
        ->route('admin', ['pg' => 'kb']);
}

/*****************************************
PERFORM ACTIONS
*****************************************/
if ($fm['vmode'] == 'add') {
    if (empty($fm['sPageName'])) {
        $feedback = errorBox(lg_errorbox);
        $formerrors['sPageName'] = lg_kb_er_nopagename;
    }

    if (empty($formerrors)) {
        $pageid = apiAddPage($fm, __FILE__, __LINE__);
        return redirect()
            ->route('admin', ['pg' => 'kb.modpage', 'chapter' => $chapid, 'page' => $pageid]);
    }
} elseif ($fm['vmode'] == 'update') {
    if (empty($fm['sPageName'])) {
        $feedback = errorBox(lg_errorbox);
        $formerrors['sPageName'] = lg_kb_er_nopagename;
    }

    if (empty($formerrors)) {
        $update = apiUpdatePage($fm, __FILE__, __LINE__);
        return redirect()
            ->route('admin', ['pg' => 'kb.page', 'page' => $pageid]);
    }
} elseif ($vmode == 'delete') {
    apiDeletePage($pageid);
    return redirect()
        ->route('admin', ['pg' => 'kb.book', 'book' => $chapter['xBook']]);
} elseif ($vmode == 'deldownload') {
    //delete docs
    $deldoc = $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Documents WHERE xDocumentId = ?', [$_GET['docid']]);
    return redirect()
        ->route('admin', ['pg' => 'kb.modpage', 'chapter' => $chapid, 'page' => $pageid]);
}
/*****************************************
PAGE TEMPLATE COMPONENTS
*****************************************/
if (! empty($pageid)) {
    $fm = apiGetPage($pageid);
    $fm['tags'] = apiGetTags($pageid);
    $title = lg_kb_edit.' '.$fm['sPageName'];
    $button = lg_kb_editbutton;
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_kb_deletepagewarn.'\',\''.$basepgurl.'&vmode=delete&chapter='.$chapid.'&page='.$pageid.'\');">'.lg_kb_deletepage.'</button>';
    $vmode = 'update';

    $chaptoc = apiGetAllBookChapters();
    $chaporderlist = '';
    if (hs_rscheck($chaptoc)) {
        while ($c = $chaptoc->FetchRow()) {
            $chaporderlist .= '<option value="'.$c['xChapter'].'" '.selectionCheck($c['xChapter'], $chapid).'>'.$c['sBookName'].' - '.$c['sChapterName'].'</option>';
        }
    }
} else {
    $title = lg_kb_addpage;
    $button = lg_kb_addpage;
    $delbutton = '';
}

$afterorderlist = '<option value="0">'.lg_kb_firstpage.'</option>';
if (is_array($pages)) {
    foreach ($pages as $p) {
        if ($p['xPage'] != $pageid) {
            $afterorderlist .= '<option value="'.$p['xPage'].'" '.selectionCheck($p['iOrder'], ($fm['iOrder'] - 1)).'>'.lg_kb_after.' '.$p['sPageName'].'</option>';
        }
    }
}

$related = apiGetRelatedPages($pageid);
if (hs_rscheck($related)) {
    while ($r = $related->FetchRow()) {
        $relar[] = $r['xRelatedPage'];
        $relnamear[] = '<li><b>'.$r['sBookName'].' / '.$r['sChapterName'].' / '.$r['sPageName'].'</b></li>';
    }
    $rellist = is_array($relar) ? implode(',', $relar) : '';
    $relnames = is_array($relnamear) ? implode(' ', $relnamear) : '';
    $relnames = '<ul style="list-style-type:none;margin-top:0px;">'.$relnames.'</ul>';
}
/*****************************************
JAVASCRIPT
*****************************************/
if (hs_setting('cHD_WYSIWYG') == 1 && $user['fHideWysiwyg'] == 0) {
    $headscript .= wysiwyg_load('tPage', 'kb', $pageid);
}

$headscript .= '
	<script type="text/javascript" language="JavaScript">
	//add another field for file uploads
	function addAnotherFile(){
		newfile = document.createElement("span");
		newfile.innerHTML = "<input type=\"file\" size=\"40\" name=\"doc[]\"><br><br> ";
		document.getElementById("downloadaddfile").appendChild(newfile);
	}

	$jq().ready(function(){
		new Ajax.Autocompleter("tagInput","search-box-small-autocomplete", "'.route('admin', ['pg' => 'ajax_gateway', 'action' => 'tag_autocomplete']).'", {paramName:"search", minChars: 1, frequency:0.1});
	});
	</script>
';
$onload = 'setFieldFocus(document.getElementById(\'sPageName\'))';

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($pageid)) {
    $page_crumb = '  &nbsp; : &nbsp;  <a href="'.route('admin', ['pg' => 'kb.page', 'page' => $pageid]).'">'.$fm['sPageName'].'</a>';
}

$pagebody .= renderPageheader('<b class="breadcrumb">
				<a href="'.route('admin', ['pg' => 'kb']).'">'.lg_kb_home.'</a>  &nbsp; / &nbsp;
				<a href="'.route('admin', ['pg' => 'kb.book', 'book' => $book['xBook']]).'">'.$book['sBookName'].'</a>  &nbsp; / &nbsp;
				<a href="'.route('admin', ['pg' => 'kb.book', 'book' => $book['xBook']]).'">'.$chapter['sChapterName'].'</a> &nbsp; / &nbsp;  '.$title.'</b>');

$pagebody .= $feedback;

$pagebody .= '<form action="'.route('admin', ['pg' => 'kb.modpage', 'page' => $pageid, 'chapter' => $chapid]).'" enctype="multipart/form-data" method="post" name="modpageform">';
$pagebody .= csrf_field();
$pagebody .= '
    <div class="card padded">

        <div class="fr">
            <div class="label">
                <label class="req" for="sPageName">'.lg_kb_pagename.'</label>
            </div>
            <div class="control">
                <input type="text" name="sPageName" id="sPageName" value="'.$fm['sPageName'].'" size="75" maxlength="255" class="'.errorClass('sPageName').'">
                '.errorMessage('sPageName').'
            </div>
        </div>';

    if (! empty($pageid)) {
        $pagebody .= '
        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="req" for="fHidden">'.lg_kb_hidden.'</label>
                <div class="info">'.lg_kb_hiddenpagedesc.'</div>
            </div>
            <div class="control">
                '.renderYesNo('fHidden', $fm['fHidden'], lg_yes, lg_no).'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="req" for="tPage">'.lg_kb_pagebody.'</label>
            </div>
            <div class="control kb-bordered">
                <textarea name="tPage" id="tPage" cols="60" rows="20" style="width:100%;">'.formCleanHtml($fm['tPage']).'</textarea>
            </div>
        </div>

		';

        $pagebody .= tagUI($fm['tags'], lg_tags_knowledgetags, 'tags[]');

        $pagebody .= '
		<div class="sectionhead">'.lg_kb_pagedetails.'</div>';
    }

    if (! empty($pageid)) {
        $pagebody .= '
		<div class="fr">
			<div class="label">
			    <label class="datalabel" for="xChapter">'.lg_kb_inchapter.'</label>
			</div>
			<div class="control">
				<select tabindex="100" name="xChapter" id="xChapter" class="'.errorClass('xChapter').'">
					'.$chaporderlist.'
				</select>
			</div>
		</div>

		<div class="hr"></div>
		';
    } else {
        $pagebody .= '<input type="hidden" name="xChapter" value="'.$chapid.'">';
    }

    $pagebody .= '
		<div class="fr">
			<div class="label">
			    <label class="datalabel" for="orderafter">'.lg_kb_orderafter.'</label>
			</div>
			<div class="control">
				<select tabindex="100" name="orderafter" id="orderafter" class="'.errorClass('orderafter').'">
					'.$afterorderlist.'
				</select>
			</div>
		</div>';

    //DON'T SHOW UNTIL PAGE CREATED
    if (! empty($pageid)) {
        $pagebody .= '
		<div class="hr"></div>

		<div class="fr">
			<div class="label">
			    <label class="datalabel" for="fHighlight">'.lg_kb_highlight.'</label>
			    <div class="info">'.lg_kb_highlightdesc.'</div>
			</div>
			<div class="control">
				<select tabindex="100" name="fHighlight" id="fHighlight" class="'.errorClass('fHighlight').'">
					<option value="0" '.selectionCheck(0, $fm['fHighlight']).'>'.lg_no.'</option>
					<option value="1" '.selectionCheck(1, $fm['fHighlight']).'>'.lg_yes.'</option>
				</select>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
			    <label class="datalabel" for="">'.lg_kb_download.'</label>
			</div>
			<div class="control">
                <div style="display:flex;flex-direction:column;">';
                $docs = apiGetPageDocs($pageid);
                if (hs_rscheck($docs)) {
                    while ($v = $docs->FetchRow()) {
                        $pagebody .= '
        							<div class="note-stream-item-attachment-detail" style="margin-top:0px;">
        								<div class="note-stream-item-attachment-icon" onclick="window.location = $jq(\'#download_link_'.$v['xDocumentId'].'\').attr(\'href\');">
        									'.hs_showMime($v['sFilename']).'
        								</div>
        								<strong onclick="window.location = $jq(\'#download_link_'.$v['xDocumentId'].'\').attr(\'href\');">'.hs_htmlspecialchars($v['sFilename']).'</strong>
                                        <a href="'.route('admin', ['pg' => 'file', 'from' => 2, 'id' => $v['xDocumentId'], 'showfullsize' => 1, 'download' => 1]).'" id="download_link_'.$v['xDocumentId'].'" class="btn inline-action tiny">'.lg_download.'</a>
        								<a href="'.route('admin', ['pg' => 'kb.modpage', 'chapter' => $chapid, 'page' => $pageid, 'vmode' => 'deldownload', 'docid' => $v['xDocumentId']]).'" onClick="return hs_confirm(\''.hs_jshtmlentities(lg_kb_deldoccheck).'\',this.href);" class="btn inline-action tiny secondary">'.lg_kb_deldoc.'</a>
        							</div>
        						';
                    }
                }

                $pagebody .= '

    				<span name="downloadaddfilew" id="downloadaddfile"></span>
    				<a href="javascript:addAnotherFile();" class="btn">'.lg_kb_adddownload.'</a>
                </div>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
			    <label class="datalabel" for="">'.lg_kb_related.'</label>
			</div>
			<div class="control">
                <div style="display:flex;flex-direction:column;">
    				<span name="relatedview" id="relatedview">'.$relnames.'</span>
    				<a href="#" onclick="hs_overlay({width:\'600px\',href:\'admin?pg=ajax_gateway&action=kbui-related&page='.$pageid.'&priv='.$book['fPrivate'].'\'});return false;" class="btn">'.lg_kb_addeditrelated.'</a>
    				<input type="hidden" name="relatedpages" id="relatedpages" value="'.$rellist.'">
                </div>
			</div>
		</div>';
    }

$pagebody .= '
</div>

<input type="hidden" name="xPage" value="'.$pageid.'">
<input type="hidden" name="vmode" value="'.$vmode.'">';

$pagebody .= '<div class="button-bar space">
                <button type="submit" name="submit" class="btn accent">'.$button.'</button>'.$delbutton.'
                </div>

</form>';
