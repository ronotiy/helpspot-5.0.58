@section('theme_js')
@if( $file_exists )
<link rel="stylesheet" href="themes/{{ $theme }}/{{ $theme }}.js" media="screen">
@endif
@stop