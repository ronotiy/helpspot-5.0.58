@extends('utility.layout', [
    'title' => 'Login',
    'orgname' => hs_setting('cHD_ORGNAME'),
    'theme' => 'utility',
])

@section('scripts_styles_includes')
    @include('shared.scripts_styles', [
        'debug' => config('app.debug'),
        'static_direct' => defined('STATIC_DIRECT'),
        'base_url' => cHOST,
        'version' => instHSVersion,
    ])
@endsection

@section('body')
    <form method="post" action="{{ route('login') }}" name="loginform">
        @csrf
        <a href="https://www.helpspot.com" class="logo" target="_blank"><img src="{{ static_url() }}/static/img5/helpspot-logo-color.svg" width="200" border="0" /></a>

        <div id="auth-box">
            <div class="title">{{ hs_setting('cHD_ORGNAME') }}</div>
            <div class="title-line"></div>
            <div class="auth-body">
                @if (session('status'))
                    <div class="hsnotificationbox hderrorbox">{{ session('status') }}</div>
                @endif
                <div class="input-wrap clearfix">
                    <div style="text-align: center; font-size: 16px; padding: 50px 0;">
                        {{ hs_lang(lg_login_authenticating, 'Authenticating...') }}
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
