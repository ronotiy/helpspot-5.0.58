<div class="settings-box workers" id="box_id_{{ md5(lg_admin_settings_workers) }}">

    {!! renderPageheader(lg_admin_settings_workers) !!}

    <div class="card padded">

        <div class="fr">
            <div class="label">
                <label for="restart_queues">{{ lg_admin_settings_workers_restart }}</label>
                <div class="info">{{ lg_admin_settings_workers_restart_info }}</div>
            </div>
            <div class="control">
                <a href="{{ route('admin', ['pg' => 'admin.settings', 'restart_queues' => 1]) }}" class="btn">{{ lg_admin_settings_workers_restart }}</a>
            </div>
        </div>

        <div class="hr"></div>

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
