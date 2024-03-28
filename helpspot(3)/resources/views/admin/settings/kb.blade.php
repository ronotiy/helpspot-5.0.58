<div class="settings-box emailintegration" id="box_id_{{ md5(lg_admin_settings_kb) }}">

    {!! renderPageheader(lg_admin_settings_kb) !!}

    <div class="card padded">


        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_WYSIWYG">{{ lg_admin_settings_wysiwyg }}</label>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_WYSIWYG" id="cHD_WYSIWYG" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_WYSIWYG')) }}>
                <label for="cHD_WYSIWYG" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="cHD_WYSIWYG_STYLES">{{ lg_admin_settings_wysiwygstyle }}</label>
                <div class="info">{{ lg_admin_settings_styleex }}</div>
            </div>
            <div class="control">
                <textarea name="cHD_WYSIWYG_STYLES" rows="10">{{ formClean(hs_setting('cHD_WYSIWYG_STYLES')) }}</textarea>
            </div>
        </div>

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
