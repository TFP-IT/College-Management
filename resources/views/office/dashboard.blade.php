@extends('office.layout')
@section('title', 'Today')
@section('content')
    @include('portal.dashboard', ['dash' => $dash])
@endsection
