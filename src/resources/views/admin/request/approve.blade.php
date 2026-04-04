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
<div class="attendance-detail">
    <h1 class="attendance-detail__page-title page-title">勤怠詳細</h1>

    <form
        action="{{ route('admin.attendance.correction.approve', [
            'attendance_correct_request_id' => $correction->id]) }}"
        method="post"
        class="correction-form correction-form--approve"
    >
        @csrf
        @method('put')

        {{-- 名前 --}}
        <dl class="correction-form__group">
            <dt class="correction-form__label">名前</dt>
            <dd class="correction-form__item">
                <span class="correction-form__text">
                    {{ $correction->attendance?->user?->name }}
                </span>
            </dd>
        </dl>

        {{-- 日付 --}}
        <dl class="correction-form__group">
            <dt class="correction-form__label">日付</dt>
            <dd class="correction-form__item">
                <div class="correction-form__text-group">
                    <span class="correction-form__text">
                        {{ $correction->attendance?->work_date->format('Y') }}年
                    </span>
                </div>
                <span class="correction-form__separator"></span>
                <div class="correction-form__text-group">
                    <span class="correction-form__text">
                        {{ $correction->attendance?->work_date->format('n月j日') }}
                    </span>
                </div>
            </dd>
        </dl>

        {{-- 出勤・退勤 --}}
        <dl class="correction-form__group">
            <dt class="correction-form__label">出勤・退勤</dt>
            <dd class="correction-form__item">
                @if ($correction->status === 'approved')
                    <div class="correction-form__text-group">
                        <span class="correction-form__text">
                            {{ $correction->requested_clock_in?->format('H:i') }}
                        </span>
                    </div>
                    <span class="correction-form__separator">〜</span>
                    <div class="correction-form__text-group">
                        <span class="correction-form__text">
                            {{ $correction->requested_clock_out?->format('H:i') }}
                        </span>
                    </div>
                @else
                    <div class="correction-form__text-group">
                        <span class="correction-form__text">
                            {{ $correction->requested_clock_in?->format('H:i') }}
                        </span>
                        <input
                            type="hidden"
                            name="requested_clock_in"
                            value="{{ $correction->requested_clock_in?->format('H:i') }}"
                        >
                    </div>
                    <span class="correction-form__separator">〜</span>
                    <div class="correction-form__text-group">
                        <span class="correction-form__text">
                            {{ $correction->requested_clock_out?->format('H:i') }}
                        </span>
                        <input
                            type="hidden"
                            name="requested_clock_out"
                            value="{{ $correction->requested_clock_out?->format('H:i') }}"
                        >
                    </div>
                @endif
            </dd>
        </dl>

        {{-- 休憩 --}}
        @if ($correction->status === 'approved')
            @foreach ($correction->attendanceCorrectionRequestBreaks as $break)
                <dl class="correction-form__group">
                    <dt class="correction-form__label">
                        休憩{{ $break->sort_order > 1 ? $break->sort_order : '' }}
                    </dt>
                    <dd class="correction-form__item">
                        <div class="correction-form__text-group">
                            <span class="correction-form__text">
                                {{ $break->requested_break_start?->format('H:i') }}
                            </span>
                        </div>
                        <span class="correction-form__separator">〜</span>
                        <div class="correction-form__text-group">
                            <span class="correction-form__text">
                                {{ $break->requested_break_end?->format('H:i') }}
                            </span>
                        </div>
                    </dd>
                </dl>
            @endforeach
            <dl class="correction-form__group">
                <dt class="correction-form__label">
                    休憩{{ $correction->attendanceCorrectionRequestBreaks->isEmpty()
                        ? ''
                        : $correction->attendanceCorrectionRequestBreaks->count() + 1 }}
                </dt>
                <dd class="correction-form__item"></dd>
            </dl>
        @else
            @foreach ($correction->attendanceCorrectionRequestBreaks as $index => $break)
                <dl class="correction-form__group">
                    <dt class="correction-form__label">
                        休憩{{ $break->sort_order > 1 ? $break->sort_order : '' }}
                    </dt>
                    <dd class="correction-form__item">
                        <input type="hidden" name="attendance_break_id[{{ $index }}]" value="{{ $break->attendance_break_id }}">
                        <div class="correction-form__text-group">
                            <span class="correction-form__text">
                                {{ $break->requested_break_start?->format('H:i') }}
                            </span>
                            <input
                                type="hidden"
                                name="requested_break_start[{{ $index }}]"
                                value="{{ $break->requested_break_start?->format('H:i') }}"
                            >
                        </div>
                        <span class="correction-form__separator">〜</span>
                        <div class="correction-form__text-group">
                            <span class="correction-form__text">
                                {{ $break->requested_break_end?->format('H:i') }}
                            </span>
                            <input
                                type="hidden"
                                name="requested_break_end[{{ $index }}]"
                                value="{{ $break->requested_break_end?->format('H:i') }}"
                            >
                        </div>
                    </dd>
                </dl>
            @endforeach
            <dl class="correction-form__group">
                <dt class="correction-form__label">
                    休憩{{ $correction->attendanceCorrectionRequestBreaks->isEmpty()
                        ? ''
                        : $correction->attendanceCorrectionRequestBreaks->count() + 1 }}
                </dt>
                <dd class="correction-form__item"></dd>
            </dl>
        @endif

        {{-- 備考 --}}
        <dl class="correction-form__group">
            <dt class="correction-form__label">備考</dt>
            <dd class="correction-form__item correction-form__item--textarea">
                @if ($correction->status === 'approved')
                    <div class="correction-form__text-group">
                        <span class="correction-form__text correction-form__text--textarea">
                            {{ $correction->request_remarks }}
                        </span>
                    </div>
                @else
                    <div class="correction-form__text-group">
                        <span class="correction-form__text correction-form__text--textarea">
                            {{ $correction->request_remarks }}
                        </span>
                        <input
                            type="hidden"
                            name="request_remarks"
                            value="{{ $correction->request_remarks }}"
                        >
                    </div>
                @endif
            </dd>
        </dl>

        {{-- 承認 --}}
        @if ($correction->status === 'approved')
            <span class="correction-form__approved-message">
                承認済み
            </span>
        @else
            <button type="submit" class="correction-form__btn btn">承認</button>
        @endif
    </form>
</div>
@endsection