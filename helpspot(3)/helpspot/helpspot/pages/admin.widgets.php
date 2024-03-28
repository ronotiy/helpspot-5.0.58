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
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.widgets']);
$pagetitle = lg_admin_widgets_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_widgets';

/*****************************************
PERFORM ACTIONS
*****************************************/

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody .= '

'. renderPageheader($pagetitle). '

	<div class="card padded">
		<div class="fr">
			<div class="label">
				<label class="datalabel">' . lg_admin_widgets_questiontab . '</label>
				<div class="info">
					<p>' . lg_admin_widgets_questiontabdesc . '</p>
					<ul style="padding-left:15px;">
						<li style="padding-bottom:5px;">' . lg_admin_widgets_questiontab1 . '</li>
						<li style="padding-bottom:5px;">' . lg_admin_widgets_questiontab2 . '</li>
						<li style="padding-bottom:5px;">' . lg_admin_widgets_questiontab3 . '</li>
						<li style="padding-bottom:5px;">' . lg_admin_widgets_questiontab4 . '</li>
					</ul>
				</div>
			</div>
			<div class="control">
				<img src="' . static_url() . '/static/img/admin_widget_tab.png" width="622" height="433" style="max-width: 622px; margin-left: 40px;">
			</div>
		</div>

	'.displayContentBoxTop(lg_admin_widgets_code, lg_admin_widgets_tabex, '', '100%').'
<div class="code-wrap">
<pre  class="brush: html" style="height:200px;" id="tab_code">&lt;style type="text/css"&gt;@import url(\''.cHOST.'/widgets/widgets.css\');&lt;/style&gt;
&lt;script type="text/javascript" src="'.cHOST.'/widgets/widgets.js"&gt;&lt;/script&gt;
&lt;script type="text/javascript"&gt;
HelpSpotWidget.Tab.show({
	// Nearly every aspect of the widget is customizable, complete documentation here:
	// http://www.helpspot.com/helpdesk/index.php?pg=kb.page&id=323
	host: \''.cHOST.'\'
});
&lt;/script&gt;</pre>
</div>

	'.syntaxHighligherJS().'
	'.displayContentBoxBottom(). '

	</div>
';
