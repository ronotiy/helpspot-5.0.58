<div class="settings-box" id="box_id_{{ md5(lg_admin_settings_datetime) }}">

    {!! renderPageheader(lg_admin_settings_datetime) !!}

    <div class="card padded">
        <div class="fr">
            <div class="label"><label for="cHD_TIMEZONE_OVERRIDE">{{ lg_admin_settings_tz }}</label></div>
            <div class="control">{!! $timezoneSelect !!}</div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_DATEFORMAT">{{ lg_admin_settings_dateformat }}</label>
                <div class="info">{{ lg_admin_settings_dateformatex }} <a href="http://us3.php.net/strftime" target="_blank">{{ lg_admin_settings_dateformatlink }}</a></div>
            </div>
            <div class="control">
                <div class="group">
                    <input name="cHD_DATEFORMAT" id="cHD_DATEFORMAT" type="text" size="30" value="{{ formClean(hs_setting('cHD_DATEFORMAT')) }}" style="margin-right: 8px;">
                    <select id="dfs" onChange="switchDate('cHD_DATEFORMAT','dfs');">
                        {!! $dateselect !!}
                    </select>
                </div>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_SHORTDATEFORMAT">{{ lg_admin_settings_shortdateformat }}</label>
            </div>
            <div class="control">
                <div class="group">
                    <input name="cHD_SHORTDATEFORMAT" id="cHD_SHORTDATEFORMAT" type="text" size="30" value="{{ formClean(hs_setting('cHD_SHORTDATEFORMAT')) }}" style="margin-right: 8px;">
                    <select id="sdfs" onChange="switchDate('cHD_SHORTDATEFORMAT','sdfs');">
                        {!! $shortdateselect !!}
                    </select>
                </div>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label for="cHD_POPUPCALDATEFORMAT">{{ lg_admin_settings_popupcalformat }}</label></div>
            <div class="control">
                <select name="cHD_POPUPCALDATEFORMAT" id="cHD_POPUPCALDATEFORMAT">
                    <option value="%m/%d/%Y %I:%M %p" {{ selectionCheck('%m/%d/%Y %I:%M %p', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%m/%d/%Y %I:%M %p') }}</option>
                    <option value="%m/%d/%Y %H:%M" {{ selectionCheck('%m/%d/%Y %H:%M', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%m/%d/%Y %H:%M') }}</option>
                    <option value="%d/%m/%Y %I:%M %p" {{ selectionCheck('%d/%m/%Y %I:%M %p', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%d/%m/%Y %I:%M %p') }}</option>
                    <option value="%d/%m/%Y %H:%M" {{ selectionCheck('%d/%m/%Y %H:%M', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%d/%m/%Y %H:%M') }}</option>
                    <option value="%d %b %Y %I:%M %p" {{ selectionCheck('%d %b %Y %I:%M %p', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%d %b %Y %I:%M %p') }}</option>
                    <option value="%d %b %Y %H:%M" {{ selectionCheck('%d %b %Y %H:%M', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%d %b %Y %H:%M') }}</option>
                    <option value="%d %B %Y %I:%M %p" {{ selectionCheck('%d %B %Y %I:%M %p', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%d %B %Y %I:%M %p') }}</option>
                    <option value="%d %B %Y %H:%M" {{ selectionCheck('%d %B %Y %H:%M', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%d %B %Y %H:%M') }}</option>
                    <option value="%b %d %Y %I:%M %p" {{ selectionCheck('%b %d %Y %I:%M %p', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%b %d %Y %I:%M %p') }}</option>
                    <option value="%b %d %Y %H:%M" {{ selectionCheck('%b %d %Y %H:%M', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%b %d %Y %H:%M') }}</option>
                    <option value="%B %d %Y %I:%M %p" {{ selectionCheck('%B %d %Y %I:%M %p', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%B %d %Y %I:%M %p') }}</option>
                    <option value="%B %d %Y %H:%M" {{ selectionCheck('%B %d %Y %H:%M', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%B %d %Y %H:%M') }}</option>
                    <option value="%Y-%m-%d %I:%M %p" {{ selectionCheck('%Y-%m-%d %I:%M %p', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%Y-%m-%d %I:%M %p') }}</option>
                    <option value="%Y-%m-%d %H:%M" {{ selectionCheck('%Y-%m-%d %H:%M', hs_setting('cHD_POPUPCALDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%Y-%m-%d %H:%M') }}</option>
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label for="cHD_POPUPCALSHORTDATEFORMAT">{{ lg_admin_settings_popupcalshortformat }}</label></div>
            <div class="control">
                <select name="cHD_POPUPCALSHORTDATEFORMAT" id="cHD_POPUPCALSHORTDATEFORMAT">
                    <option value="%m/%d/%Y" {{ selectionCheck('%m/%d/%Y', hs_setting('cHD_POPUPCALSHORTDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%m/%d/%Y') }}</option>
                    <option value="%d/%m/%Y" {{ selectionCheck('%d/%m/%Y', hs_setting('cHD_POPUPCALSHORTDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%d/%m/%Y') }}</option>
                    <option value="%d %b %Y" {{ selectionCheck('%d %b %Y', hs_setting('cHD_POPUPCALSHORTDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%d %b %Y') }}</option>
                    <option value="%d %B %Y" {{ selectionCheck('%d %B %Y', hs_setting('cHD_POPUPCALSHORTDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%d %B %Y') }}</option>
                    <option value="%b %d %Y" {{ selectionCheck('%b %d %Y', hs_setting('cHD_POPUPCALSHORTDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%b %d %Y') }}</option>
                    <option value="%B %d %Y" {{ selectionCheck('%B %d %Y', hs_setting('cHD_POPUPCALSHORTDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%B %d %Y') }}</option>
                    <option value="%Y-%m-%d" {{ selectionCheck('%Y-%m-%d', hs_setting('cHD_POPUPCALSHORTDATEFORMAT')) }}>{{ hs_showCustomDate(date('U'), '%Y-%m-%d') }}</option>
                </select>
            </div>
        </div>

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
