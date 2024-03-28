<?php

namespace HS\Http;


class Security
{
    protected $columns = [
        'xContact',
        'sFirstName',
        'sLastName',
        'sEmail',
        'sTitle',
        'sDescription',
        'fHighlight',
        'xAssignmentChainId',
        'xRequest',
        'xPerson',
        'xPreviousPerson',
        'xChangedByPerson',
        'dtChange',
        'sLogItem',
        'xAutoRule',
        'sRuleName',
        'fOrder',
        'fDeleted',
        'tRuleDef',
        'fDirectOnly',
        'sSchedule',
        'dtNextRun',
        'xAutomationId',
        'iRunCount',
        'sWord',
        'xCategory',
        'iCount',
        'iMsgCount',
        'key',
        'value',
        'expiration',
        'owner',
        'sCategory',
        'sCategoryGroup',
        'fAllowPublicSubmit',
        'xPersonDefault',
        'fAutoAssignTo',
        'sPersonList',
        'sCustomFieldList',
        'xReportingTag',
        'sReportingTag',
        'iOrder',
        'xCustomField',
        'fieldName',
        'isRequired',
        'isPublic',
        'isAlwaysVisible',
        'fieldType',
        'iDecimalPlaces',
        'sTxtSize',
        'sRegex',
        'sAjaxUrl',
        'lrgTextRows',
        'listItems',
        'listItemsColors',
        'xDocumentId',
        'sFilename',
        'sFileMimeType',
        'sCID',
        'blobFile',
        'sFileLocation',
        'xRequestHistory',
        'xResponse',
        'xErrors',
        'dtErrorDate',
        'sType',
        'sFile',
        'sLine',
        'sDesc',
        'id',
        'connection',
        'queue',
        'payload',
        'exception',
        'failed_at',
        'xFilter',
        'fType',
        'fShowCount',
        'fCustomerFriendlyRSS',
        'dtCachedCountAt',
        'iCachedCount',
        'fCacheNever',
        'fDisplayTop',
        'sShortcut',
        'sFilterName',
        'sFilterView',
        'tFilterDef',
        'iCachedMinutes',
        'xGroup',
        'xID',
        'dTime',
        'dtRunAt',
        'xForumId',
        'sForumName',
        'fPrivate',
        'tModerators',
        'fClosed',
        'xKnownUserId',
        'sName',
        'sIP',
        'sOS',
        'sLabel',
        'xPostId',
        'xTopicId',
        'dtGMTPosted',
        'fSpam',
        'iSpamProbability',
        'fEmailUpdate',
        'sURL',
        'sBrowser',
        'tPost',
        'fSticky',
        'sTopic',
        'attempts',
        'reserved_at',
        'available_at',
        'created_at',
        'xBook',
        'sBookName',
        'tDescription',
        'tEditors',
        'xChapter',
        'sChapterName',
        'fAppendix',
        'fHidden',
        'xPage',
        'fDownload',
        'sPageName',
        'tPage',
        'xPersonCreator',
        'xPersonLastUpdate',
        'dtCreatedOn',
        'dtUpdatedOn',
        'iHelpful',
        'iNotHelpful',
        'xRelatedPage',
        'xAttempt',
        'sUsername',
        'dtDateAdded',
        'fValid',
        'xSMSService',
        'sFromSize',
        'sMsgSize',
        'sTotalSize',
        'sPrefixType',
        'sAddress',
        'fTop',
        'xStatus',
        'sStatus',
        'xMailbox',
        'sMailbox',
        'sHostname',
        'sPassword',
        'sPort',
        'sSecurity',
        'fAutoResponse',
        'sReplyName',
        'sReplyEmail',
        'sLastImportMessageId',
        'sLastImportFrom',
        'iLastImportAttemptCt',
        'tAutoResponse',
        'tAutoResponse_html',
        'sSMTPSettings',
        'fArchive',
        'xStuckEmail',
        'sEmailMessageId',
        'sEmailFrom',
        'sEmailDate',
        'xMailRule',
        'migration',
        'batch',
        'xPortal',
        'xMailboxToSendFrom',
        'sHost',
        'sPortalPath',
        'sPortalName',
        'sPortalPhone',
        'tPortalMsg',
        'tDisplayKBs',
        'tDisplayForums',
        'tDisplayCategories',
        'tDisplayCfs',
        'tHistoryMailboxes',
        'sPortalTerms',
        'sPortalPrivacy',
        'fIsPrimaryPortal',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'updated_at',
        'sGroup',
        'fModuleReports',
        'fModuleKbPriv',
        'fModuleForumsPriv',
        'fViewInbox',
        'fCanBatchRespond',
        'fCanMerge',
        'fCanViewOwnReqsOnly',
        'fLimitedToAssignedCats',
        'fCanAdvancedSearch',
        'fCanManageSpam',
        'fCanManageTrash',
        'fCanManageKB',
        'fCanManageForum',
        'fCanTransferRequests',
        'sFname',
        'sLname',
        'sEmail2',
        'sSMS',
        'sPasswordHash',
        'sPhone',
        'tSignature',
        'tSignature_HTML',
        'fNotifyEmail',
        'fNotifyEmail2',
        'fNotifySMS',
        'fNotifySMSUrgent',
        'xPersonPhotoId',
        'fUserType',
        'xPersonOutOfOffice',
        'fNotifyNewRequest',
        'fKeyboardShortcuts',
        'fDefaultToPublic',
        'fHideWysiwyg',
        'fHideImages',
        'fReturnToReq',
        'fSidebarSearchFullText',
        'iRequestHistoryLimit',
        'fRequestHistoryView',
        'sHTMLEditor',
        'sWorkspaceDefault',
        'tWorkspace',
        'sRememberToken',
        'sEmoji',
        'iLastTipViewed',
        'tokenable_type',
        'tokenable_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'sSeries',
        'blobPhoto',
        'xPersonStatus',
        'dtGMTEntered',
        'sPage',
        'sDetails',
        'xLogin',
        'xReminder',
        'dtGMTReminder',
        'tReminder',
        'xReport',
        'fOpenedVia',
        'xOpenedViaId',
        'xPersonOpenedBy',
        'xPersonAssignedTo',
        'fOpen',
        'fUrgent',
        'dtGMTOpened',
        'dtGMTClosed',
        'iLastReplyBy',
        'iLastReadCount',
        'fTrash',
        'dtGMTTrashed',
        'sRequestPassword',
        'sUserId',
        'sRequestHash',
        'Custom1',
        'Custom2',
        'Custom3',
        'xEvent',
        'sColumn',
        'dtLogged',
        'iSecondsInState',
        'iValue',
        'sValue',
        'dValue',
        'dtGMTChange',
        'fPublic',
        'fInitial',
        'iTimerSeconds',
        'fNoteIsHTML',
        'fMergedFromRequest',
        'sRequestHistoryHash',
        'tLog',
        'tNote',
        'tEmailHeaders',
        'fPinned',
        'xMergedRequest',
        'xDraft',
        'dtGMTSaved',
        'xPushed',
        'dtGMTPushed',
        'sPushedTo',
        'sReturnedID',
        'tComment',
        'email',
        'sResponseTitle',
        'sFolder',
        'tResponse',
        'tResponseOptions',
        'fRecurringRequest',
        'fSendEvery',
        'fSendDay',
        'fSendTime',
        'dtSendsAt',
        'sReport',
        'sShow',
        'tData',
        'fEmail',
        'fSendToStaff',
        'fSendToExternal',
        'xSearch',
        'dtGMTPerformed',
        'sSearch',
        'sFromPage',
        'sSearchType',
        'iResultCount',
        'sSetting',
        'tValue',
        'counter_key',
        'max_doc_id',
        'dtGMTOccured',
        'xSubscriptions',
        'xTag',
        'sTag',
        'xTagMap',
        'xThermostat',
        'xSurvey',
        'iScore',
        'tFeedback',
        'xTimeId',
        'iSeconds',
        'fBillable',
        'dtGMTDate',
        'dtGMTDateAdded',
        'xTrigger',
        'sTriggerName',
        'tTriggerDef',
        'fullname',
        'pathname',
        'label',
        'label2',
        'customername',
        'personname',
        'boxname',
        'lastupdate',
        'lastpubupdate',
        'lastcustupdate',
        'thermostat_feedback',
        'ctPublicUpdates',
        'speedtofirstresponse',
        'speedtofirstresponse_biz',
        'timetrack',
    ];

