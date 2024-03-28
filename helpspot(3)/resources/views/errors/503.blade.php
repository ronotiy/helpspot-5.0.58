@extends('utility.layout', [
    'title' => 'Maintenance Mode',
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
    <div>
        <a href="https://www.helpspot.com" class="logo" target="_blank"><img src="{{ static_url() }}/static/img5/helpspot-logo-color.svg" width="200" border="0" /></a>

        <div id="auth-box" style="min-height: auto;">
            <div class="title">{{ hs_setting('cHD_ORGNAME') }}</div>
            <div class="title-line"></div>
            <div class="auth-body">
                <div style="display: flex; align-items: center;">
                    <div>
                        <svg style="width: 40px;" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="exclamation-triangle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="#208AAE" d="M569.517 440.013C587.975 472.007 564.806 512 527.94 512H48.054c-36.937 0-59.999-40.055-41.577-71.987L246.423 23.985c18.467-32.009 64.72-31.951 83.154 0l239.94 416.028zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z"></path></svg>
                    </div>
                    <div style="padding: 20px 0 20px 20px; font-size: 17px;">
                        {{ lg_maintenance_mode }}
                    </div>
                </div>
                @if(auth()->check())
                <form method="post" action="{{ route('maintenance') }}" style="display: flex; align-items: center; justify-content: center; padding-top: 16px;">
                    @csrf
                    <input type="hidden" name="status" value="up">
                    <button type="submit" class="btn accent auth-button" style="float: none;">Disable Maintenance Mode</button>
                </form>
                @endif
            </div>
        </div>
    </div>
@endsection
