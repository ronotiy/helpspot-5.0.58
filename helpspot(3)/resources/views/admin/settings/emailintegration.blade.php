<div class="settings-box emailintegration" id="box_id_{{ md5(lg_admin_settings_emailintegration) }}">

    {!! renderPageheader(lg_admin_settings_emailintegration) !!}

    <div class="card padded">

        <div class="fr">
            <div class="label">
                <label for="cHD_MAIL_OUTTYPE">{{ lg_admin_settings_outboundemail }}</label>
                <div class="info">{{ lg_admin_settings_outboundemailex }}</div>
            </div>
            <div class="control">
                <select name="cHD_MAIL_OUTTYPE" id="cHD_MAIL_OUTTYPE" onchange="emailSwitch();">
                    <option value="mail" {{ selectionCheck('mail', hs_setting('cHD_MAIL_OUTTYPE')) }}>{{ lg_admin_settings_phpmail }}</option>
                    <option value="smtp" {{ selectionCheck('smtp', hs_setting('cHD_MAIL_OUTTYPE')) }}>{{ lg_admin_settings_smtp }}</option>
                </select>
            </div>
        </div>

        <div class="testOutbound"></div>

        <div class="fr testOutbound">
            <div class="label">&nbsp;</div>
            <div class="control"><a href="#" onclick="testOutboundEmail(event);" class="btn inline-action">{{ lg_admin_settings_smtptest }}</a> </div>
        </div>

        <fieldset class="fieldset" id="emailblock" style="display:none;">
            <div class="sectionhead">{{ lg_admin_settings_smtpconf }}</div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_MAIL_SMTPPROTOCOL">{{ lg_admin_settings_smtpproto }}</label>
                    <div class="info">{{ lg_admin_settings_smtpprotoex }}</div>
                </div>
                <div class="control">
                    @if (defined('cEXTRASECURITYOPTIONS'))
                    <select name="cHD_MAIL_SMTPPROTOCOL">
                        <option value="" {{ selectionCheck('', $sm['cHD_MAIL_SMTPPROTOCOL']) }}>{{ lg_admin_settings_smtpprotonone }}</option>
                        <optgroup label="{{ lg_admin_settings_smtpprotolabeldefault }}">
                            <option value="ssl" {{ selectionCheck('ssl', $sm['cHD_MAIL_SMTPPROTOCOL']) }}>{{ lg_admin_settings_smtpprotossl }}</option>
                            <option value="tls" {{ selectionCheck('tls', $sm['cHD_MAIL_SMTPPROTOCOL']) }}>{{ lg_admin_settings_smtpprototls }}</option>
                        </optgroup>
                        {{ $advancedTransports }}
                    </select>
                    @else
                    <select name="cHD_MAIL_SMTPPROTOCOL">
                        <option value="" {{ selectionCheck('', $sm['cHD_MAIL_SMTPPROTOCOL']) }}>{{ lg_admin_settings_smtpprotonone }}</option>
                        <option value="ssl" {{ selectionCheck('ssl', $sm['cHD_MAIL_SMTPPROTOCOL']) }}>{{ lg_admin_settings_smtpprotossl }}</option>
                        <option value="tls" {{ selectionCheck('tls', $sm['cHD_MAIL_SMTPPROTOCOL']) }}>{{ lg_admin_settings_smtpprototls }}</option>
                        <option value="tlsv1.2" {{ selectionCheck('tlsv1.2', $sm['cHD_MAIL_SMTPPROTOCOL']) }}>{{ lg_admin_settings_smtpprototlsv12 }}</option>
                    </select>
                    @endif
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_MAIL_SMTPHOST">{{ lg_admin_settings_smtphost }}</label>
                    <div class="info">{{ lg_admin_settings_smtphostex }}</div>
                </div>
                <div class="control">
                    <input name="cHD_MAIL_SMTPHOST" id="cHD_MAIL_SMTPHOST" type="text" size="40" value="{{ formClean($sm['cHD_MAIL_SMTPHOST']) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label"><label for="cHD_MAIL_SMTPPORT">{{ lg_admin_settings_smtpport }}</label></div>
                <div class="control">
                    <input name="cHD_MAIL_SMTPPORT" id="cHD_MAIL_SMTPPORT" type="text" size="10" value="{{ formClean($sm['cHD_MAIL_SMTPPORT']) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label tdlcheckbox">
                    <label for="cHD_MAIL_SMTPAUTH">{{ lg_admin_settings_smtpauth }}</label>
                </div>
                <div class="control">
                    <input type="checkbox" name="cHD_MAIL_SMTPAUTH" id="cHD_MAIL_SMTPAUTH" class="checkbox" value="1" {{ checkboxCheck(1, $sm['cHD_MAIL_SMTPAUTH']) }}>
                    <label for="cHD_MAIL_SMTPAUTH" class="switch"></label>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label"><label for="cHD_MAIL_SMTPUSER">{{ lg_admin_settings_smtpuser }}</label></div>
                <div class="control">
                    <input name="cHD_MAIL_SMTPUSER" id="cHD_MAIL_SMTPUSER" type="text" size="40" value="{{ formClean($sm['cHD_MAIL_SMTPUSER']) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_MAIL_SMTPPASS">{{ lg_admin_settings_smtppass }}</label>
                    <div class="info">{{ lg_admin_settings_smtppass_msg }}</div>
                </div>
                <div class="control">
                    <input name="cHD_MAIL_SMTPPASS" id="cHD_MAIL_SMTPPASS" type="text" size="40" value="">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label"><label for="cHD_MAIL_SMTPTIMEOUT">{{ lg_admin_settings_smtptimeout }}</label></div>
                <div class="control">
                    <input name="cHD_MAIL_SMTPTIMEOUT" id="cHD_MAIL_SMTPTIMEOUT" type="text" size="40" value="{{ (($sm['cHD_MAIL_SMTPTIMEOUT']) ? formClean($sm['cHD_MAIL_SMTPTIMEOUT']) : 10) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_MAIL_SMTPHELO">{{ lg_admin_settings_smtphelo }}</label>
                    <div class="info">{{ lg_admin_settings_smtpheloex }}</div>
                </div>
                <div class="control">
                    <input name="cHD_MAIL_SMTPHELO" id="cHD_MAIL_SMTPHELO" type="text" size="40" value="{{ formClean($sm['cHD_MAIL_SMTPHELO']) }}">
                </div>
            </div>
        </fieldset>

        <fieldset class="fieldset">
            <div class="sectionhead">{{ lg_admin_settings_emailopt }}</div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_EMAILPREFIX">{{ lg_admin_settings_prefix }}</label>
                    <div class="info">{{ lg_admin_settings_prefixex }}</div>
                </div>
                <div class="control">
                    <input name="cHD_EMAILPREFIX" id="cHD_EMAILPREFIX" type="text" size="10" maxlength="255" value="{{ formClean(trim(hs_setting('cHD_EMAILPREFIX'))) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_EMAIL_GLOBALBCC">{{ lg_admin_settings_globalbcc }}</label>
                    <div class="info">{{ lg_admin_settings_globalbcc_note }}</div>
                </div>
                <div class="control">
                    <div class="group">
                        <input name="cHD_EMAIL_GLOBALBCC" id="cHD_EMAIL_GLOBALBCC" type="text" size="40" maxlength="255" value="{{ formClean(trim(hs_setting('cHD_EMAIL_GLOBALBCC'))) }}" style="margin-right: 8px;">
                        <select name="cHD_EMAIL_GLOBALBCC_TYPE">
                            <option value="public" {{ selectionCheck('public', hs_setting('cHD_EMAIL_GLOBALBCC_TYPE')) }}>{{ lg_admin_settings_globalbcc_typepublic }}</option>
                            <option value="all" {{ selectionCheck('all', hs_setting('cHD_EMAIL_GLOBALBCC_TYPE')) }}>{{ lg_admin_settings_globalbcc_typeall }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_EMAIL_REPLYABOVE">{{ lg_admin_settings_replyabove }}</label>
                    <div class="info">{{ lg_admin_settings_replyaboveex }}</div>
                </div>
                <div class="control">
                    <input name="cHD_EMAIL_REPLYABOVE" id="cHD_EMAIL_REPLYABOVE" type="text" size="60" maxlength="255" value="{{ formClean(hs_setting('cHD_EMAIL_REPLYABOVE')) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label tdlcheckbox">
                    <label for="cHD_MAIL_ALLOWMAILATTACHMENTS">{{ lg_admin_settings_allowattachments }}</label>
                </div>
                <div class="control">
                    <input type="checkbox" name="cHD_MAIL_ALLOWMAILATTACHMENTS" id="cHD_MAIL_ALLOWMAILATTACHMENTS" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_MAIL_ALLOWMAILATTACHMENTS')) }}>
                    <label for="cHD_MAIL_ALLOWMAILATTACHMENTS" class="switch"></label>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_EXCLUDEMIMETYPES">{{ lg_admin_settings_excludemimes }}</label>
                    <div class="info">{{ lg_admin_settings_excludemimesex }}</div>
                </div>
                <div class="control">
                    <input name="cHD_EXCLUDEMIMETYPES" id="cHD_EXCLUDEMIMETYPES" type="text" size="60" maxlength="255" value="{{ formClean(hs_setting('cHD_EXCLUDEMIMETYPES')) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_MAIL_MAXATTACHSIZE">{{ lg_admin_settings_maxattachsize }}</label>
                    <div class="info">{!! lg_admin_settings_maxattachsizeex !!}</div>
                </div>
                <div class="control">
                    <select name="cHD_MAIL_MAXATTACHSIZE">
                        <option value="500000" {{ selectionCheck('100000', hs_setting('cHD_MAIL_MAXATTACHSIZE')) }}>100 K</option>
                        <option value="500000" {{ selectionCheck('500000', hs_setting('cHD_MAIL_MAXATTACHSIZE')) }}>500 K</option>
                        <option value="1000000" {{ selectionCheck('1000000', hs_setting('cHD_MAIL_MAXATTACHSIZE')) }}>1 MB</option>
                        <option value="3000000" {{ selectionCheck('3000000', hs_setting('cHD_MAIL_MAXATTACHSIZE')) }}>3 MB</option>
                        <option value="5000000" {{ selectionCheck('5000000', hs_setting('cHD_MAIL_MAXATTACHSIZE')) }}>5 MB</option>
                        <option value="10000000" {{ selectionCheck('10000000', hs_setting('cHD_MAIL_MAXATTACHSIZE')) }}>10 MB</option>
                        <option value="25000000" {{ selectionCheck('25000000', hs_setting('cHD_MAIL_MAXATTACHSIZE')) }}>25 MB</option>
                        <option value="50000000" {{ selectionCheck('50000000', hs_setting('cHD_MAIL_MAXATTACHSIZE')) }}>50 MB</option>
                        <option value="100000000" {{ selectionCheck('100000000', hs_setting('cHD_MAIL_MAXATTACHSIZE')) }}>100 MB</option>
                    </select>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_EMAIL_DAYS_AFTER_CLOSE">{{ lg_admin_settings_emaildaysafter }}</label>
                    <div class="info">{{ lg_admin_settings_emaildaysafterex }}</div>
                </div>
                <div class="control">
                    <select name="cHD_EMAIL_DAYS_AFTER_CLOSE">
                        <option value="1" {{ selectionCheck('1', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>1 {{ lg_day }}</option>
                        <option value="2" {{ selectionCheck('2', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>2 {{ lg_days }}</option>
                        <option value="3" {{ selectionCheck('3', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>3 {{ lg_days }}</option>
                        <option value="4" {{ selectionCheck('4', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>4 {{ lg_days }}</option>
                        <option value="5" {{ selectionCheck('5', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>5 {{ lg_days }}</option>
                        <option value="10" {{ selectionCheck('10', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>10 {{ lg_days }}</option>
                        <option value="15" {{ selectionCheck('15', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>15 {{ lg_days }}</option>
                        <option value="30" {{ selectionCheck('30', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>30 {{ lg_days }}</option>
                        <option value="90" {{ selectionCheck('90', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>90 {{ lg_days }}</option>
                        <option value="365" {{ selectionCheck('365', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>365 {{ lg_days }}</option>
                        <option value="0" {{ selectionCheck('0', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>{{ lg_admin_settings_emaildaysafter_always }}</option>
                        <option value="never" {{ selectionCheck('never', hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) }}>{{ lg_admin_settings_emaildaysafter_never }}</option>
                    </select>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_EMAILS_MAX_TO_IMPORT">{{ lg_admin_settings_emailmaximport }}</label>
                    <div class="info">{{ lg_admin_settings_emailmaximportex }}</div>
                </div>
                <div class="control">
                    <input name="cHD_EMAILS_MAX_TO_IMPORT" id="cHD_EMAILS_MAX_TO_IMPORT" type="text" size="10" maxlength="10" value="{{ formClean(hs_setting('cHD_EMAILS_MAX_TO_IMPORT')) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label tdlcheckbox">
                    <label for="cHD_HTMLEMAILS_FILTER_IMG">{{ lg_admin_settings_htmlemails_filterimg }}</label>
                    <div class="info">{{ lg_admin_settings_htmlemails_filterimgex }}</div>
                </div>
                <div class="control">
                    <input type="checkbox" name="cHD_HTMLEMAILS_FILTER_IMG" id="cHD_HTMLEMAILS_FILTER_IMG" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_HTMLEMAILS_FILTER_IMG')) }}>
                    <label for="cHD_HTMLEMAILS_FILTER_IMG" class="switch"></label>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label tdlcheckbox">
                    <label for="cSTAFFREPLY_AS_PUBLIC">{{ lg_admin_settings_reply_as_public }}</label>
                    <div class="info">{{ lg_admin_settings_reply_as_publicex }}</div>
                </div>
                <div class="control">
                    <input type="checkbox" name="cSTAFFREPLY_AS_PUBLIC" id="cSTAFFREPLY_AS_PUBLIC" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cSTAFFREPLY_AS_PUBLIC')) }}>
                    <label for="cSTAFFREPLY_AS_PUBLIC" class="switch"></label>
                </div>
            </div>

            <div class="hr"></div>

            {{--
            <div class="fr">
                <div class="label tdlcheckbox">
                    <label for="cHD_TASKSDEBUG">{{ lg_admin_settings_debugtasks }}</label>
                    <div class="info">{{ lg_admin_settings_debugtasksex }}</div>
                </div>
                <div class="control">
                    <input type="checkbox" name="cHD_TASKSDEBUG" id="cHD_TASKSDEBUG" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_TASKSDEBUG')) }}>
                    <label for="cHD_TASKSDEBUG" class="switch"></label>
                </div>
            </div>

            <div class="hr"></div>
            --}}

        </fieldset>

        <fieldset class="fieldset">
            <div class="sectionhead">{{ lg_admin_settings_spamsettings }}</div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_SPAMFILTER">{{ lg_admin_settings_spamfilter }}</label>
                    <div class="info">{{ lg_admin_settings_spamfilterex }}</div>
                </div>
                <div class="control">
                    <select name="cHD_SPAMFILTER">
                        <option value="1" {{ selectionCheck('1', hs_setting('cHD_SPAMFILTER')) }}>{{ lg_admin_settings_spamfilter_on }}</option>
                        <option value="2" {{ selectionCheck('2', hs_setting('cHD_SPAMFILTER')) }}>{{ lg_admin_settings_spamfilter_checkonly }}</option>
                        <option value="0" {{ selectionCheck('0', hs_setting('cHD_SPAMFILTER')) }}>{{ lg_admin_settings_spamfilter_off }}</option>
                    </select>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_SPAM_WHITELIST">{{ lg_admin_settings_spam_whitelist }}</label>
                    <div class="info">{{ lg_admin_settings_spam_whitelistex }}</div>
                </div>
                <div class="control">
                    <textarea name="cHD_SPAM_WHITELIST" rows="5" cols="60" style="">{{ formClean(hs_setting('cHD_SPAM_WHITELIST')) }}</textarea><br>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_SPAM_BLACKLIST">{{ lg_admin_settings_spam_blacklist }}</label>
                    <div class="info">{{ lg_admin_settings_spam_blacklistex }}</div>
                </div>
                <div class="control">
                    <textarea name="cHD_SPAM_BLACKLIST" rows="5" cols="60" style="">{{ formClean(hs_setting('cHD_SPAM_BLACKLIST')) }}</textarea><br>
                </div>
            </div>
        </fieldset>

        {!! displayContentBoxTop(lg_admin_settings_loopnew, lg_admin_settings_loopnewex) !!}

        <div class="fr">
            <div class="label">
                <label for="cHD_EMAIL_LOOPCHECK_TIME">{{ lg_admin_settings_loopnewtime }}</label>
                <div class="info">{{ lg_admin_settings_loopnewtimeex }}</div>
            </div>
            <div class="control">
                <select name="cHD_EMAIL_LOOPCHECK_TIME">
                    <option value="-10 minute" {{ selectionCheck('-10 minute', hs_setting('cHD_EMAIL_LOOPCHECK_TIME')) }}>10 {{ lg_admin_settings_min }}</option>
                    <option value="-30 minute" {{ selectionCheck('-30 minute', hs_setting('cHD_EMAIL_LOOPCHECK_TIME')) }}>30 {{ lg_admin_settings_min }}</option>
                    <option value="-1 hour" {{ selectionCheck('-1 hour', hs_setting('cHD_EMAIL_LOOPCHECK_TIME')) }}>1 {{ lg_admin_settings_hour }}</option>
                    <option value="-4 hour" {{ selectionCheck('-4 hour', hs_setting('cHD_EMAIL_LOOPCHECK_TIME')) }}>4 {{ lg_admin_settings_hours }}</option>
                    <option value="-12 hour" {{ selectionCheck('-12 hour', hs_setting('cHD_EMAIL_LOOPCHECK_TIME')) }}>12 {{ lg_admin_settings_hours }}</option>
                    <option value="-24 hour" {{ selectionCheck('-24 hour', hs_setting('cHD_EMAIL_LOOPCHECK_TIME')) }}>24 {{ lg_admin_settings_hours }}</option>
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_EMAIL_LOOPCHECK_CTMAX">{{ lg_admin_settings_loopnewct }}</label>
                <div class="info">{{ lg_admin_settings_loopnewctex }}</div>
            </div>
            <div class="control">
                <input name="cHD_EMAIL_LOOPCHECK_CTMAX" id="cHD_EMAIL_LOOPCHECK_CTMAX" type="text" size="10" maxlength="255" value="{{ formClean(trim(hs_setting('cHD_EMAIL_LOOPCHECK_CTMAX'))) }}">
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_EMAILLOOP_TIME">{{ lg_admin_settings_emailloop }}<br /><span>{{ lg_admin_settings_inseconds }}</span></label>
                <div class="info">{{ lg_admin_settings_emailloopex }}</div>
            </div>
            <div class="tdr">
                <input name="cHD_EMAILLOOP_TIME" id="cHD_EMAILLOOP_TIME" type="text" size="10" maxlength="255" value="{{ formClean(hs_setting('cHD_EMAILLOOP_TIME')) }}">
            </div>
        </div>

        {!! displayContentBoxBottom() !!}

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
