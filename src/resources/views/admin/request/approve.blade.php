{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', '修正申請承認画面（管理者）')

{{-- Header --}}
@section('header-logo-link', route('admin.attendance.list'))

{{-- Header-Nav --}}
@section('header-nav')
@include('components.nav.admin')
@endsection

{{-- CSS --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

{{-- Content --}}
@section('content')
@endsection