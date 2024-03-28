@extends('utility.layout', [
    'title' => 'Reset Password',
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
    <form method="post" action="{{ route('password.update') }}" name="loginform">
        @csrf
        <a href="https://www.helpspot.com" class="logo" target="_blank"><img src="{{ static_url() }}/static/img5/helpspot-logo-color.svg" width="200" border="0" /></a>

        <input type="hidden" name="token" value="{{ $token }}">
        <div id="auth-box">
            <div class="title">{{ hs_setting('cHD_ORGNAME') }}</div>
            <div class="auth-body">
                <div class="input-wrap clearfix form-title">
                    Create New Password
                </div>
                @if (session('status'))
                    <div class="hsnotificationbox hderrorbox">{{ session('status') }}</div>
                @endif
                <div class="input-wrap clearfix">
                    <input type="email" name="email" id="email" spellcheck="false" value="{{ isset($email) ? $email : old('email') }}" placeholder="you@company.com" required autofocus />
                    <div class="username-icon"></div>
                    @if ($errors->has('email'))
                        <div class="authbox-error">
                            <strong>{{ $errors->first('email') }}</strong>
                        </div>
                    @endif
                </div>
                <div class="input-wrap clearfix">
                    <input type="password" name="password" id="password" autocomplete="off" spellcheck="false" placeholder="New Password" value="" />
                    <div class="password-icon"></div>
                    @if ($errors->has('password'))
                        <div class="authbox-error">
                            <strong>{{ $errors->first('password') }}</strong>
                        </div>
                    @endif
                </div>
                <div class="input-wrap clearfix">
                    <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="off" spellcheck="false" placeholder="Confirm Password" value="" />
                    <div class="password-icon"></div>
                </div>
                <div class="input-wrap clearfix">
                    <button type="submit" class="btn accent auth-button">Reset Password</button>
                </div>
            </div>
        </div>
    </form>
@endsection
