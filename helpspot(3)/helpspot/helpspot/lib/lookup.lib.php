<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/******************************************
LOOKUP TABLES
******************************************/
$GLOBALS['userscapesupport'] = 'http://www.helpspot.com/helpdesk';

// Flags how a request was opened
$GLOBALS['openedVia'] = [1=>lg_lookup_email,
                              2=>lg_lookup_phone,
                              3=>lg_lookup_walkin,
                              4=>lg_lookup_mail,
                              5=>lg_lookup_other,
                              6=>lg_lookup_webservice,
                              7=>lg_lookup_webform,
                              8=>lg_lookup_forum,
                              9=>lg_lookup_im,
                              10=>lg_lookup_fax,
                              11=>lg_lookup_voicemail,
                              12=>lg_lookup_staffinit,
                              13=>lg_lookup_widget,
                              14=>lg_lookup_mobile, ];

$GLOBALS['imageMimeTypes'] = ['image/png', 'image/gif', 'image/jpeg', 'image/pjpeg'];
$GLOBALS['audioMimeTypes'] = ['audio/x-aiff', 'audio/aiff', 'audio/x-m4a', 'audio/m4a', 'audio/x-m4b', 'audio/m4b', 'audio/x-m4p', 'audio/m4p', 'audio/mp3', 'audio/x-mpeg', 'audio/mpeg', 'audio/mpeg3', 'audio/x-wav', 'audio/wav'];

// Flags for workflow
$GLOBALS['wordflow'] = [1=>lg_wf_open,
                              2=>lg_wf_single, ];

// Custom Field Names
$GLOBALS['customFieldTypes'] = ['select'=>lg_lookup_cfields_dropdown,
                                     'text'=>lg_lookup_cfields_text,
                                     'lrgtext'=>lg_lookup_cfields_lrgtext,
                                     'checkbox'=>lg_lookup_cfields_checkbox,
                                     'numtext'=>lg_lookup_cfields_numfield,
                                     'decimal'=>lg_lookup_cfields_decimal,
                                     'regex'=>lg_lookup_cfields_regex,
                                     'date'=>lg_lookup_cfields_date,
                                     'datetime'=>lg_lookup_cfields_datetime,
                                     'drilldown'=>lg_lookup_cfields_drilldown,
                                     'ajax'=>lg_lookup_cfields_ajax, ];

$GLOBALS['timeGroupings'] = ['time:today-yesterday'=>lg_lookup_filter_timegroup_today_yesterday,
                                  'time:hourly'			=>lg_lookup_filter_timegroup_hourly,
                                  'time:daily'			=>lg_lookup_filter_timegroup_daily,
                                  'time:monthly'		=>lg_lookup_filter_timegroup_monthly, ];

