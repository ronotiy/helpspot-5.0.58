<div class="settings-box emailintegration" id="box_id_{{ md5(lg_admin_settings_livelookup) }}">

    {!! renderPageheader(lg_admin_settings_livelookup) !!}

    <div class="card padded">

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_LIVELOOKUP">{{ lg_admin_settings_uselivelookup }}</label>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_LIVELOOKUP" id="cHD_LIVELOOKUP" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_LIVELOOKUP')) }}>
                <label for="cHD_LIVELOOKUP" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_LIVELOOKUPAUTO">{{ lg_admin_settings_llauto }}</label>
                <div class="info">{{ lg_admin_settings_llautoex }}</div>
            </div>
            <div class="control">
                <select name="cHD_LIVELOOKUPAUTO">
                    <option value="" {{ selectionCheck('', hs_setting('cHD_LIVELOOKUPAUTO')) }}>{{ lg_admin_settings_llautonorun }}</option>
                    <option value="sUserId" {{ selectionCheck('sUserId', hs_setting('cHD_LIVELOOKUPAUTO')) }}>{{ lg_lookup_filter_custid }}</option>
                    <option value="sEmail" {{ selectionCheck('sEmail', hs_setting('cHD_LIVELOOKUPAUTO')) }}>{{ lg_lookup_filter_custemail }}</option>
                    <option value="sPhone" {{ selectionCheck('sPhone', hs_setting('cHD_LIVELOOKUPAUTO')) }}>{{ lg_lookup_filter_custphone }}</option>
                    <option value="sLastName" {{ selectionCheck('sLastName', hs_setting('cHD_LIVELOOKUPAUTO')) }}>{{ lg_lookup_filter_lastname }}</option>';
                    @foreach ($GLOBALS['customFields'] as $k => $v)

                    <option value="Custom{{ $v['fieldID'] }}" {{ selectionCheck('Custom'. $v['fieldID'], hs_setting('cHD_LIVELOOKUPAUTO')) }}>{{ $v['fieldName'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @foreach ($live_lookup_searches as $l => $llv)

        <fieldset class="fieldset">
            <div class="sectionhead">{{ lg_admin_settings_llpath }} #{{ $loop->index+1 }}</div>

            <div class="fr">
                <div class="label">
                    <label for="livelookup_{{ $loop->index+1 }}_name">{{ lg_admin_settings_llsearchname }}</label>
                    <div class="info">{{ lg_admin_settings_llsearchnameex }}</div>
                </div>
                <div class="control">
                    <input name="livelookup_{{ $loop->index+1 }}_name" id="livelookup_{{ $loop->index+1 }}_name" type="text" size="30" maxlength="255" value="{{ formClean($llv['name']) }}">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="livelookup_{{ $loop->index+1 }}_type">{{ lg_admin_settings_lltype }}</label>
                    <div class="info">{{ lg_admin_settings_lltypeex }}</div>
                </div>
                <div class="control">
                    <select name="livelookup_{{ $loop->index+1 }}_type">
                        <option value="http" {{ selectionCheck('http', $llv['type']) }}>{{ lg_admin_settings_http }}</option>
                        @if (function_exists('curl_init'))
                        <option value="http-post" {{ selectionCheck('http-post', $llv['type']) }}>{{ lg_admin_settings_httppost }}</option>
                        @endif
                        <option value="cmdline" {{ selectionCheck('cmdline', $llv['type']) }}>{{ lg_admin_settings_cmd }}</option>
                    </select>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="livelookup_{{ $loop->index+1 }}_path">{{ lg_admin_settings_llpathtoscript }}</label>
                    <div class="info">{{ lg_admin_settings_llpathex }}</div>
                </div>
                <div class="control">
                    <input name="livelookup_{{ $loop->index+1 }}_path" id="livelookup_{{ $loop->index+1 }}_path" type="text" size="70" value="{{ formClean($llv['path']) }}">
                </div>
            </div>

        </fieldset>

        @endforeach

        <div class="" id="new_livelookup_link" style="margin-top:20px;">
            <div class="fr">
                <div class="label">&nbsp;</div>
                <div class="control">
                    <a href="" onclick="addNewLiveLookupSource();return false;" class="btn">
                        <img src="{{ static_url() }}/static/img/space.gif" class="button-add" alt="">
                        {{ lg_admin_settings_lladd }}</a>
                </div>
            </div>
        </div>

        <input type="hidden" name="livelookup_count" value="{{ $llsearchct }}" />

        <fieldset class="fieldset" id="new_livelookup" style="display:none;">
            <div class="sectionhead">{{ lg_admin_settings_llpath }} #{{ $llsearchct }}</div>

            <div class="fr">
                <div class="label">
                    <label for="livelookup_{{ $llsearchct }}_name">{{ lg_admin_settings_llsearchname }}</label>
                    <div class="info">{{ lg_admin_settings_llsearchnameex }}</div>
                </div>
                <div class="control">
                    <input name="livelookup_{{ $llsearchct }}_name" id="livelookup_{{ $llsearchct }}_name" type="text" size="30" maxlength="255" value="">
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="livelookup_{{ $llsearchct }}_type">{{ lg_admin_settings_lltype }}</label>
                    <div class="info">{{ lg_admin_settings_lltypeex }}</div>
                </div>
                <div class="control">
                    <select name="livelookup_{{ $llsearchct }}_type">
                        <option value="http" selected>{{ lg_admin_settings_http }}</option>
                        @if (function_exists('curl_init'))
                        <option value="http-post">{{ lg_admin_settings_httppost }}</option>
                        @endif
                        <option value="cmdline">{{ lg_admin_settings_cmd }}</option>
                    </select>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label for="livelookup_{{ $llsearchct }}_path">{{ lg_admin_settings_llpathtoscript }}</label>
                    <div class="info">{{ lg_admin_settings_llpathex }}</div>
                </div>
                <div class="control">
                    <input name="livelookup_{{ $llsearchct }}_path" id="livelookup_{{ $llsearchct }}_path" type="text" size="70" value="">
                </div>
            </div>

        </fieldset>
        <script type="text/javascript">
            //Show LL new source box if no other ones set
            if (!$("livelookup_2_path")) {
                addNewLiveLookupSource();
            }
        </script>

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
