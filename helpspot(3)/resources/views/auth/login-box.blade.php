<div style="padding:10px 10px;">
    <form action="{{ route('login') }}" method="post" id="login-form" onsubmit="return login_form_sub(event);">
        @csrf
        <div style="display:flex;flex-direction:column;padding: 20px 40px;">
            @if( hs_setting('cAUTHTYPE', 'internal') == 'internal' )
                <label class="datalabel" for="popup_username">{{ lg_email }}</label>
                <input type="text" name="sEmail" id="popup_username" value="{{ $_COOKIE['tmp_login_user'] ?? '' }}" style="" />
            @else
                <label class="datalabel" for="popup_username">{{ lg_username }}</label>
                <input type="text" name="sUsername" id="popup_username" value="{{ $_COOKIE['tmp_login_user'] ?? '' }}" style="" />
            @endif
            <label class="datalabel" for="popup_password" style="margin-top:10px;">{{ lg_password }}</label>
            <input type="password" name="password" id="popup_password" value="" style="" />
            <div style="display:flex;justify-content:flex-end;margin-top:10px;">
                <button type="submit" class="btn accent">
                    {{ lg_loginbutton }}
                </button>
            </div>
        </div>
    </form>
</div>
