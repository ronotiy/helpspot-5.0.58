<?php
/******************************************
Class to manage updating of request
handles logging and updating or request details.
Does not handle notifications, note additions, or file uploads
******************************************/

use HS\Cache\Manager;
use HS\Domain\Workspace\Event;
use Illuminate\Support\Facades\Cache;
use HS\Domain\Workspace\EventCollection;

class requestUpdate
{
    // Old Request Id
    public $oldReqId;

    // Current request info
    public $oldReq;

    // New request info
    public $newReq;

    // List of all staff members
    public $staffList = [];

    /**
     * @var HS\Domain\Workspace\EventCollection
     */
    public $logs;

    // Log note, used to add an initial note to the log
    public $logNote = '';

    // If there are changes then this heading is placed at the top of the log. Used by automation rules and mail rules to label them.
    public $log_heading = '';

    // Person making change
    public $person;

    // Should notifications go out. Generally yes, but in some cases we want to suppress this
    public $notify = 1;

    // Array of custom fields
    public $customfields;

    // file and line
    public $file;

    public $line;

    //METHODS

    public function __construct($oldreqid, $newreq, $person, $f, $l)
    {
        $this->logs = new EventCollection;
        $this->init($oldreqid, $newreq, $person, $f, $l);
    }

    public function init($oldreqid, $newreq, $person, $f, $l)
    {
        $this->file = $f;
        $this->line = $l;

        $oldreqid = is_numeric($oldreqid) ? $oldreqid : 0;
        $person = is_numeric($person) ? $person : 0;

        $this->person = $person;
        $this->oldReqId = $oldreqid;
        $this->customfields = $GLOBALS['customFields'];
        $this->oldReq = apiGetRequest($oldreqid);
        $this->newReq = $newreq;

        // get all categories
        $catres = apiGetAllCategoriesComplete();
        while ($cat = $catres->FetchRow()) {
            $this->categories[$cat['xCategory']] = $cat['sCategory'];
        }

        //get list of staff
        $staffres = apiGetAllUsersComplete();
        //create array of staff for use in people list
        foreach ($staffres as $k=>$v) {
            $this->staffList[$k] = $v['fullname'];
        }
        //Add inbox
        $this->staffList[0] = utf8_strtoupper(lg_inbox);
    }

    public function trackLogItem($data)
    {
        $data['xRequest'] = (int) $this->oldReqId;
        $data['xPerson'] = (int) $this->person;
        $data['dtLogged'] = time();
        // $data['iSecondsInState']

        $this->logs->add(new Event($data));
    }

