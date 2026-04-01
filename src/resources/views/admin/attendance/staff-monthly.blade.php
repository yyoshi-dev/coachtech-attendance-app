{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', 'スタッフ別勤怠一覧画面（管理者）')

{{-- Header --}}
@section('header-logo-link', route('admin.attendance.list'))

{{-- Header-Nav --}}
@section('header-nav')
@include('components.nav.admin')
@endsection

{{-- CSS --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

{{-- Content --}}
@section('content')

@endsection