    /**
     * Parse out a order by statement and clean it
     * Handles the following cases:
     * $sortby='foo asc, bar desc'
     * $sortby='foo, bar desc, baz'
     * $sortby='foo, bar'
     * $sortby='foo desc'
     * $sortby='foo'
     * @param $order
     * @return string
     */
    public function parseAndCleanOrder($order)
    {
        $orderBy = '';
        $orderColumns = explode(',', $order);

        foreach($orderColumns as $orderColumn) {
            // Tmp var needed for conditional to decide to append sort order
            // The space here is needed
            $tmp = '';
            $sortParts = explode(' ', trim($orderColumn));
            $tmp .= $this->cleanOrderBy($sortParts[0]);

            if (count($sortParts) > 1 && trim($tmp) !== '') {
                $tmp .= ' '.$this->cleanOrderDirection($sortParts[1]);
            }

            $orderBy .= ' '.$tmp.',';
        }

        return trim(rtrim($orderBy, ','));
    }

    /**
     * Ensure orderby column is valid (exists)
     * @param $orderBy
     * @return string
     */
    public function cleanOrderBy($orderBy)
    {
        if ($this->isCustomField($orderBy)) {
            return $orderBy;
        }

        if ($this->columnExists($orderBy)) {
            return $orderBy;
        }

        return '';
    }

    public function cleanOrderDirection($direction='DESC')
    {
        if (in_array(strtolower($direction), ['asc', 'desc'])) {
            return $direction;
        }

        return '';
    }

    /**
     * Matches a custom field column such as "Custom1" (case-insensitive)
     * Does not match:
     *  xxCustom1
     *  Custom1xx
     *  xxCustom1xx
     *  Customx
     * @param string $orderBy
     * @return bool
     */
    protected function isCustomField($orderBy)
    {
        $output_array = [];
        preg_match('/^Custom[0-9]+$/', trim($orderBy), $output_array);

        return count($output_array) > 0;
    }

    /**
     * Matches a column to one known to exist within the database
     * @param $orderBy
     * @return string
     */
    protected function columnExists($orderBy)
    {
        return (in_array(trim($orderBy), $this->columns))
            ? $orderBy
            : '';
    }
}
