<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! isAdmin()) {
    die();
}

/*****************************************
LIBS
*****************************************/

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.themes']);
$pagetitle = lg_admin_themes_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_themes';

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">

	function changeStyle(){
		$("theme_stylesheet").writeAttribute("href","themes/"+$F("theme")+"/"+$F("theme")+".css");
	}

	function changePortalStyle(){
		var portaltheme = $jq("#theme_portal").val();
		$jq("#theme_portal_img").attr("src","'.static_url().'/static/img5/portal_"+portaltheme+".jpg");
		$jq(\'[id^="portal-theme-desc-"]\').hide();
		$jq("#portal-theme-desc-"+portaltheme).show();
	}

	function setTheme(){
		var url = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'set_theme']).'&rand=" + ajaxRandomString();
		var pars = {cHD_THEME_PORTAL:$F("theme_portal")};

		var updateWs = new Ajax.Updater(
						{success: "feedback_box"},
						url,
						{method: "post", parameters: pars, onFailure: ajaxError,evalScripts: true});
	}

	$jq(document).ready(function(){
		changePortalStyle();

		preloadImages("'.static_url().'/static/img5/portal_clean.jpg",
					  "'.static_url().'/static/img5/portal_grey.jpg",
					  "'.static_url().'/static/img5/portal_blue.jpg",
					  "'.static_url().'/static/img5/portal_classic.jpg");
	});

</script>
';

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody .= '
<div id="feedback_box"></div>
<form action="'.$basepgurl.'" method="POST" name="themeform" onSubmit="">
	'.csrf_field().'
	'. renderPageheader(lg_admin_themes_admin). '

	<div class="card padded">
		<div class="fr">
			<div class="label">
				<label for="theme_portal" class="datalabel">' . lg_admin_themes_portaltheme . '</label>
				<div class="info">' . lg_admin_themes_custom . '</div>
			</div>
			<div class="control">
				<select id="theme_portal" onchange="changePortalStyle();">
					<option value="clean" ' . selectionCheck('clean', hs_setting('cHD_THEME_PORTAL')) . '>' . lg_admin_themes_pt_clean . '</option>
					<option value="grey" ' . selectionCheck('grey', hs_setting('cHD_THEME_PORTAL')) . '>' . lg_admin_themes_pt_grey . '</option>
					<option value="blue" ' . selectionCheck('blue', hs_setting('cHD_THEME_PORTAL')) . '>' . lg_admin_themes_pt_blue . '</option>
					<option value="classic" ' . selectionCheck('classic', hs_setting('cHD_THEME_PORTAL')) . '>' . lg_admin_themes_pt_classic . '</option>
				</select>
			</div>
		</div>
		<div class="hr"></div>
		<div class="fr">
			<div class="label">
				<div class="portal-theme-desc" id="portal-theme-desc-clean" style="display:none;">' . lg_admin_themes_pt_cleandesc . '</div>
				<div class="portal-theme-desc" id="portal-theme-desc-grey" style="display:none;">' . lg_admin_themes_pt_greydesc . '</div>
				<div class="portal-theme-desc" id="portal-theme-desc-blue" style="display:none;">' . lg_admin_themes_pt_bluedesc . '</div>
				<div class="portal-theme-desc" id="portal-theme-desc-classic" style="display:none;">' . lg_admin_themes_pt_classicdesc . '</div>
			</div>
			<div class="control">
				<div class="portal-theme-img">
					<img src="' . static_url() . '/static/img5/space.gif" id="theme_portal_img" border="0" />
				</div>
			</div>
		</div>

	</div>

	<div class="button-bar space">
		<button type="button" name="submit" class="btn accent" onclick="return setTheme();">' . lg_admin_themes_save . '</button>
	</div>

</form>
';
