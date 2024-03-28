@extends('errors.layout')

@section('title', __('Upgrade Required'))
@section('code', 'Error')
@section('message')
    The database needs to be upgraded, please review the <a href="https://support.helpspot.com/index.php?pg=kb.page&id=614">HelpSpot 5 documentation</a>.
@stop
