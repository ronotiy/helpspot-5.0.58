<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! isAdmin()) {
    die();
}

include_once cBASEPATH.'/helpspot/lib/class.array2recordset.php';

//Don't let the operation timeout
set_time_limit(0);

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.jobsmgmt']);
$hidePageFrame = 0;
$pagetitle = lg_admin_jobsmgmt_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$feedbackArea = '';
$textoutput = '';

/*****************************************
ACTIONS
*****************************************/

/*****************************************
INFORMATION TABLES
*****************************************/
$jobMgmt = function($task) {
    // todo: Find/replace html encoded to %s
    if ($task == 'retry') {
        $button = lg_admin_jobsmgmt_retry;
        $action = route('jobs.retry', ['job' => '%s']);
        $httpMethod = '<input name="_method" type="hidden" value="PUT">';
    } else {
        $button = lg_admin_jobsmgmt_delete;
        $action = route('jobs.delete', ['job' => '%s']); // todo jobs.delete not working
        $httpMethod = '<input name="_method" type="hidden" value="DELETE">';
    }
    return '<form method="POST" action="'.$action.'">
    '.csrf_field().'
    '.$httpMethod.'
    <button class="btn" type="submit">'.$button.'</button>
</form>';
};

$columns[] = ['type'=>'string', 'label'=>'ID', 'sort'=>0, 'width'=>'40', 'fields'=>'id',];
$columns[] = ['type'=>'string', 'label'=>lg_admin_jobsmgmt_jobname, 'sort'=>0, 'width'=>'150', 'fields'=>'name',];
$columns[] = ['type'=>'string', 'label'=>lg_admin_jobsmgmt_failed_at, 'sort'=>0, 'width'=>'80', 'fields'=>'failed_at_human'];
$columns[] = ['type'=>'json', 'label'=>lg_admin_jobsmgmt_info, 'sort'=>0, 'width'=>'150', 'fields'=>'meta_data_json'];
$columns[] = ['type'=>'link', 'label'=>'', 'align-right'=>true, 'sort'=>0, 'width'=>'80', 'fields'=>'xFilter', 'code'=>$jobMgmt('retry'), 'linkfields'=>['id']];
$columns[] = ['type'=>'link', 'label'=>'', 'align-right'=>true, 'sort'=>0, 'width'=>'80', 'fields'=>'xFilter', 'code'=>$jobMgmt('delete'), 'linkfields'=>['id']];

// Grab all failed jobs
$jobs = \HS\FailedJob::latest('failed_at')
    ->get()
    ->filter(function(\HS\FailedJob $failed) {
        return $failed->getJob()->visibleToAdministrators();
    })->toArray();

$allJobs = new array2recordset;
$allJobs->init($jobs);

// build data table
$jobsTable = recordSetTable($allJobs, $columns, ['title'=>lg_admin_jobsmgmt_title], $basepgurl);

/*****************************************
PAGE OUTPUTS
*****************************************/

$pagebody .= $jobsTable;
