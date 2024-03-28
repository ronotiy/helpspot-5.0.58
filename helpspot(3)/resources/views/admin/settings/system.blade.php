<div class="settings-box" id="box_id_{{ md5(lg_admin_settings_system) }}">

    {!! renderPageheader(lg_admin_settings_system) !!}

    <div class="card padded">

        <div class="fr">
            <div class="label">
                <label class="req" for="cHD_ORGNAME">{{ lg_admin_settings_orgname }}</label>
            </div>
            <div class="control">
                <input name="cHD_ORGNAME" id="cHD_ORGNAME" type="text" value="{{ formClean(hs_setting('cHD_ORGNAME')) }}">
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label class="" for="cHD_CUSTCONNECT_ACTIVE">{{ lg_admin_settings_allow_err_rep }}</label>
                <div class="info">{{ lg_admin_settings_allow_err_repex }}</div>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_CUSTCONNECT_ACTIVE" id="cHD_CUSTCONNECT_ACTIVE" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_CUSTCONNECT_ACTIVE')) }}>
                <label for="cHD_CUSTCONNECT_ACTIVE" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

{{--
        <div class="fr">
            <div class="label">
                <label class="req" for="cHD_ORGLOGO">{{ lg_admin_settings_orgimg }}</label>
                <div class="info">{{ lg_admin_settings_orgimgex }}</div>
            </div>
            <div class="control">
                <input name="cHD_ORGLOGO" id="cHD_ORGLOGO" type="text" value="{{ formClean(hs_setting('cHD_ORGLOGO')) }}">
            </div>
        </div>

        <div class="hr"></div>
--}}

        <div class="fr">
            <div class="label">
                <label class="" for="cHD_NOTIFICATIONEMAILACCT">{{ lg_admin_settings_notacct }}</label>
                <div class="info">{{ lg_admin_settings_notacctex }}</div>
            </div>
            <div class="control">
                <input name="cHD_NOTIFICATIONEMAILACCT" id="cHD_NOTIFICATIONEMAILACCT" type="text" value="{{ formClean(hs_setting('cHD_NOTIFICATIONEMAILACCT')) }}">
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="" for="cHD_NOTIFICATIONEMAILNAME">{{ lg_admin_settings_notname }}</label>
                <div class="info">{{ lg_admin_settings_notacctex }}</div>
            </div>
            <div class="control">
                <input name="cHD_NOTIFICATIONEMAILNAME" id="cHD_NOTIFICATIONEMAILNAME" type="text" value="{{ formClean(hs_setting('cHD_NOTIFICATIONEMAILNAME')) }}">
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_DEFAULTMAILBOX">{{ lg_admin_settings_defaultemail }}</label>
                <div class="info">{{ lg_admin_settings_defaultemailex }}</div>
            </div>
            <div class="control">
                <select name="cHD_DEFAULTMAILBOX">
                    {!! $mailboxesSelect !!}
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label for="cHD_LANG">{{ lg_admin_settings_lang }}</label></div>
            <div class="control">
                <select name="cHD_LANG">
                    {!! $langopt !!}
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label for="cHD_CONTACTVIA">{{ lg_admin_settings_contactvia }}</label></div>
            <div class="control">
                <select name="cHD_CONTACTVIA">
                    {!! $contactopt !!}
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label for="cHD_DEFAULT_HISTORYSEARCH">{{ lg_admin_settings_defaulthistorysearch }}</label></div>
            <div class="control">
                <select name="cHD_DEFAULT_HISTORYSEARCH">
                    <option value="1" {{ selectionCheck('1', hs_setting('cHD_DEFAULT_HISTORYSEARCH')) }}>{{ lg_admin_settings_request_search1 }}</option>
                    <option value="2" {{ selectionCheck('2', hs_setting('cHD_DEFAULT_HISTORYSEARCH')) }}>{{ lg_admin_settings_request_search2 }}</option>
                    <option value="4" {{ selectionCheck('4', hs_setting('cHD_DEFAULT_HISTORYSEARCH')) }}>{{ lg_admin_settings_request_search4 }}</option>
                    <option value="3" {{ selectionCheck('3', hs_setting('cHD_DEFAULT_HISTORYSEARCH')) }}>{{ lg_admin_settings_request_search3 }}</option>
                    <option value="6" {{ selectionCheck('6', hs_setting('cHD_DEFAULT_HISTORYSEARCH')) }}>{{ lg_admin_settings_request_search6 }}</option>
                    <option value="5" {{ selectionCheck('5', hs_setting('cHD_DEFAULT_HISTORYSEARCH')) }}>{{ lg_admin_settings_request_search5 }}</option>
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_BATCHCLOSE">{{ lg_admin_settings_allowbatch }}</label>
                <div class="info">{{ lg_admin_settings_allowbatchex }}</div>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_BATCHCLOSE" id="cHD_BATCHCLOSE" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_BATCHCLOSE')) }}>
                <label for="cHD_BATCHCLOSE" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_BATCHRESPOND">{{ lg_admin_settings_allowbatchrespond }}</label>
                <div class="info">{{ lg_admin_settings_allowbatchrespondex }}</div>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_BATCHRESPOND" id="cHD_BATCHRESPOND" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_BATCHRESPOND')) }}>
                <label for="cHD_BATCHRESPOND" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_FEEDSENABLED">{{ lg_admin_settings_feedsenabled }}</label>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_FEEDSENABLED" id="cHD_FEEDSENABLED" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_FEEDSENABLED')) }}>
                <label for="cHD_FEEDSENABLED" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_FEEDCOPYRIGHT">{{ lg_admin_settings_feedcopyright }}</label>
            </div>
            <div class="control">
                <input name="cHD_FEEDCOPYRIGHT" id="cHD_FEEDCOPYRIGHT" type="text" size="60" maxlength="255" value="{{ formClean(cHD_FEEDCOPYRIGHT) }}">
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_EMBED_MEDIA">{{ lg_admin_settings_embedmedia }}</label>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_EMBED_MEDIA" id="cHD_EMBED_MEDIA" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_EMBED_MEDIA')) }}>
                <label for="cHD_EMBED_MEDIA" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_DAYS_TO_LEAVE_TRASH">{{ lg_admin_settings_daystoleavetrash }}</label>
                <div class="info">{{ lg_admin_settings_daystoleavetrashex }}</div>
            </div>
            <div class="control">
                <select name="cHD_DAYS_TO_LEAVE_TRASH">
                    <option value="0" {{ selectionCheck('0', hs_setting('cHD_DAYS_TO_LEAVE_TRASH')) }}>{{ lg_admin_settings_neverdeletetrash }}</option>
                    <option value="1" {{ selectionCheck('1', hs_setting('cHD_DAYS_TO_LEAVE_TRASH')) }}>1 {{ lg_day }}</option>
                    <option value="7" {{ selectionCheck('7', hs_setting('cHD_DAYS_TO_LEAVE_TRASH')) }}>7 {{ lg_days }}</option>
                    <option value="14" {{ selectionCheck('14', hs_setting('cHD_DAYS_TO_LEAVE_TRASH')) }}>14 {{ lg_days }}</option>
                    <option value="30" {{ selectionCheck('30', hs_setting('cHD_DAYS_TO_LEAVE_TRASH')) }}>30 {{ lg_days }}</option>
                    <option value="60" {{ selectionCheck('60', hs_setting('cHD_DAYS_TO_LEAVE_TRASH')) }}>60 {{ lg_days }}</option>
                    <option value="90" {{ selectionCheck('90', hs_setting('cHD_DAYS_TO_LEAVE_TRASH')) }}>90 {{ lg_days }}</option>
                    <option value="180" {{ selectionCheck('180', hs_setting('cHD_DAYS_TO_LEAVE_TRASH')) }}>180 {{ lg_days }}</option>
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_MAXSEARCHRESULTS">{{ lg_admin_settings_maxresults }}</label>
                <div class="info">{{ lg_admin_settings_maxresultsex }}</div>
            </div>
            <div class="control">
                <input name="cHD_MAXSEARCHRESULTS" id="cHD_MAXSEARCHRESULTS" type="text" size="10" maxlength="255" value="{{ formClean(hs_setting('cHD_MAXSEARCHRESULTS')) }}">
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_VIRTUAL_ARCHIVE">{{ lg_admin_settings_virtualarchive }}</label>
                <div class="info">{{ lg_admin_settings_virtualarchiveex }}</div>
            </div>
            <div class="control">
                <input name="cHD_VIRTUAL_ARCHIVE" id="cHD_VIRTUAL_ARCHIVE" type="text" size="10" maxlength="255" value="{{ formClean(hs_setting('cHD_VIRTUAL_ARCHIVE')) }}">
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_SAVE_DRAFTS_EVERY">{{ lg_admin_settings_savedraftsevery }}</label>
                <div class="info">{{ lg_admin_settings_savedraftseveryex }}</div>
            </div>
            <div class="control">
                <input name="cHD_SAVE_DRAFTS_EVERY" id="cHD_SAVE_DRAFTS_EVERY" type="text" size="10" maxlength="255" value="{{ formClean(hs_setting('cHD_SAVE_DRAFTS_EVERY')) }}">
            </div>
        </div>

        <div class="hr"></div>

        @if (! isHosted())

        <div class="fr">
            <div class="label">
                <label for="cHD_ATTACHMENT_LOCATION">{{ lg_admin_settings_saveattach }}</label>
                <div class="info">{{ lg_admin_settings_saveattachex }}</div>
            </div>
            <div class="control">
                <select name="cHD_ATTACHMENT_LOCATION" id="cHD_ATTACHMENT_LOCATION" onChange="attachPathSwitch()">
                    <option value="db" {{ selectionCheck('db', hs_setting('cHD_ATTACHMENT_LOCATION')) }}>{{ lg_admin_settings_saveattach_db }}</option>
                    <option value="file" {{ selectionCheck('file', hs_setting('cHD_ATTACHMENT_LOCATION')) }}>{{ lg_admin_settings_saveattach_file }}</option>
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr" id="attachment_location_path">
            <div class="label">
                <label for="cHD_ATTACHMENT_LOCATION_PATH">{{ lg_admin_settings_saveattach_path }}</label>
                <div class="info">{!! lg_admin_settings_saveattach_pathex !!}</div>
            </div>
            <div class="control">
                <input name="cHD_ATTACHMENT_LOCATION_PATH" id="cHD_ATTACHMENT_LOCATION_PATH" type="text" size="80" maxlength="255" value="{{ formClean(hs_setting('cHD_ATTACHMENT_LOCATION_PATH')) }}">
            </div>
        </div>

        <div class="hr"></div>

        @endif


        <div class="fr">
            <div class="label">
                <label for="cHD_STRIPHTML">{{ lg_admin_settings_striphtml }}</label>
                <div class="info">{{ lg_admin_settings_striphtmldesc }}</div>
            </div>
            <div class="control">
                <select name="cHD_STRIPHTML" onChange="htmlSwitch()">
                    <option value="2" {{ selectionCheck('2', hs_setting('cHD_STRIPHTML')) }}>{{ lg_admin_settings_htmldisplay }}</option>
                    <option value="1" {{ selectionCheck('1', hs_setting('cHD_STRIPHTML')) }}>{{ lg_admin_settings_htmlremove }}</option>
                    <option value="0" {{ selectionCheck('0', hs_setting('cHD_STRIPHTML')) }}>{{ lg_admin_settings_htmlescape }}</option>
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr" id="htmlallowed">
            <div class="label">
                <div class="info">{{ lg_admin_settings_htmlallowed }}</div>
            </div>
            <div class="control">
                <input name="cHD_HTMLALLOWED" id="cHD_HTMLALLOWED" type="text" size="80" maxlength="255" value="{{ formClean(hs_setting('cHD_HTMLALLOWED')) }}">
            </div>
        </div>
{{-- 
        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_SERIOUS">{{ lg_admin_settings_serious }}</label>
                <div class="info">{{ lg_admin_settings_seriousex }}</div>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_SERIOUS" id="cHD_SERIOUS" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_SERIOUS')) }}>
                <label for="cHD_SERIOUS" class="switch"></label>
            </div>
        </div> --}}

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
