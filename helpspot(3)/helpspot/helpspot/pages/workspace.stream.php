<?php
// SECURITY: Don't allow direct calls
if (!defined('cBASEPATH')) die();

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('workspace');

/*****************************************
LIBS
*****************************************/
include(cBASEPATH.'/helpspot/lib/api.users.lib.php');
include(cBASEPATH.'/helpspot/lib/class.requestupdate.php');
include(cBASEPATH.'/helpspot/lib/api.requests.lib.php');

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'workspace.stream']);
$pagetitle = lg_workspacestream_title;
$tab       = 'nav_workspace';
$subtab    = '';
$show      = isset($_GET['show']) ? trim($_GET['show']) : $user['sWorkspaceDefault'];
$reqids    = array();
$efb        = isset($_GET['efb']) && !hs_empty($_GET['efb']) ? errorBox(urldecode($_GET['efb']),'100%') : '';
$fb         = isset($_GET['fb']) && !hs_empty($_GET['fb']) ? displayFeedbackBox(urldecode(hs_htmlspecialchars($_GET['fb'])),'100%') : '';

// All user filters
$filters = apiGetAllFilters($user['xPerson'], 'all');

/*****************************************
ACTIONS
*****************************************/
switch($show){
    case "inbox":
        // Security: don't show inbox to guests or L2
        if(!perm('fViewInbox')) die();

        $ft = new hs_filter();
        $ft->idsOnly = true;
        $ft->useSystemFilter('inbox');
        $reqids = $ft->outputReqIDs();
        break;
    case "myq":
        $ft = new hs_filter();
        $ft->idsOnly = true;
        $ft->useSystemFilter('myq');
        $reqids = $ft->outputReqIDs();
        break;
    default:
        if(is_numeric($show) && isset($filters[$show])){
            $ft = new hs_filter($filters[$show]);
            $ft->idsOnly = true;
            $reqids = $ft->outputReqIDs();
        }else{
            $reqids = false;
        }
        break;
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript  = '
<script type="text/javascript" language="JavaScript">
    function getMostRecentID(){
         return $jq("#stream-box_box_body [id^=xRequestHistory-]").first().attr("id").replace("xRequestHistory-","");
    }

    function getOldestID(){
         return $jq("#stream-box_box_body .note-stream-item[id^=xRequestHistory-]").last().attr("id").replace("xRequestHistory-","");
    }

    $jq(document).ready(function(){
        $jq.post("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'stream']).'",$jq("#streamform").serialize(),function(data){
            $jq("#stream-box_box_body").html(data);
            $jq("#last_xRequestHistory").val(getMostRecentID());
            $jq("#oldest_xRequestHistory").val(getOldestID());
        });
    });

    function changeStreamType(){
        $jq("#last_xRequestHistory").val(0);
        $jq("#stream-box_box_body").html(ajaxLoading());
        pollStream();
    }

    function pollStream(){
        $jq.post("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'stream']).'",$jq("#streamform").serialize(),function(data){
            if(data != ""){
                //Remove loader if its there
                $jq("#stream-box_box_body .inline_loading").remove();
                //Add line above previous item
                if($jq("#last_xRequestHistory").val() > 0) $jq("#xRequestHistory-" + getMostRecentID()).css({borderTop:"2px solid #ff8e46"});
                //Insert new items
                $jq("#stream-box_box_body").prepend(data);
                //Set newest most recent. Must call getMostRecentID() twice as this changes after the prepend
                $jq("#last_xRequestHistory").val(getMostRecentID());

                //Set the oldest request history id
                $jq("#oldest_xRequestHistory").val(getOldestID());
            }
        });
    }
    hs_PeriodicalExecuter("pollStream",pollStream,'.cHD_STREAM_VIEW_REFRESH.');

    function loadMore(){
        //Set limit negative
        $jq("#limit").val(-20);
        $jq.post("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'stream']).'",$jq("#streamform").serialize(),function(data){
            if(data != ""){
                //remove load more
                $jq("#note-stream-load-more").remove();

                //Insert new items
                $jq("#stream-box_box_body").append(data);

                //Set the oldest request history id
                $jq("#oldest_xRequestHistory").val(getOldestID());

                //reset limit
                $jq("#limit").val(20);
            }else{
                $jq("#note-stream-load-more").remove();
            }
        });
    }

</script>
';

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody .= '';
$pagebody .= renderPageheader(lg_workspacestream_streaming.': '.$ft->filterDef['sFilterName'], '
    <form name="streamform" id="streamform">
        <input type="hidden" name="limit" id="limit" value="20" />
        <input type="hidden" name="reqids" id="reqids" value="'.implode(',',$reqids).'" />
        <input type="hidden" name="last_xRequestHistory" id="last_xRequestHistory" value="0" />
        <input type="hidden" name="oldest_xRequestHistory" id="oldest_xRequestHistory" value="0" />
        <div class="table-top-menu">
            <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => $show]).'" id="table-top-menu-grid">'.lg_workspacestream_grid.'</a>
            <select name="sFilterView" id="sFilterView" onchange="changeStreamType();" style="align-self: center;margin: 0 0 0 20px;">
                <option value="stream" '.selectionCheck('stream',$filters[$show]['sFilterView']).'>'.lg_workspacestream_stream.'</option>
                <option value="stream-priv" '.selectionCheck('stream-priv',$filters[$show]['sFilterView']).'>'.lg_workspacestream_streamwpriv.'</option>
                <option value="stream-cust" '.selectionCheck('stream-cust',$filters[$show]['sFilterView']).'>'.lg_workspacestream_streamcustomers.'</option>
                <option value="stream-cust-staff" '.selectionCheck('stream-cust-staff',$filters[$show]['sFilterView']).'>'.lg_workspacestream_streamcuststaff.'</option>
            </select>
        </div>
    </form>
');

$pagebody .= '<div id="stream-box_box_body"></div>';

?>
