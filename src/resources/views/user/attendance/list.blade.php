{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', '勤怠一覧画面（一般ユーザー）')

{{-- Header --}}
@section('header-logo-link', route('attendance.index'))

{{-- Header-Nav --}}
@section('header-nav')
@include('components.nav.user-default')
@endsection

{{-- CSS --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

{{-- Content --}}
@section('content')
<div class="attendance-list">
    <h1 class="attendance-list__page-title page-title">勤怠一覧</h1>

    <div class="attendance-list__nav">
        <a href="{{ route('attendance.list', ['month' => $previousMonth]) }}" class="attendance-list__nav-item">
            <img src="{{ asset('images/arrow-image.png') }}" alt="" class="attendance-list__arrow-icon">
            <span class="attendance-list__nav-item-text">前月</span>
        </a>
        <div class="attendance-list__nav-period">
            <img src="{{ asset('images/calendar-image.png') }}" alt="" class="attendance-list__calendar-icon">
            <span class="attendance-list__nav-period-text">{{ $currentMonth }}</span>
        </div>
        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="attendance-list__nav-item">
            <span class="attendance-list__nav-item-text">翌月</span>
            <img src="{{ asset('images/arrow-image.png') }}" alt="" class="attendance-list__arrow-icon attendance-list__arrow-icon--reverse">
        </a>
    </div>

    <table class="attendance-table">
        <colgroup>
            <col class="attendance-table__col-date">
            <col class="attendance-table__col-time">
            <col class="attendance-table__col-time">
            <col class="attendance-table__col-time">
            <col class="attendance-table__col-time">
            <col class="attendance-table__col-detail">
        </colgroup>
        <thead>
            <tr class="attendance-table__row">
                <th class="attendance-table__header">日付</th>
                <th class="attendance-table__header">出勤</th>
                <th class="attendance-table__header">退勤</th>
                <th class="attendance-table__header">休憩</th>
                <th class="attendance-table__header">合計</th>
                <th class="attendance-table__header">詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($period as $date)
                @php $attendance = $attendances->get($date->toDateString()); @endphp
                <tr class="attendance-table__row">
                    <td class="attendance-table__item">{{ $date->isoFormat('MM/DD(ddd)') }}</td>
                    <td class="attendance-table__item">
                        {{ $attendance && $attendance->clock_in ? $attendance->clock_in->format('H:i') : '' }}
                    </td>
                    <td class="attendance-table__item">
                        {{ $attendance && $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}
                    </td>
                    <td class="attendance-table__item">
                        {{ $attendance && $attendance->breakTotalFormatted ? $attendance->breakTotalFormatted : '' }}
                    </td>
                    <td class="attendance-table__item">
                        {{ $attendance && $attendance->workTotalFormatted ? $attendance->workTotalFormatted : '' }}
                    </td>
                    <td class="attendance-table__item">
                        @if ($attendance)
                            <a href="{{ route('attendance.detail', ['id' => $attendance->id]) }}"
                                class="attendance-table__detail-link">詳細</a>
                        @else
                            <span class="attendance-table__detail-text">詳細</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection