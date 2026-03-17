{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', '勤怠一覧画面（管理者）')

{{-- Header --}}
@section('header-logo-link', url('/admin/login'))

{{-- Header-Nav --}}
@section('header-nav')
@include('components.nav.admin')
@endsection

{{-- CSS --}}
{{-- @section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection --}}

{{-- Content --}}
@section('content')
<h1>勤怠一覧画面（管理者）</h1>
@endsection