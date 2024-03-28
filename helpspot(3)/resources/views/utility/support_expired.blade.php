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
<form name="ext_formfield" action="{{ route('license.store') }}" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
    <a href="https://www.helpspot.com" class="logo" target="_blank"><img src="{{ static_url() }}/static/img5/helpspot-logo-color.svg" width="200" border="0" /></a>
    <div id="auth-box">
        <div class="title">{{ lg_trialover }}</div>
        <div class="auth-body">

            @if (session('status'))
                <div class="hsnotificationbox hderrorbox">{{ session('status') }}</div>
            @endif

            <p>{{ lg_trialnote }} <a href="{{ createStoreLink() }}" target="_blank">{{ lg_purchase_here }}</a>.</p>

            <div class="input-wrap clearfix">
                <label class="auth-datalabel" for="customerid" style="display:inline-block; width: 160px;">{{ lg_trialcustid }}:</label>
                <b style="font-size:18px;">{{ hs_setting('cHD_CUSTOMER_ID') }}</b>
            </div>

            <div class="input-wrap clearfix">
                <label class="auth-datalabel" for="license" style="display:inline-block; width: 160px;">{{ lg_trialupload }}:</label>
                <input type="file" name="license" class="ext_formfield">
                @if ($errors->has('license'))
                    <div class="authbox-error">
                        <strong>{{ $errors->first('license') }}</strong>
                    </div>
                @endif
            </div>

            <div class="input-wrap clearfix">
                <button type="submit" class="btn accent auth-button">{{ lg_trialbutton }}</button>
                <input type="hidden" name="vmode" value="license_upload">
            </div>

        </div>
    </div>
</form>
@stop
