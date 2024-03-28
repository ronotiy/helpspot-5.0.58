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
                <div class="input-wrap clearfix" style="text-align: center;">
                    <p>{{ lg_logged_out }}</p>
                    <a href="{{ route('login') }}" class="btn accent auth-button" style="float: none; box-sizing: border-box;">{{ lg_logbackin }}</a>
                </div>

                @if( config('app.debug') )
                <div class="input-wrap clearfix" style="text-align:center;">
                    <hr>
                    <p>&nbsp;</p>
                    <p><strong>{{ lg_login_trouble }}</strong></p>
                    <p>{{ lg_login_use_default }}.</p>
                </div>
                <div class="input-wrap clearfix">
                    <input type="email" name="sEmail" id="email" spellcheck="false" value="{{ old('sEmail') }}" placeholder="you@company.com" required autofocus />
                    @if ($errors->has('sEmail'))
                        <div class="authbox-error">
                            <strong>{{ $errors->first('sEmail') }}</strong>
                        </div>
                    @endif
                </div>
                <div class="input-wrap clearfix">
                    <input type="password" name="password" id="password" autocomplete="off" spellcheck="false" placeholder="password" value="" />
                    @if ($errors->has('password'))
                        <div class="authbox-error">
                            <strong>{{ $errors->first('password') }}</strong>
                        </div>
                    @endif
                </div>
                <div class="input-wrap clearfix">
                    <a style="text-decoration: none; " href="{{ route('password.request') }}">Forgot Password?</a>
                </div>
                <div class="input-wrap clearfix">
                    <button type="submit" class="btn accent auth-button">Sign In</button>
                </div>
                @endif
            </div>
        </div>
        @if(! $mobileauth)
        <div class="auth-blogpost">
            News From HelpSpot:<br />
            <a href="{{ $article_link }}" target="_blank" class="latest-helpspot-post">{{ $article_title }}</a>
        </div>
        @endif
    </form>
@endsection
