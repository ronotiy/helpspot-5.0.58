@extends('utility.layout', [
    'title' => 'SAML Login',
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
    <form name="loginform" onsubmit="return false;">
        <a href="https://www.helpspot.com" class="logo" target="_blank"><img src="{{ static_url() }}/static/img5/helpspot-logo-color.svg" width="200" border="0" /></a>

        <div id="auth-box">
            <div class="title">{{ hs_setting('cHD_ORGNAME') }}</div>
            <div class="title-line"></div>
            <div class="auth-body">
                <table cellpadding="0" cellspacing="5" border="0" class="hsnotificationbox hderrorbox">
                    <tr>
                        <td width="42">
                            <img src="{{ static_url() }}/static/img5/exclamation-triangle-solid.svg" width="32" height="32">
                        </td>
                        <td>HelpSpot could not find any registered users matching {{ $samlErrors['attempted'] ?? 'your username' }}.</td>
                    </tr>
                </table>
                @if( $debug && $samlErrors )
                    <div class="input-wrap">
                        <div style="padding:30px 0 15px 0;">
                            <h4><strong>Debug information</strong>:</h4>
                        </div>
                        <div style="padding: 20px; border: 1px solid #ccc; border-radius: 3px; background:#f8f9ff;">
                            <p style="padding-bottom: 14px;"><strong>Attempted username:</strong> {{ $samlErrors['attempted'] }}</p>
                            <p><strong>Attributes returned from the SAML Identity Provider:</strong></p>
                            <ul>
                                <li style="padding-bottom: 12px;"><strong>NameID:</strong> {{ $samlErrors['attributes']['nameId'] }}</li>
                                @foreach($samlErrors['attributes']['attributes'] as $attribute => $value)
                                    <li style="padding-bottom: 12px;"><strong>{{ $attribute }}:</strong><br /> {{ $value[0] }}</li>
                                @endforeach
                                @if( empty($samlErrors['attributes']) || (isset($samlErrors['attributes']['attributes']) && empty($samlErrors['attributes']['attributes'])) )
                                    <li style="padding-bottom: 12px;"><em>SAML provider returned no extra attributes.</em></li>
                                @endif
                            </ul>
                            <p>Debug mode is enabled. If you need to login using HelpSpot's default login mechanism, you can <a style="" href="{{ route('altlogin') }}">login here</a>.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </form>
@endsection
