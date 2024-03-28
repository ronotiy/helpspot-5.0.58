<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH') || ! isAdmin()) {
    die();
}

/*****************************************
VARIABLE DECLARATIONS
 *****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.customize']);
$pagetitle = lg_admin_customize;
$tab = 'nav_admin';
$subtab = 'admin-customize';

/*****************************************
JAVASCRIPT
 *****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">

	function saveCustomizations(){
		var url = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'set_admin_customizations']).'&rand=" + ajaxRandomString();
		var pars = {cHD_ADMIN_CSS:$F("cHD_ADMIN_CSS"),cHD_ADMIN_JS:$F("cHD_ADMIN_JS")};

		new Ajax.Updater(
            {success: "feedback_box"},
            url,
			{method: "post", parameters: pars, onFailure: ajaxError,evalScripts: true});
		return false;
	}

</script>
';

/*****************************************
PAGE OUTPUTS
 *****************************************/
$pagebody .= '
<div id="feedback_box"></div>
<form action="'.$basepgurl.'" method="POST" name="themeform" onSubmit="">
	'.csrf_field().'
	'. renderPageheader(lg_admin_customize). '

	<div class="card padded">
		<div class="fr">
			<div class="label">
				<label for="cHD_ADMIN_CSS" class="datalabel">' . lg_admin_customize_css . '</label>
			</div>
			<div class="control">
				<textarea name="cHD_ADMIN_CSS" id="cHD_ADMIN_CSS" cols="30" rows="10">' . hs_setting('cHD_ADMIN_CSS') . '</textarea>
			</div>
		</div>
		<div class="hr"></div>
		<div class="fr">
			<div class="label">
				<label for="theme_portal" class="datalabel">' . lg_admin_customize_js . '</label>
			</div>
			<div class="control">
				<textarea name="cHD_ADMIN_JS" id="cHD_ADMIN_JS" cols="30" rows="10">' . hs_setting('cHD_ADMIN_JS') . '</textarea>
			</div>
		</div>

	</div>

	<div class="button-bar space">
		<button type="button" class="btn accent" onClick="return saveCustomizations();">' . lg_admin_customize_save . '</button>
	</div>

</form>
';
