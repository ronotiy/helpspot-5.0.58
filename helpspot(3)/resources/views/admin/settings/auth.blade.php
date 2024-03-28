<div class="settings-box emailintegration" id="box_id_{{ md5(lg_admin_settings_auth) }}">

    {!! renderPageheader(lg_admin_settings_auth) !!}

    <div class="card padded">

        <div class="fr">
            <div class="label">
                <label for="cAUTHTYPE">{{ lg_admin_settings_authtype }}</label>
                <div class="info" id="authblock" style="display:none;"> {{ lg_admin_settings_blackboxdesc }}</div>
                <div class="info" id="ldapblock" style="display:none;">{{ lg_admin_settings_ldap_usernamenote }}</div>
                <div class="info" id="samlblock" style="display:none;">{{ lg_admin_settings_saml_usernamenote }}</div>
            </div>
            <div class="control">
                <select name="cAUTHTYPE" id="cAUTHTYPE" onChange="authSwitch();">
                    <option value="internal" {{ selectionCheck('internal', hs_setting('cAUTHTYPE')) }}>{{ lg_admin_settings_hsauth }}</option>
                    <option value="blackbox" {{ selectionCheck('blackbox', hs_setting('cAUTHTYPE')) }}>{{ lg_admin_settings_blackbox }}</option>
                    <option value="ldap_ad" {{ selectionCheck('ldap_ad', hs_setting('cAUTHTYPE')) }}>{{ lg_admin_settings_ldap }}</option>
                    <option value="saml" {{ selectionCheck('saml', hs_setting('cAUTHTYPE')) }}>{{ lg_admin_settings_saml }}</option>
                </select>
            </div>
        </div>

        <div id="saml_options" style="display:none;">
            <fieldset class="fieldset">
                <div>
                    <div class="sectionhead">{{ lg_admin_settings_saml_idp }}</div>
                </div>


                <div class="fr">
                    <div class="label">
                        <label for="cHD_SAML_ENTITYID">{{ lg_admin_settings_saml_entityid }}</label>
                        <div class="info">{{ lg_admin_settings_saml_entityid_ex }}</div>
                    </div>
                    <div class="control">
                        <input name="cHD_SAML_ENTITYID" id="cHD_SAML_ENTITYID" type="text" value="{{ formClean($saml['cHD_SAML_ENTITYID']) }}">
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="cHD_SAML_LOGINURL">{{ lg_admin_settings_saml_loginurl }}</label>
                        <div class="info">{{ lg_admin_settings_saml_loginurl_ex }}</div>
                    </div>
                    <div class="control">
                        <input name="cHD_SAML_LOGINURL" id="cHD_SAML_LOGINURL" type="text" value="{{ formClean($saml['cHD_SAML_LOGINURL']) }}">
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="cHD_SAML_LOGOUTURL">{{ lg_admin_settings_saml_logouturl }}</label>
                        <div class="info">{{ lg_admin_settings_saml_logouturl_ex }}</div>
                    </div>
                    <div class="control">
                        <input name="cHD_SAML_LOGOUTURL" id="cHD_SAML_LOGOUTURL" type="text" value="{{ formClean($saml['cHD_SAML_LOGOUTURL']) }}">
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="cHD_SAML_CERT">{{ lg_admin_settings_saml_cert }}</label>
                        <div class="info">{{ lg_admin_settings_saml_cert_ex }}</div>
                    </div>
                    <div class="control">
                        <textarea name="cHD_SAML_CERT" id="cHD_SAML_CERT" rows="10">{{ formClean($saml['cHD_SAML_CERT']) }}</textarea>
                    </div>
                </div>
            </fieldset>

            <fieldset class="fieldset">
                <div>
                    <div class="sectionhead">{{ lg_admin_settings_saml_sp }}</div>
                    <div style="padding: 10px 0 30px 0;">{{ lg_admin_settings_saml_sp_ex }}</div>
                </div>


                <div class="fr">
                    <div class="label"><label>{{ lg_admin_settings_saml_entityid }}</label></div>
                    <div class="control">
                        <div>{{ route('saml2_metadata', 'hs') }}</div>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label"><label>{{ lg_admin_settings_saml_acs }}</label></div>
                    <div class="control">
                        <div>{{ route('saml2_acs', 'hs') }}</div>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label"><label>{{ lg_admin_settings_saml_relaystate }}</label></div>
                    <div class="control">
                        <div>{{ route('admin') }}</div>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label"><label>{{ lg_admin_settings_saml_signonurl }}</label></div>
                    <div class="control">
                        <div>{{ route('saml2_login', 'hs') }}</div>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label"><label>{{ lg_admin_settings_saml_signouturl }}</label></div>
                    <div class="control">
                        <div>{{ route('saml2_sls', 'hs') }}</div>
                    </div>
                </div>

            </fieldset>
        </div>

        <div class="ft" id="ldap_ad_options" style="display:none;">

            @if(! function_exists('ldap_connect'))
            <tr>
                <td colspan="2" class="linespace">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td class="line" style="width:380px;">
                    <div class="red">{{ lg_admin_settings_ldap_noldap }}</div>
                </td>
            </tr>
            @endif

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_LDAP_ACCOUNT_SUFFIX">{{ lg_admin_settings_ldap_accountsuffix }}</label>
                    <div class="info">{{ lg_admin_settings_ldap_accountsuffix_ex }}</div>
                </div>
                <div class="control">
                    <input name="cHD_LDAP_ACCOUNT_SUFFIX" id="cHD_LDAP_ACCOUNT_SUFFIX" type="text" value="{{ formClean($ldap['cHD_LDAP_ACCOUNT_SUFFIX']) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_LDAP_BASE_DN">{{ lg_admin_settings_ldap_basedn }}</label>
                    <div class="info">{{ lg_admin_settings_ldap_basedn_ex }}</div>
                </div>
                <div class="control">
                    <input name="cHD_LDAP_BASE_DN" id="cHD_LDAP_BASE_DN" type="text" value="{{ formClean($ldap['cHD_LDAP_BASE_DN']) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="cHD_LDAP_DN_CONTROL">{{ lg_admin_settings_ldap_dn_control }}</label>
                    <div class="info">{{ lg_admin_settings_ldap_dn_control_ex }}</div>
                </div>
                <div class="control">
                    <input name="cHD_LDAP_DN_CONTROL" id="cHD_LDAP_DN_CONTROL" type="text" value="{{ formClean($ldap['cHD_LDAP_DN_CONTROL']) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label tdlcheckbox">
                    <label for="cHD_LDAP_USESSL">{{ lg_admin_settings_ldap_usessl }}</label>
                    <div class="info"> {!! lg_admin_settings_ldap_usessl_ex !!}</div>
                </div>
                <div class="control">
                    <input type="checkbox" name="cHD_LDAP_USESSL" id="cHD_LDAP_USESSL" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_LDAP_USESSL')) }}>
                    <label for="cHD_LDAP_USESSL" class="switch"></label>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label tdlcheckbox">
                    <label for="cHD_LDAP_USETLS">{{ lg_admin_settings_ldap_usetls }}</label>
                </div>
                <div class="control">
                    <input type="checkbox" name="cHD_LDAP_USETLS" id="cHD_LDAP_USETLS" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_LDAP_USETLS')) }}>
                    <label for="cHD_LDAP_USETLS" class="switch"></label>
                </div>
            </div>
        </div>
    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
