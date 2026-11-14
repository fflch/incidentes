@extends('laravel-usp-theme::master')

@section('styles')
    @parent
    @include('partials.bs5-compat')
@endsection

@section('flash')
    @include('partials.flash-errors')
@endsection
