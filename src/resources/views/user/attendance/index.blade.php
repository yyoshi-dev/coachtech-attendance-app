{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', '勤怠登録画面（一般ユーザー）')

{{-- Header --}}
@section('header-logo-link', url('/login'))

{{-- Header-Nav --}}
@section('header-nav')
@include('components.nav.user-default')
@endsection

{{-- CSS --}}
{{-- @section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-index.css') }}">
@endsection --}}

{{-- Content --}}
@section('content')
<h1>勤怠登録画面（一般ユーザー）</h1>
@endsection