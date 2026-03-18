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
<div class="attendance-content">
    <span class="attendance-status">{{ $status }}</span>
    <span class="attendance-date">{{ $date }}</span>
    <span class="attendance-time">{{ $time }}</span>
    <div class="attendance-form">
        @switch($status)
            @case('勤務外')
                <form action="/attendance/clock-in" method="post" class="attendance-form__form">
                    @csrf
                    <button type="submit" class="attendance-form__btn">出勤</button>
                </form>
                @break
            @case('出勤中')
                <form action="/attendance/clock-out" method="post" class="attendance-form__form">
                    @csrf
                    <button type="submit" class="attendance-form__btn">退勤</button>
                </form>
                <form action="/attendance/break-start" method="post" class="attendance-form__form">
                    @csrf
                    <button type="submit" class="attendance-form__btn attendance-form__btn--break">休憩入</button>
                </form>
                @break
            @case('休憩中')
                <form action="/attendance/break-end" method="post" class="attendance-form__form">
                    @csrf
                    <button type="submit" class="attendance-form__btn attendance-form__btn--break">休憩戻</button>
                </form>
                @break
            @default
                <span class="attendance-form__clock-out">お疲れ様でした。</span>
        @endswitch
    </div>
</div>
@endsection