$GLOBALS['filterCols'] = ['takeitfilter'=>['type'=>'link', 'label'=>lg_lookup_filter_takeit, 'sort'=>0, 'nowrap'=>true, 'width'=>'30',
                                                  //'code'=>'<a href="admin?pg=request&reqid=%s&frominbox=1">'.lg_lookup_filter_takeit.'</a>',
                                                  //'code'=>'<input type="button" value="'.lg_lookup_filter_takeit.'" onClick="goPage(\'admin?pg=request&reqid=%s&takeitfilter=1&rand='.time().'\');" class="" rel="%s">',
                                                  'code'=>'<a href="'.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => '%s', 'frominbox' => 1, 'rand'=> time(), 'takeitfilter' => '1'])).'" class="btn accent-alt inline-action" id="takeitfilter-%s">'.lg_lookup_filter_takeit.'</a>',
                                                  'fields'=>'xRequest', 'linkfields'=>['xRequest', 'xRequest'], ],
                               'xPersonPhoto'=>['type'=>'string', 'label'=>'', 'sort'=>0, 'width'=>'34', 'fields'=>['xPerson'], 'function'=>'xPersonPhotoUrl', 'function_args'=>['xPerson']],
                               'isunread'=>['type'=>'string', 'label'=>'', 'sort'=>1, 'width'=>'10', 'fields'=>['xRequest', 'iLastReadCount'], 'function'=>'isUnread', 'function_args'=>['xRequest', 'history_ct', 'iLastReadCount', 'iLastReplyBy']],
                               'iLastReplyBy'=>['type'=>'string', 'label'=>lg_lookup_filter_isrepliedto, 'label2'=>lg_lookup_filter_isrepliedto2, 'sort'=>1, 'width'=>'10', 'fields'=>['xRequest', 'iLastReplyBy'], 'function'=>'isRepliedTo', 'function_args'=>['xRequest', 'iLastReplyBy']],
                               'fOpenedVia'=>['type'=>'openedvia', 'label'=>lg_lookup_filter_openedvia, 'label2'=>lg_lookup_filter_openedvia2, 'sort'=>0, 'width'=>'20', 'fields'=>'fOpenedVia'],
                               'mailbox'=>['type'=>'string', 'label'=>lg_lookup_filter_mailbox, 'sort'=>0, 'width'=>'150', 'fields'=>'xOpenedViaId', 'function'=>'hs_mailbox_from_id'],
                               'xPortal'=>['type'=>'string', 'label'=>lg_lookup_filter_portal, 'sort'=>0, 'width'=>'150', 'fields'=>'xPortal', 'function'=>'apiGetPortalName', 'function_args'=>['xPortal', 'fOpenedVia']],
                               'fOpen'=>['type'=>'bool', 'label'=>lg_lookup_filter_open, 'label2'=>lg_lookup_filter_open2, 'sort'=>1, 'width'=>'10', 'fields'=>'fOpen'],
                               'xRequest'=>['type'=>'string', 'label'=>lg_lookup_filter_reqid, 'label2'=>lg_lookup_filter_reqid2, 'sort'=>1, 'fields'=>'xRequest'],
                               'sUserId'=>['type'=>'string', 'label'=>lg_lookup_filter_custid, 'sort'=>1, 'width'=>'80', 'fields'=>'sUserId'],
                               'fullname'=>['type'=>'string', 'label'=>lg_lookup_filter_custname, 'sort'=>1, 'nowrap'=>true, 'fields'=>'fullname', 'width'=>110],
                               'sLastName'=>['type'=>'string', 'label'=>lg_lookup_filter_lastname, 'sort'=>1, 'width'=>'100', 'fields'=>'sLastName'],
                               'sEmail'=>['type'=>'string', 'label'=>lg_lookup_filter_custemail, 'sort'=>1, 'width'=>'100', 'fields'=>'sEmail'],
                               'sPhone'=>['type'=>'string', 'label'=>lg_lookup_filter_custphone, 'sort'=>1, 'width'=>'100', 'fields'=>'sPhone'],
                               'xPersonOpenedBy'=>['type'=>'string', 'label'=>lg_lookup_filter_openedby, 'sort'=>1, 'width'=>'100', 'fields'=>'xPersonOpenedBy', 'function'=>'hs_personName'],
                               'xPersonAssignedTo'=>['type'=>'string', 'label'=>lg_lookup_filter_assignedto, 'sort'=>1, 'width'=>'100', 'fields'=>'xPersonAssignedTo', 'function'=>'hs_personName'],
                               'xStatus'=>['type'=>'string', 'label'=>lg_lookup_filter_status, 'sort'=>1, 'width'=>'100', 'fields'=>'sStatus'],
                               'sCategory'=>['type'=>'string', 'label'=>lg_lookup_filter_category, 'sort'=>1, 'width'=>'100', 'fields'=>'sCategory'],
                               'sTitle'=>['type'=>'string', 'label'=>lg_lookup_filter_emailtitle, 'sort'=>1, 'width'=>'', 'nowrap'=>true, 'fields'=>'sTitle', 'chars'=>75],
                               'reqsummary'=>['type'=>'string', 'label'=>lg_lookup_filter_reqsummary, 'sort'=>0, 'width'=>'', 'fields'=>'tNote', 'hideflow'=>true, 'function'=>'initRequestClean'],
                               'lastpublicnote'=>['type'=>'string', 'label'=>lg_lookup_filter_lastpublicnote, 'sort'=>0, 'width'=>'', 'fields'=>'lastpublicnote', 'hideflow'=>true],
                               'lastpublicnoteby'=>['type'=>'string', 'label'=>lg_lookup_filter_lastpublicnoteby, 'sort'=>0, 'width'=>'120', 'fields'=>'lastpublicnoteby', 'function'=>'hs_personNameNotes'],
                               'lastupdateby'=>['type'=>'string', 'label'=>lg_lookup_filter_lastupdateby, 'sort'=>0, 'width'=>'120', 'fields'=>'lastupdateby', 'function'=>'hs_personNameNotes'],
                               'dtGMTOpened'=>['type'=>'string', 'label'=>lg_lookup_filter_timeopen, 'label2'=>lg_lookup_filter_timeopen2, 'sort'=>1, 'width'=>'110', 'fields'=>'dtGMTOpened', 'function'=>'hs_showShortDate'],
                               'dateTimeOpened'=>['type'=>'string', 'label'=>lg_lookup_filter_timeopen, 'label2'=>lg_lookup_filter_datetimeopen, 'sort'=>1, 'width'=>'150', 'fields'=>'dtGMTOpened', 'function'=>'hs_showDate'],
                               'dtGMTClosed'=>['type'=>'string', 'label'=>lg_lookup_filter_timeclosed, 'label2'=>lg_lookup_filter_timeclosed2, 'sort'=>1, 'width'=>'110', 'fields'=>'dtGMTClosed', 'function'=>'hs_showShortDate'],
                               'dateTimeClosed'=>['type'=>'string', 'label'=>lg_lookup_filter_timeclosed, 'label2'=>lg_lookup_filter_datetimeclosed, 'sort'=>1, 'width'=>'150', 'fields'=>'dtGMTClosed', 'function'=>'hs_showDate'],							   'dtGMTTrashed'=>['type'=>'string', 'label'=>lg_lookup_filter_trashedon, 'sort'=>1, 'width'=>'160', 'fields'=>'dtGMTTrashed', 'function'=>'hs_showDate'],
                               'lastupdate'=>['type'=>'string', 'label'=>lg_lookup_filter_lastupdate, 'sort'=>1, 'width'=>'160', 'fields'=>'lastupdate', 'function'=>'hs_showDate'],
                               'lastpubupdate'=>['type'=>'string', 'label'=>lg_lookup_filter_lastpubupdate, 'sort'=>1, 'width'=>'160', 'fields'=>'lastpubupdate', 'function'=>'hs_showDate'],
                               'lastcustupdate'=>['type'=>'string', 'label'=>lg_lookup_filter_lastcustupdate, 'sort'=>1, 'width'=>'160', 'fields'=>'lastcustupdate', 'function'=>'hs_showDate'],
                               'thermostat_nps_score'=>['type'=>'string', 'label'=>lg_lookup_filter_thermostat_nps_score, 'sort'=>1, 'width'=>'160', 'fields'=>'thermostat_nps_score', 'function' => 'hs_showNpsScore'],
                               'thermostat_csat_score'=>['type'=>'string', 'label'=>lg_lookup_filter_thermostat_csat_score, 'sort'=>1, 'width'=>'160', 'fields'=>'thermostat_csat_score', 'function' => 'hs_showCsatScore'],
                               'thermostat_feedback'=>['type'=>'string', 'label'=>lg_lookup_filter_thermostat_feedback, 'sort'=>1, 'width'=>'200', 'fields'=>'thermostat_feedback'],
                               'ctPublicUpdates'=>['type'=>'string', 'label'=>lg_lookup_filter_ctpublicupdates, 'label2'=>lg_lookup_filter_ctpublicupdates2, 'sort'=>1, 'width'=>'10', 'fields'=>'ctPublicUpdates'],
                               'speedtofirstresponse'=>['type'=>'string', 'label'=>lg_lookup_filter_speedtofirstresponse, 'sort'=>1, 'width'=>'140', 'fields'=>'speedtofirstresponse', 'function'=>'parseSecondsToTimeWlabel'],
                               'speedtofirstresponse_biz'=>['type'=>'string', 'label'=>lg_lookup_filter_speedtofirstresponse_biz, 'sort'=>1, 'width'=>'140', 'fields'=>'speedtofirstresponse_biz', 'function'=>'getBizHours', 'function_args'=>['dtGMTOpened', 'speedtofirstresponse_biz']],
                               'reportingTags'=>['type'=>'string', 'label'=>lg_lookup_filter_reportingtags, 'sort'=>0, 'width'=>'200', 'fields'=>'reportingTags'],
                               'age'=>['type'=>'string', 'label'=>lg_lookup_filter_age, 'sort'=>1, 'width'=>'130', 'fields'=>'dtGMTOpened', 'function'=>'time_since'],
                               'attachment'=>['type'=>'bool', 'label'=>lg_lookup_filter_attachment, 'label2'=>lg_lookup_filter_attachment2, 'sort'=>0, 'width'=>'16', 'fields'=>'attachment_ct', 'img'=>static_url().'/static/img5/paperclip-regular.svg', 'noimg'=>static_url().'/static/img5/space.gif'],
                               'takeit'=>['type'=>'link', 'label'=>'', 'sort'=>0, 'nowrap'=>true, 'width'=>'30',
                                                  //'code'=>'<a href="admin?pg=request&reqid=%s&frominbox=1">'.lg_lookup_filter_takeit.'</a>',
                                                  //'code'=>'<input type="button" value="'.lg_lookup_filter_takeit.'" onClick="goPage(\'admin?pg=request&reqid=%s&frominbox=1&rand='.time().'\');" class="" rel="%s">',
                                                  'code'=>'<a href="'.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => '%s', 'frominbox' => 1, 'rand'=> time(), 'takeitfilter' => '1'])).'" class="btn accent-alt inline-action" id="takeit-%s">'.lg_lookup_filter_takeit.'</a>',
                                                  'fields'=>'xRequest', 'linkfields'=>['xRequest', 'xRequest'], ],
                               'view'=>['type'=>'link', 'label'=>lg_lookup_filter_reqid, 'sort'=>1, 'nowrap'=>true, 'width'=>'30',
                                                  'code'=>'<a href="'.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => '%s'])).'" class="reqid-link" id="link-%s">%s</a>',
                                                  'fields'=>'xRequest', 'linkfields'=>['xRequest', 'xRequest', 'xRequest'], ],
                                 'timetrack'=>['type'=>'string', 'align-right'=>true, 'label'=>lg_lookup_filter_timetrack, 'label2'=>lg_lookup_filter_timetrack2, 'sort'=>1, 'width'=>'35', 'fields'=>'timetrack', 'function'=>'parseSecondsToTime'], ];

