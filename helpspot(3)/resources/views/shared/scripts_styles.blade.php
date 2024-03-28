@section('scripts_styles_includes')
<!--PROTOTIP PATHS-->
<script type="text/javascript">
    var Tips = {
        options: {
            paths: {                                // paths can be relative to this file or an absolute url
                images:     "{{ $base_url }}/static/js/prototip/images/prototip/",
                javascript: "{{ $base_url }}/static/js/prototip/js/prototip/"
            },
            zIndex: 6000                            // raise if required
        }
    };
</script>
<link rel="stylesheet" href="{{ $base_url.mix('static/css/helpspot.css') }}" media="screen">
<link rel="stylesheet" href="{{ $base_url.mix('static/css/helpspot-print.css') }}" type="text/css" media="print" />
<script type="text/javascript" src="{{ $base_url.mix('static/js/helpspot.js') }}"></script>
@stop
