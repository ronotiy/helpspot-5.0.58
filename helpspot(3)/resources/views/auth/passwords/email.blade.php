@extends('utility.layout', [
    'title' => 'Forgot Password',
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
    <form method="post" action="{{ route('password.email') }}" name="loginform">
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
                    <input type="email" name="email" id="email" spellcheck="false" value="{{ old('email') }}" placeholder="you@company.com" required autofocus />
                    <div class="username-icon"></div>
                    @if ($errors->has('email'))
                        <div class="authbox-error">
                            <strong>{{ $errors->first('email') }}</strong>
                        </div>
                    @endif
                </div>
                <div class="input-wrap clearfix">
                    <button type="submit" class="btn accent auth-button">Send Password Reset Link</button>
                </div>
            </div>
        </div>
    </form>
@endsection
