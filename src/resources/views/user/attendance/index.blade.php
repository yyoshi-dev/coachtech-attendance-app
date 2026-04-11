{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', '勤怠登録画面（一般ユーザー）')

{{-- Header --}}
@section('header-logo-link', route('attendance.index'))

{{-- Header-Nav --}}
@section('header-nav')
@if ($isAfterWork)
    @include('components.nav.user-after-work')
@else
    @include('components.nav.user-default')
@endif
@endsection

{{-- CSS --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

{{-- Content --}}
@section('content')
<div class="attendance-content">
    <span class="attendance-status">{{ $status }}</span>
    <span id="date" class="attendance-date">{{ $currentDateTime->isoFormat('YYYY年M月D日(ddd)') }}</span>
    <span id="time" class="attendance-time">{{ $currentDateTime->isoFormat('HH:mm') }}</span>

    {{-- 時間のリアルタイム更新 --}}
    <script>
    const base = new Date(@json($currentDateTime)); // サーバー時刻
    const start = Date.now(); // スクリプトが読み込まれたブラウザ時刻

    const dateFmt = new Intl.DateTimeFormat('ja-JP', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'short'
    }); // 日付フォーマットの定義

    function render() {
        const now = new Date(base.getTime() + (Date.now() - start)); // サーバー時刻+ページを開いてからの経過時間

        document.getElementById('date').textContent = dateFmt.format(now);
        document.getElementById('time').textContent =
            now.toTimeString().slice(0,5);
    }

    // 以降は0.5秒毎に日付と時間を更新
    setInterval(render, 500);
    </script>

    {{-- 勤怠登録ボタン --}}
    <div class="attendance-form">
        @switch($status)
            @case('勤務外')
                <form action="{{ route('attendance.clock-in') }}" method="post" class="attendance-form__form">
                    @csrf
                    <button data-testid="clock-in-button" type="submit" class="attendance-form__btn btn">出勤</button>
                </form>
                @break
            @case('出勤中')
                <form action="{{ route('attendance.clock-out') }}" method="post" class="attendance-form__form">
                    @csrf
                    <button data-testid="clock-out-button" type="submit" class="attendance-form__btn btn">退勤</button>
                </form>
                <form action="{{ route('attendance.break-start') }}" method="post" class="attendance-form__form">
                    @csrf
                    <button data-testid="break-start-button" type="submit" class="attendance-form__btn attendance-form__btn--break btn">休憩入</button>
                </form>
                @break
            @case('休憩中')
                <form action="{{ route('attendance.break-end') }}" method="post" class="attendance-form__form">
                    @csrf
                    <button data-testid="break-end-button" type="submit" class="attendance-form__btn attendance-form__btn--break btn">休憩戻</button>
                </form>
                @break
            @default
                <span class="attendance-form__clock-out">お疲れ様でした。</span>
        @endswitch
    </div>
</div>
@endsection