//add any custom fields to filter cols array and add date column to time grouping
if (isset($GLOBALS['customFields']) && is_array($GLOBALS['customFields'])) {
    foreach ($GLOBALS['customFields'] as $cfV) {
        $cfFid = 'Custom'.$cfV['fieldID'];
        if ($cfV['fieldType'] == 'checkbox') {
            $GLOBALS['filterCols'][$cfFid] = ['type'=>'bool', 'fieldType'=>$cfV['fieldType'], 'label'=>$cfV['fieldName'], 'sort'=>1, 'width'=>'40', 'fields'=>$cfFid, 'img'=>static_url().'/static/img5/check-square-regular.svg', 'noimg'=>static_url().'/static/img5/square-regular.svg'];
        } elseif ($cfV['fieldType'] == 'drilldown') {
            $GLOBALS['filterCols'][$cfFid] = ['type'=>'string', 'fieldType'=>$cfV['fieldType'], 'label'=>$cfV['fieldName'], 'sort'=>1, 'width'=>'200', 'fields'=>$cfFid, 'function'=>'cfDrillDownFormat'];
        } elseif ($cfV['fieldType'] == 'date') {
            $GLOBALS['filterCols'][$cfFid] = ['type'=>'string', 'fieldType'=>$cfV['fieldType'], 'label'=>$cfV['fieldName'], 'sort'=>1, 'width'=>'80', 'fieldType'=>'date', 'fields'=>$cfFid, 'function'=>'hs_showShortDate'];
            $GLOBALS['timeGroupings'][$cfFid] = $cfV['fieldName'];
        } elseif ($cfV['fieldType'] == 'datetime') {
            $GLOBALS['filterCols'][$cfFid] = ['type'=>'string', 'fieldType'=>$cfV['fieldType'], 'label'=>$cfV['fieldName'], 'sort'=>1, 'width'=>'135', 'fieldType'=>'datetime', 'fields'=>$cfFid, 'function'=>'hs_showDate'];
        } elseif ($cfV['fieldType'] == 'decimal') {
            $GLOBALS['filterCols'][$cfFid] = ['type'=>'number', 'fieldType'=>$cfV['fieldType'], 'label'=>$cfV['fieldName'], 'decimals'=>$cfV['iDecimalPlaces'], 'sort'=>1, 'width'=>'80', 'fields'=>$cfFid];
        } else {
            $GLOBALS['filterCols'][$cfFid] = ['type'=>'string', 'fieldType'=>$cfV['fieldType'], 'label'=>$cfV['fieldName'], 'sort'=>1, 'width'=>'80', 'fields'=>$cfFid];
        }
    }
}

$GLOBALS['defaultFilterCols'] = ['iLastReplyBy', 'fOpenedVia', 'fOpen', 'fullname', 'xPersonAssignedTo', 'reqsummary', 'age'];
$GLOBALS['defaultWorkspaceCols'] = ['fOpenedVia', 'fullname', 'reqsummary', 'age', 'attachment'];

$GLOBALS['filterKeys'] = ['52'=>'4',
                               '53'=>'5',
                               '54'=>'6',
                               '55'=>'7',
                               '56'=>'8',
                               '57'=>'9',
                               '48'=>'0',
                               '189'=>'-',
                               '187'=>'=',
                               '219'=>'[',
                               '221'=>']',
                               '220'=>'\\',
                               '186'=>';',
                               '222'=>"'",
                               '188'=>',',
                               '190'=>'.',
                               '191'=>'/', ];
