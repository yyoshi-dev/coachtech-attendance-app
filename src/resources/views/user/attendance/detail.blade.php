{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', '勤怠詳細画面（一般ユーザー）')

{{-- Header --}}
@section('header-logo-link', route('attendance.index'))

{{-- Header-Nav --}}
@section('header-nav')
@include('components.nav.user-default')
@endsection

{{-- CSS --}}
{{-- @section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection --}}