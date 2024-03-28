<div class="settings-box timetracker" id="box_id_{{ md5(lg_admin_settings_timetracking) }}">

    {!! renderPageheader(lg_admin_settings_timetracking) !!}

    <div class="card padded">

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="cHD_TIMETRACKER">{{ lg_admin_settings_timetrackingon }}</label>
            </div>
            <div class="control">
                <input type="checkbox" name="cHD_TIMETRACKER" id="cHD_TIMETRACKER" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_TIMETRACKER')) }}>
                <label for="cHD_TIMETRACKER" class="switch"></label>
            </div>
        </div>

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
