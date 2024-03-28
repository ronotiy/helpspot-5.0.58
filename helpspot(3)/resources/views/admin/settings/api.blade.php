<div class="settings-box emailintegration" id="box_id_{{ md5(lg_admin_settings_ws) }}">

    {!! renderPageheader(lg_admin_settings_ws) !!}

    <div class="card padded">

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_WSPUBLIC">{{ lg_admin_settings_wspub }}</label>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_WSPUBLIC" id="cHD_WSPUBLIC" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_WSPUBLIC')) }}>
                <label for="cHD_WSPUBLIC" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_WSPRIVATE">{{ lg_admin_settings_wspriv }}</label>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_WSPRIVATE" id="cHD_WSPRIVATE" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_WSPRIVATE')) }}>
                <label for="cHD_WSPRIVATE" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_WSPUBLIC_EXCLUDECOLUMNS">{{ lg_admin_settings_wspubcols }}</label>
                <div class="info">{{ lg_admin_settings_wspubcolsex }}</div>
            </div>
            <div class="control">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr valign="top">
                        <td width="150" style="padding-right:8px;">
                            @foreach ($api_methods as $k=>$v)
                                <div class="wscolslnk" id="wscolslnk_{{ str_replace('.', '_', $k) }}" onclick="setWSEdit('{{ str_replace('.', '_', $k) }}');">{{ $k }}</div>
                            @endforeach
                        </td>
                        <td id="wspubcols_edit" style="border-left:1px solid #ccc;padding-left:8px;">
                            @foreach ($api_methods as $k=>$v)
                            <div id="wscols_{{ str_replace('.', '_', $k) }}" class="wscols" style="display:none;">
                                <div style="margin-bottom:6px;font-weight:bold;">{{ $k }}</div>
                                @foreach ($v as $field=>$default)
                                @php
                                $checked = 'checked';
                                if (in_array($field, $excluded_columns[$k] ?? [])) {
                                $checked = '';
                                } elseif (! isset($excluded_columns[$k]) && $default == false) { //For when excluded cols have never been saved before in a new installation or a new API method
                                $checked = '';
                                }
                                @endphp

                                <div style="margin-bottom:3px;">
                                    <input type="checkbox" name="excludecols_{{ str_replace('.', '_', $k) }}[]" value="{{ $field }}" style="vertical-align:middle;margin-right:4px;" {{ $checked }} />
                                    {{ $field.(substr($field, 0, 6) == 'Custom' ? ' ('.$GLOBALS['customFields'][utf8_substr($field, 6, 1)]['fieldName'].')' : '') }}<br>
                                </div>
                                @endforeach
                            </div>
                            @endforeach
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
