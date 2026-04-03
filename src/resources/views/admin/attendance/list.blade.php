{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', '勤怠一覧画面（管理者）')

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
<div class="attendance-list">
    <h1 class="attendance-list__page-title page-title">{{ $targetDate->format('Y年n月j日') }}の勤怠</h1>

    <div class="attendance-list__nav">
        <a href="{{ route('admin.attendance.list', ['date' => $previousDate->toDateString()]) }}" class="attendance-list__nav-item">
            <img src="{{ asset('images/arrow-image.png') }}" alt="" class="attendance-list__arrow-icon">
            <span class="attendance-list__nav-item-text">前日</span>
        </a>
        <div class="attendance-list__nav-period">
            <img src="{{ asset('images/calendar-image.png') }}" alt="" class="attendance-list__calendar-icon">
            <span class="attendance-list__nav-period-text">{{ $targetDate->format('Y/m/d') }}</span>
        </div>
        <a href="{{ route('admin.attendance.list', ['date' => $nextDate->toDateString()]) }}" class="attendance-list__nav-item">
            <span class="attendance-list__nav-item-text">翌日</span>
            <img src="{{ asset('images/arrow-image.png') }}" alt="" class="attendance-list__arrow-icon attendance-list__arrow-icon--reverse">
        </a>
    </div>

    <table class="attendance-table">
        <colgroup>
            <col class="attendance-table__col-name">
            <col class="attendance-table__col-time">
            <col class="attendance-table__col-time">
            <col class="attendance-table__col-time">
            <col class="attendance-table__col-time">
            <col class="attendance-table__col-detail">
        </colgroup>
        <thead>
            <tr class="attendance-table__row">
                <th class="attendance-table__header">名前</th>
                <th class="attendance-table__header">出勤</th>
                <th class="attendance-table__header">退勤</th>
                <th class="attendance-table__header">休憩</th>
                <th class="attendance-table__header">合計</th>
                <th class="attendance-table__header">詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                @php $attendance = $user->attendances->first(); @endphp
                <tr class="attendance-table__row">
                    <td class="attendance-table__item">{{ $user->name }}</td>
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
                            <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}"
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