    public function checkChanges()
    {
        $keys = array_keys($this->oldReq);

        // Handle reporting tags. If new tags sent in or if the category has been changed then perform this action
        if (isset($this->newReq['reportingTags']) || ($this->oldReq['xCategory'] != $this->newReq['xCategory'])) {
            $oldtags = apiGetRequestRepTags($this->oldReqId);
            //initialize
            $oldtags = is_array($oldtags) ? $oldtags : [];
            $this->newReq['reportingTags'] = is_array($this->newReq['reportingTags']) ? $this->newReq['reportingTags'] : [];

            $oldtagids = array_keys($oldtags); //get reptag ids
            $this->oldReq['reportingTags'] = $oldtagids; //Save for use in triggers later
            $diffold = array_diff($oldtagids, $this->newReq['reportingTags']);
            $diffnew = array_diff($this->newReq['reportingTags'], $oldtagids);

            if (! empty($diffold) || ! empty($diffnew)) {
                //Update change
                $tagres = apiAddEditRequestRepTags($this->newReq['reportingTags'], $this->oldReqId, $this->newReq['xCategory'], $this->file, $this->line);

                //Create string for log
                if ($tagres) {
                    $reptagnames = apiGetReportingTags($this->newReq['xCategory']);
                    $newTags = [];
                    $newTagsKeys = [];
                    foreach ($this->newReq['reportingTags'] as $k=>$v) {
                        $newTags[] = $reptagnames[$v];
                        $newTagsKeys[] = (int) $k;
                    }
                    $newc = implode(',', $newTags);

                    // Add to request event log
                    $this->trackLogItem([
                        'sColumn' => 'xReportingTag',
                        'sValue' => json_encode($newTagsKeys),
                        'sLabel' => $newc,
                        'sDescription' => lg_lookup_15.': '.str_replace(',', ', ', $newc),
                    ]);
                }
            }
        }

        // Make an adjustment to the closing time if we're re-opening
        if ($this->oldReq['fOpen'] != $this->newReq['fOpen']) {
            // We're here because something changed. We have to handle the case in both directions
            if ($this->newReq['fOpen'] == 1) {
                // Moved to opened so remove previous closed date
                $closedtime = 0;
            } else {
                // We moved to closed so set date. We use this time to match last request history
                $closedtime = $this->newReq['dtGMTOpened'];
            }
        } else {
            // Nothing changed so don't change the closing time
            $closedtime = $this->oldReq['dtGMTClosed'];
        }

        // Other elements
        foreach ($keys as $key) {
            switch ($key) {
                case 'fOpen':
                    if ($this->oldReq['fOpen'] != $this->newReq['fOpen']) {
                        $this->trackLogItem([
                            'sColumn' => 'fOpen',
                            'iValue' => $this->newReq['fOpen'],
                            'sLabel' => boolShow($this->newReq['fOpen'], lg_isopen, lg_isclosed),
                            'sDescription' => sprintf(lg_lookup_3, boolShow($this->oldReq['fOpen'], lg_isopen, lg_isclosed), boolShow($this->newReq['fOpen'], lg_isopen, lg_isclosed)),
                        ]);
                    }

                    //Special case to handle drafts when closing a request
                    if ($this->oldReq['fOpen'] == 1 && $this->newReq['fOpen'] == 0) {
                        apiDeleteRequestDrafts($this->oldReqId);
                    }

                    break;
                case 'xPersonAssignedTo':
                    $assignLog = '';
                    if ($this->oldReq['xPersonAssignedTo'] != $this->newReq['xPersonAssignedTo']) {
                        $msg = sprintf(lg_lookup_1, $this->staffList[$this->oldReq['xPersonAssignedTo']], $this->staffList[$this->newReq['xPersonAssignedTo']]);
                        $assignLog = $msg;
                        logAssignmentChange($this->oldReqId, $this->newReq['xPersonAssignedTo'], $msg, $this->oldReq['xPersonAssignedTo'], $this->person);
                    }

                    //Break here if overriding changes during a user delete
                    if (isset($this->newReq['override_autoassign']) && $this->newReq['override_autoassign'] == true) {
                        break;
                    }

                    //See if auto assignment comes into play. Only if being assigned to INBOX.
                    if ($this->newReq['xPersonAssignedTo'] == 0 && $this->newReq['xStatus'] != hs_setting('cHD_STATUS_SPAM', 2)) {	//Inbox
                        $auto_check = apiAutoAssignStaff($this->newReq['xPersonAssignedTo'], $this->newReq['xCategory'], __FILE__, __LINE__);
                        //if there was a change then do it and log it
                        if ($auto_check != $this->newReq['xPersonAssignedTo']) {
                            $msg = sprintf(lg_lookup_17, $this->staffList[$this->newReq['xPersonAssignedTo']], $this->staffList[$auto_check]);
                            $assignLog .= $msg;
                            logAssignmentChange($this->oldReqId, $auto_check, $msg, $this->newReq['xPersonAssignedTo'], -1);
                            $this->newReq['xPersonAssignedTo'] = $auto_check;
                        }
                    }

                    //Check for out of office
                    $oo_check = apiOutOfOffice($this->newReq['xPersonAssignedTo']);
                        //If there was a change do it and log it
                        if ($oo_check != $this->newReq['xPersonAssignedTo']) {
                            $msg = sprintf(lg_lookup_18, $this->staffList[$this->newReq['xPersonAssignedTo']], $this->staffList[$oo_check]);
                            $assignLog .= $msg;
                            logAssignmentChange($this->oldReqId, $oo_check, $msg, $this->newReq['xPersonAssignedTo'], -1);
                            $this->newReq['xPersonAssignedTo'] = $oo_check;
                        }

                    //if reassigning then put old person's id in this var for notification purposes, else leave empty
                    $assignedfrom = ($this->oldReq['xPersonAssignedTo'] == $this->newReq['xPersonAssignedTo']) ? '' : $this->oldReq['xPersonAssignedTo'];

                    //Special check. If user making change is old assigned user then don't notify
                    $assignedfrom = ($this->oldReq['xPersonAssignedTo'] == $this->person) ? '' : $assignedfrom;

                    //special check for spam. Since spam is auto reassigned to person 0 we add an extra check here so that if person doing change is
                    //current owner then don't send them a message. If it's someone else then we still do.
                    if ($this->newReq['xStatus'] == hs_setting('cHD_STATUS_SPAM', 2) && $this->oldReq['xPersonAssignedTo'] == $this->person) {
                        $assignedfrom = '';
                    }

                    // Add to request log down here to get correct
                    // xPersonAssignedTo and sLabel, but only if
                    // xPersonAssignedTo changed after above checked
                    if ($this->oldReq['xPersonAssignedTo'] != $this->newReq['xPersonAssignedTo']) {
                        $this->trackLogItem([
                            'sColumn' => 'xPersonAssignedTo',
                            'iValue' => $this->newReq['xPersonAssignedTo'],
                            'sLabel' => $this->staffList[$this->newReq['xPersonAssignedTo']],
                            'sDescription' => $assignLog, // TODO: Say which auto rule and RR was used
                        ]);
                    }

                    break;
                case 'xStatus':
                    if ($this->oldReq['xStatus'] != $this->newReq['xStatus']) {
                        $sLabel = $GLOBALS['reqStatus'][$this->newReq['xStatus']];
                        $fromStatus = $GLOBALS['reqStatus'][$this->oldReq['xStatus']];
                        $toStatus = $GLOBALS['reqStatus'][$this->newReq['xStatus']];

                        // Changing to spam
                        if (is_null($sLabel) && $this->newReq['xStatus'] == hs_setting('cHD_STATUS_SPAM', 2)) {
                            $sLabel = lg_spam;
                            $toStatus = lg_spam;
                        }

                        // Changing from spam
                        if ($this->oldReq['xStatus'] == hs_setting('cHD_STATUS_SPAM', 2)) {
                            $fromStatus = lg_spam;
                        }

                        $this->trackLogItem([
                            'sColumn' => 'xStatus',
                            'iValue' => $this->newReq['xStatus'],
                            'sLabel' => $sLabel,
                            'sDescription' => sprintf(lg_lookup_4, $fromStatus, $toStatus),
                        ]);
                    }

                    break;
                case 'fUrgent':
                    $this->newReq['fUrgent'] = ($this->newReq['fUrgent'] == '') ? 0 : $this->newReq['fUrgent']; //compensate for checkboxes coming through as empty strings.
                    if ($this->oldReq['fUrgent'] != $this->newReq['fUrgent']) {
                        $this->trackLogItem([
                            'sColumn' => 'fUrgent',
                            'iValue' => $this->newReq['fUrgent'],
                            'sLabel' => boolShow($this->newReq['fUrgent'], lg_isurgent, lg_isnormal),
                            'sDescription' => sprintf(lg_lookup_7, boolShow($this->oldReq['fUrgent'], lg_isurgent, lg_isnormal), boolShow($this->newReq['fUrgent'], lg_isurgent, lg_isnormal)),
                        ]);
                    }

                    break;
                case 'xCategory':
                    if ($this->oldReq['xCategory'] != $this->newReq['xCategory']) {
                        $this->trackLogItem([
                            'sColumn' => 'xCategory',
                            'iValue' => $this->newReq['xCategory'],
                            'sLabel' => (string) $this->categories[$this->newReq['xCategory']],
                            'sDescription' => sprintf(lg_lookup_8, $this->categories[$this->oldReq['xCategory']], $this->categories[$this->newReq['xCategory']]),
                        ]);
                    }

                    break;
                case 'sUserId':
                    if (trim($this->oldReq['sUserId']) != trim($this->newReq['sUserId'])) {
                        $this->trackLogItem([
                            'sColumn' => 'sUserId',
                            'sValue' => $this->newReq['sUserId'],
                            'sLabel' => $this->newReq['sUserId'],
                            'sDescription' => sprintf(lg_lookup_9, $this->oldReq['sUserId'], $this->newReq['sUserId']),
                        ]);
                    }

                    break;
                case 'sFirstName':
                    if (trim($this->oldReq['sFirstName']) != trim($this->newReq['sFirstName'])) {
                        $this->trackLogItem([
                            'sColumn' => 'sFirstName',
                            'sValue' => trim($this->newReq['sFirstName']),
                            'sLabel' => trim($this->newReq['sFirstName']),
                            'sDescription' => sprintf(lg_lookup_10, $this->oldReq['sFirstName'], $this->newReq['sFirstName']),
                        ]);
                    }

                    break;
                case 'sLastName':
                    if (trim($this->oldReq['sLastName']) != trim($this->newReq['sLastName'])) {
                        $this->trackLogItem([
                            'sColumn' => 'sLastName',
                            'sValue' => trim($this->newReq['sFirstName']),
                            'sLabel' => trim($this->newReq['sFirstName']),
                            'sDescription' => sprintf(lg_lookup_11, $this->oldReq['sLastName'], $this->newReq['sLastName']),
                        ]);
                    }

                    break;
                case 'sEmail':
                    if (trim($this->oldReq['sEmail']) != trim($this->newReq['sEmail'])) {
                        $this->trackLogItem([
                            'sColumn' => 'sEmail',
                            'sValue' => trim($this->newReq['sEmail']),
                            'sLabel' => trim($this->newReq['sEmail']),
                            'sDescription' => sprintf(lg_lookup_12, $this->oldReq['sEmail'], $this->newReq['sEmail']),
                        ]);
                    }

                    break;
                case 'sPhone':
                    if (trim($this->oldReq['sPhone']) != trim($this->newReq['sPhone'])) {
                        $this->trackLogItem([
                            'sColumn' => 'sPhone',
                            'sValue' => trim($this->newReq['sPhone']),
                            'sLabel' => trim($this->newReq['sPhone']),
                            'sDescription' => sprintf(lg_lookup_13, $this->oldReq['sPhone'], $this->newReq['sPhone']),
                        ]);
                    }

                    break;
                case 'sTitle':
                    //if it's empty don't change title, only if it's actually different
                    if (! hs_empty($this->newReq['sTitle']) && trim($this->oldReq['sTitle']) != trim($this->newReq['sTitle'])) {
                        $this->trackLogItem([
                            'sColumn' => 'sTitle',
                            'sValue' => trim($this->newReq['sTitle']),
                            'sLabel' => trim($this->newReq['sTitle']),
                            'sDescription' => sprintf(lg_lookup_23, $this->oldReq['sTitle'], $this->newReq['sTitle']),
                        ]);
                    } else {
                        //Set new to old one if it was empty, don't update to an empty title as priv notes have empty titles and we don't want empty titles
                        $this->newReq['sTitle'] = $this->oldReq['sTitle'];
                    }

                    break;
                case 'xMailboxToSendFrom':
                    if ($this->oldReq['xMailboxToSendFrom'] != $this->newReq['xMailboxToSendFrom']) {
                        //include mailbox functions if needed
                        if (! function_exists('apiGetMailbox')) {
                            include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
                        }

                        //Find mailbox names
                        if ($this->oldReq['xMailboxToSendFrom'] == 0) {
                            $oldmb = lg_default_mailbox;
                        } else {
                            $oldmb = apiGetMailbox($this->oldReq['xMailboxToSendFrom']);
                            $oldmb = $oldmb['sReplyEmail'];
                        }

                        if ($this->newReq['xMailboxToSendFrom'] == 0) {
                            $newmb = lg_default_mailbox;
                        } else {
                            $newmb = apiGetMailbox($this->newReq['xMailboxToSendFrom']);
                            $newmb = $newmb['sReplyEmail'];
                        }

                        $this->trackLogItem([
                            'sColumn' => 'xMailboxToSendFrom',
                            'iValue' => $this->newReq['xMailboxToSendFrom'],
                            'sLabel' => $newmb,
                            'sDescription' => sprintf(lg_lookup_24, $oldmb, $newmb),
                        ]);
                    }

                    break;
                case 'fTrash':
                    //Any update should remove request from trash if it's in the trash unless the new change also includes keeping the item in the trash (usually a mail rule where email matches multiple rules)
                    if ($this->oldReq['fTrash'] == 1 && $this->newReq['fTrash'] != 1) {
                        $this->newReq['fTrash'] = 0;
                        $this->newReq['dtGMTTrashed'] = 0;

                        $this->trackLogItem([
                            'sColumn' => 'fTrash',
                            'iValue' => 0,
                            'sLabel' => lg_lookup_20,
                            'sDescription' => lg_lookup_20,
                        ]);
                    } elseif ($this->newReq['fTrash'] == 1) {
                        //close request and reassign to inbox
                        //$this->newReq['xPersonAssignedTo'] = 0;	//back to inbox
                        //$this->newReq['fOpen'] 		= 0;	//close request

                        $this->trackLogItem([
                            'sColumn' => 'fTrash',
                            'iValue' => 1,
                            'sLabel' => lg_lookup_19,
                            'sDescription' => lg_lookup_19,
                        ]);
                    }

                    break;
            }

            // Handle custom fields since this loop won't work in switch statement
            if (is_array($this->customfields)) {
                foreach ($this->customfields as $fvalue) {
                    $fid = 'Custom'.$fvalue['fieldID'];
                    if ($key == $fid) { 	//check if current field being looped on is one of the custom fields
                        if ($fvalue['fieldType'] == 'checkbox') {
                            $this->newReq[$fid] = ($this->newReq[$fid] == '') ? 0 : (int) $this->newReq[$fid]; //compensate for checkboxes coming through as empty strings.
                            if ($this->oldReq[$fid] !== $this->newReq[$fid]) {
                                $this->trackLogItem([
                                    'sColumn' => $fid,
                                    'sValue' => $this->newReq[$fid],
                                    'sLabel' =>  boolShow($this->newReq[$fid], lg_checked, lg_notchecked),
                                    'sDescription' => sprintf(lg_lookup_2, $fvalue['fieldName'], boolShow($this->oldReq[$fid], lg_checked, lg_notchecked), boolShow($this->newReq[$fid], lg_checked, lg_notchecked)),
                                ]);
                            }
                        } elseif ($fvalue['fieldType'] == 'lrgtext') {
                            if (trim($this->oldReq[$fid]) != trim($this->newReq[$fid])) {
                                $this->trackLogItem([
                                    'sColumn' => $fid,
                                    'sValue' => substr($this->newReq[$fid], 0, 75).'...',
                                    'sLabel' =>  substr($this->newReq[$fid], 0, 75).'...',
                                    'sDescription' =>  sprintf(lg_lookup_14, $fvalue['fieldName']),
                                ]);
                            }
                        } elseif ($fvalue['fieldType'] == 'drilldown') {
                            if (trim($this->oldReq[$fid]) != trim($this->newReq[$fid])) {
                                $this->trackLogItem([
                                    'sColumn' => $fid,
                                    'sValue' => $this->newReq[$fid],
                                    'sLabel' => cfDrillDownFormat($this->newReq[$fid]),
                                    'sDescription' =>  sprintf(lg_lookup_2, $fvalue['fieldName'], cfDrillDownFormat(trim($this->oldReq[$fid])), cfDrillDownFormat($this->newReq[$fid])),
                                ]);
                            }
                        } elseif ($fvalue['fieldType'] == 'date' || $fvalue['fieldType'] == 'datetime') {
                            $time_format = $fvalue['fieldType'] == 'date' ? hs_setting('cHD_POPUPCALSHORTDATEFORMAT') : hs_setting('cHD_POPUPCALDATEFORMAT');
                            if (! hs_empty($this->newReq[$fid])) {
                                $time = is_numeric($this->newReq[$fid]) ? $this->newReq[$fid] : jsDateToTime($this->newReq[$fid], $time_format);
                                $time = $this->newReq[$fid] == '' ? 0 : $time; //handle resetting a time back to empty

                                if ($fvalue['fieldType'] == 'date') {
                                    $time =\Carbon\Carbon::createFromTimestamp($time)
                                        ->startOfDay()
                                        ->timestamp;
                                }
                            } else {
                                $time = false;
                                $this->newReq[$fid] = '';
                            }

                            if ($this->oldReq[$fid] != $time) {
                                // Ensure we save an integer, not a string date
                                $iValue = strtotime($this->newReq[$fid]);
                                if($iValue === false) $iValue = 0;

                                $this->trackLogItem([
                                    'sColumn' => $fid,
                                    'iValue' => $iValue,
                                    'sLabel' => hs_showCustomDate($time, $time_format),
                                    'sDescription' =>  sprintf(lg_lookup_2, $fvalue['fieldName'], ($this->oldReq[$fid] == 0 ? '' : hs_showCustomDate($this->oldReq[$fid], $time_format)), ($this->newReq[$fid] == 0 ? '' : hs_showCustomDate($time, $time_format))),
                                ]);
                            } else {
                                $logitem = '';
                            }
                        } elseif ($fvalue['fieldType'] == 'numtext') {
                            if (is_numeric($this->newReq[$fid])) {
                                $trimOld_Numtext = trim($this->oldReq[$fid]);
                                $trimNew_Numtext = trim($this->newReq[$fid]);
                                if ($trimOld_Numtext != $trimNew_Numtext) {
                                    $this->trackLogItem([
                                        'sColumn' => $fid,
                                        'iValue' => $trimNew_Numtext,
                                        'sLabel' =>  $trimNew_Numtext,
                                        'sDescription' =>  sprintf(lg_lookup_2, $fvalue['fieldName'], $trimOld_Numtext, $trimNew_Numtext),
                                    ]);
                                }
                            }
                        } elseif ($fvalue['fieldType'] == 'decimal') {
                            if (is_numeric($this->newReq[$fid])) {
                                $trimOld_Decimal = trim($this->oldReq[$fid]);
                                $trimNew_Decimal = trim($this->newReq[$fid]);
                                if ($trimOld_Decimal != $trimNew_Decimal) {
                                    $this->trackLogItem([
                                        'sColumn' => $fid,
                                        'dValue' => $trimNew_Decimal,
                                        'sLabel' =>  $trimNew_Decimal,
                                        'sDescription' =>  sprintf(lg_lookup_2, $fvalue['fieldName'], $trimOld_Decimal, $trimNew_Decimal),
                                    ]);
                                }
                            }
                        } else {
                            $trimOld = trim($this->oldReq[$fid]);
                            $trimNew = trim($this->newReq[$fid]);
                            if ($trimOld !== $trimNew) {
                                $this->trackLogItem([
                                    'sColumn' => $fid,
                                    'sValue' => $trimNew,
                                    'sLabel' => $trimNew,
                                    'sDescription' => sprintf(lg_lookup_2, $fvalue['fieldName'], $trimOld, $trimNew),
                                ]);
                            }
                        }
                    }
                }
            }
        } //end foreach

        // No entry for empty arrays (no changes)
        if ((isset($this->newReq['override_autoassign']) and $this->newReq['override_autoassign'] == true) || ! $this->logs->isEmpty() || ! empty($this->log_heading) || ! empty($this->logNote)) {
            if ($this->log_heading) {
                //Append log heading
                $this->logNote = $this->log_heading."\n".$this->logNote;
            }

            if (empty($this->logNote)) {
                $this->logNote = lg_requestchanged.':';
            }

            $updatedFields = ['mode'=>'update',
                                    'xRequest'	=>$this->oldReqId,
                                    'tLog'		=>$this->logNote,
                                    'xPerson'		=>$this->person,
                                    'dtGMTChange'	=>$this->newReq['dtGMTOpened'],
                                    'dtGMTClosed'	=>$closedtime,
                                    'reassignedfrom'=>$assignedfrom,
                                    'fOpen'=>$this->newReq['fOpen'],
                                    'xPersonAssignedTo'=>$this->newReq['xPersonAssignedTo'],
                                    'xStatus'=>$this->newReq['xStatus'],
                                    'fUrgent'=>$this->newReq['fUrgent'],
                                    'fTrash'=>$this->newReq['fTrash'],
                                    'dtGMTTrashed'=>$this->newReq['dtGMTTrashed'],
                                    'xCategory'=>$this->newReq['xCategory'],
                                    'sUserId'=>$this->newReq['sUserId'],
                                    'sFirstName'=>$this->newReq['sFirstName'],
                                    'sLastName'=>$this->newReq['sLastName'],
                                    'sEmail'=>$this->newReq['sEmail'],
                                    'sPhone'=>$this->newReq['sPhone'],
                                    'sTitle'=>$this->newReq['sTitle'],
                                    'xMailboxToSendFrom'=>$this->newReq['xMailboxToSendFrom'],
                                    'sRequestPassword'=>$this->newReq['sRequestPassword'],
            ];
            //add custom fields back into update
            if (is_array($this->customfields)) {
                foreach ($this->customfields as $fvalue) {
                    $fid = 'Custom'.$fvalue['fieldID'];
                    $updatedFields[$fid] = $this->newReq[$fid];
                }
            }

            $logMessageForNotifications = $this->logs->reduce(function ($carry, $item) {
                if (! empty($item->sDescription)) {
                    return $carry."\n".$item->sDescription;
                }
            }, "\n");

            $updatedFields['request_events'] = $logMessageForNotifications;

            $reqHis = apiAddEditRequest($updatedFields, $this->notify, $this->file, $this->line); //opened time used even for updates because it's set to current time not open time in the newreq array

            // On success, flush log events (save to db)
            if ($reqHis) {
                $this->logs->flush($reqHis['xRequestHistory']);
            }
        }

        //Trigger check
        if (! isset($this->skipTrigger)) { //Prevents cascading triggers for now
            $trigger_body = (isset($this->newReq['tBody']) ? $this->newReq['tBody'] : '');
            $trigger_public = (isset($this->newReq['fPublic']) ? $this->newReq['fPublic'] : 0);
            apiRunTriggers($this->oldReq['xRequest'], $this->newReq, $this->oldReq, $trigger_body, $trigger_public, $this->person, 2, __FILE__, __LINE__);
        }

        Cache::forget(Manager::history_count_key($this->oldReqId));

        return isset($reqHis) ? $reqHis : false;
    }

    // end checkChanges
}
