<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request.batch']);
$hidePageFrame = 0;
$tab = 'nav_workspace';
$subtab = '';
$pagetitle = lg_request_batch_title;
$new_filter_id = false;
$uniq_batch_id = uniqid('batch_', true);

/*****************************************
ACTIONS
*****************************************/
//Setup session to store all the POST batch info
session([$uniq_batch_id => $_POST]);

//Create a filter if indicated on request page
if (isset($_POST['build_batch_filter'])) {
    $f = [];
    $f['sFilterName'] = lg_request_batch_batch.': '.hs_showDate(time());
    $f['anyall'] = 'any';
    $f['displayColumns'] = ['iLastReplyBy', 'fOpenedVia', 'fOpen', 'fullname', 'reqsummary', 'age'];
    $f['sFilterFolder'] = lg_request_batch_batchhistory;

    foreach ($_POST['batch'] as $id) {
        $f['condition'.$id.'_1'] = 'xRequest';
        $f['condition'.$id.'_2'] = 'is';
        $f['condition'.$id.'_3'] = $id;
    }

    $rule = new hs_auto_rule();
    $rule->SetAutoRule($f);

    $build['tFilterDef'] = hs_serialize($rule);
    $build['xPerson'] = $user['xPerson'];
    $build['sFilterName'] = $f['sFilterName'];

    $new_filter_id = apiAddEditFilter($build);
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript">
var queue = new Array('.(count($_POST['batch']) == 1 ? '"'.$_POST['batch'][0].'"' : implode(',', $_POST['batch'])).');

var count=0;
function process(){
	if(queue.length > 0){
		//Get the top id and process
		var id = queue.shift();
		var elem = $("r" + id);
		var msg = $("r" + id + "_message");

		//Highlight
		elem.addClassName("batching");
		msg.innerHTML = "'.hs_jshtmlentities(lg_request_batch_processing).'";

		//Increment
		count++;
		$("ct").innerHTML = count;

		//Perform batch on id
		var url  = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'batch_respond']).'&rand=" + ajaxRandomString();
		var pars = "reqid=" + id + "&batch_id='.$uniq_batch_id.'";

		var call = new Ajax.Request(
			url,
			{
				method: 	 "post",
				parameters:  pars,
				onComplete:  function(){

					if(arguments[0].responseText == "error:smtp"){
						//Show error
						elem.removeClassName("batching");
						elem.addClassName("batching_complete_error");
						msg.innerHTML = "'.hs_jshtmlentities(lg_request_batch_smtp).' - <img src=\"'.static_url().'/static/img5/exclamation-triangle-solid.svg\" style=\"vertical-align: middle;\" />";
					}else if(arguments[0].responseText == "error:general"){
						//Show error
						elem.removeClassName("batching");
						elem.addClassName("batching_complete_error");
						msg.innerHTML = "'.hs_jshtmlentities(lg_request_batch_ergeneral).' - <img src=\"'.static_url().'/static/img5/exclamation-triangle-solid.svg\" style=\"vertical-align: middle;\" />";
					}else{
						//Show as complete
						elem.removeClassName("batching");
						elem.addClassName("batching_complete");
						msg.innerHTML = "<img src=\"'.static_url().'/static/img5/check-circle-solid.svg\" style=\"vertical-align: middle;height: 24px;\" />";
					}

					//Run next id
					process();
				}
			});
	}else{
		//Batch over, show navigation out
		new Ajax.Request("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'batch_clear', 'batch_id' => $uniq_batch_id]).'"); //Clear session variable
		$("complete_link").show();
	}
}
</script>';

$onload = 'process();';

/*****************************************
PAGE OUTPUTS
*****************************************/

$pagebody .= '
'.$feedbackArea.'
<form action="'.$basepgurl.'" method="post" name="batchform">'.csrf_field();

$pagebody .= displayContentBoxTop($pagetitle, lg_request_batch_instr, '', '100%', 'card box-noborder box-no-top-margin');

$pagebody .= '<div class="yui-gc"><div class="yui-u first">';
    $pagebody .= '<div id="queue">';

        foreach ($_POST['batch'] as $k=>$v) {
            $req = apiGetRequest($v);
            $pagebody .= '
			<div class="batching_row" id="r'.$v.'" style="display:flex;justify-content:space-between;align-items: center;">
				<div><b>'.$v.'</b> - '.$req['fullname'].'</div>
				<div style="padding-right:10px;font-weight:bold;" id="r'.$v.'_message"></div>
			</div>';
        }

    $pagebody .= '</div>';
$pagebody .= '</div><div class="yui-u"><div class="batching_processed"> ';
    $pagebody .= sprintf(lg_request_batch_processed, '<span id="ct">0</span>', count($_POST['batch']));
$pagebody .= '</div>'.($new_filter_id ? '<div id="complete_link" class="batching_complete_linkbox" style="display:none"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => $new_filter_id]).'">'.lg_request_batch_complete_link.'</a></div>' : '');
$pagebody .= '</div></div>';

$pagebody .= displayContentBoxBottom().'</form>';
