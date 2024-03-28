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
                    @if( hs_setting('cAUTHTYPE', 'internal') == 'internal' )
                        <input type="email" name="sEmail" id="email" spellcheck="false" value="{{ old('sEmail') }}" placeholder="you@company.com" required autofocus />
                        @if ($errors->has('sEmail'))
                            <div class="authbox-error">
                                <strong>{{ $errors->first('sEmail') }}</strong>
                            </div>
                        @endif
                    @else
                        <input type="text" name="sUsername" id="sUsername" spellcheck="false" value="{{ old('sUsername') }}" placeholder="username" required autofocus />
                        @if ($errors->has('sUsername'))
                            <div class="authbox-error">
                                <strong>{{ $errors->first('sUsername') }}</strong>
                            </div>
                        @endif
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
                <div class="input-wrap clearfix" style="display:flex;align-items:center;justify-content:space-between;">
                    <label class="form-check-label" style="flex:1;">
                        <input class="form-check-input" type="hidden" name="remember" id="remember" value="yes">&nbsp;
                    </label>
                    <a style="text-decoration: none; " href="{{ route('password.request') }}">Forgot Password?</a>
                </div>
                <div class="input-wrap clearfix">
                    <button type="submit" class="btn accent auth-button">Sign In</button>
                </div>
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
