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
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

{{-- Content --}}
@section('content')
<div class="attendance-detail">
    <h1 class="attendance-detail__page-title page-title">勤怠詳細</h1>

    <form
        action="{{ route('attendance.corrections.store', $attendance->id) }}"
        method="post"
        class="correction-form"
    >
        @csrf
        {{-- 名前 --}}
        <dl class="correction-form__group">
            <dt class="correction-form__label">名前</dt>
            <dd class="correction-form__item">
                <span class="correction-form__text">
                    {{ $user->name }}
                </span>
            </dd>
        </dl>

        {{-- 日付 --}}
        <dl class="correction-form__group">
            <dt class="correction-form__label">日付</dt>
            <dd class="correction-form__item">
                <div class="correction-form__text-group">
                    <span class="correction-form__text">
                        {{ $attendance->work_date->format('Y') }}年
                    </span>
                </div>
                <span class="correction-form__separator"></span>
                <div class="correction-form__text-group">
                    <span class="correction-form__text">
                        {{ $attendance->work_date->format('n月j日') }}
                    </span>
                </div>
            </dd>
        </dl>

        {{-- 出勤・退勤 --}}
        <dl class="correction-form__group">
            <dt class="correction-form__label">出勤・退勤</dt>
            <dd class="correction-form__item">
                @if ($isPending)
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
                    <div class="correction-form__input-group">
                        <input
                            type="time"
                            name="requested_clock_in"
                            value="{{ old('requested_clock_in', $attendance->clock_in?->format('H:i')) }}"
                            class="correction-form__input"
                        >
                        @error('requested_clock_in')
                            <p class="correction-form__error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <span class="correction-form__separator">〜</span>
                    <div class="correction-form__input-group">
                        <input
                            type="time"
                            name="requested_clock_out"
                            value="{{ old('requested_clock_out', $attendance->clock_out?->format('H:i')) }}"
                            class="correction-form__input"
                        >
                        @error('requested_clock_out')
                            <p class="correction-form__error-message">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </dd>
        </dl>

        {{-- 休憩 --}}
        @if ($isPending)
            @if ($correction->attendanceCorrectionRequestBreaks->isNotEmpty())
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
            @else
                <dl class="correction-form__group">
                    <dt class="correction-form__label">休憩</dt>
                    <dd class="correction-form__item">
                        <div class="correction-form__text-group">
                            <span class="correction-form__text"></span>
                        </div>
                        <span class="correction-form__separator">〜</span>
                        <div class="correction-form__text-group">
                            <span class="correction-form__text"></span>
                        </div>
                    </dd>
                </dl>
            @endif
        @else
            @foreach ($attendance->attendanceBreaks as $index => $break)
                <dl class="correction-form__group">
                    <dt class="correction-form__label">
                        休憩{{ $break->sort_order > 1 ? $break->sort_order : '' }}
                    </dt>
                    <dd class="correction-form__item">
                        <input type="hidden" name="attendance_break_id[{{ $index }}]" value="{{ $break->id }}">
                        <div class="correction-form__input-group">
                            <input
                                type="time"
                                name="requested_break_start[{{ $index }}]"
                                value="{{ old('requested_break_start.' . $index, $break->break_start?->format('H:i')) }}"
                                class="correction-form__input"
                            >
                            @error('requested_break_start.' . $index)
                                <p class="correction-form__error-message">{{ $message }}</p>
                            @enderror
                        </div>
                        <span class="correction-form__separator">〜</span>
                        <div class="correction-form__input-group">
                            <input
                                type="time"
                                name="requested_break_end[{{ $index }}]"
                                value="{{ old('requested_break_end.' . $index, $break->break_end?->format('H:i')) }}"
                                class="correction-form__input"
                            >
                            @error('requested_break_end.' . $index)
                                <p class="correction-form__error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </dd>
                </dl>
            @endforeach
            <dl class="correction-form__group">
                <dt class="correction-form__label">
                    休憩{{ $attendance->attendanceBreaks->isEmpty() ? '' : $attendance->attendanceBreaks->count() + 1 }}
                </dt>
                <dd class="correction-form__item">
                    <div class="correction-form__input-group">
                        <input
                            type="time"
                            name="requested_break_start[{{ $attendance->attendanceBreaks->count() }}]"
                            value="{{ old('requested_break_start.' . $attendance->attendanceBreaks->count()) }}"
                            class="correction-form__input"
                        >
                        @error('requested_break_start.' . $attendance->attendanceBreaks->count())
                            <p class="correction-form__error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <span class="correction-form__separator">〜</span>
                    <div class="correction-form__input-group">
                        <input
                            type="time"
                            name="requested_break_end[{{ $attendance->attendanceBreaks->count() }}]"
                            value="{{ old('requested_break_end.' . $attendance->attendanceBreaks->count()) }}"
                            class="correction-form__input"
                        >
                        @error('requested_break_end.' . $attendance->attendanceBreaks->count())
                            <p class="correction-form__error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </dd>
            </dl>
        @endif

        {{-- 備考 --}}
        <dl class="correction-form__group">
            <dt class="correction-form__label">備考</dt>
            <dd class="correction-form__item correction-form__item--textarea">
                @if ($isPending)
                    <div class="correction-form__text-group">
                        <span class="correction-form__text correction-form__text--textarea">
                            {{ $correction->request_remarks }}
                        </span>
                    </div>
                @else
                    <div class="correction-form__input-group">
                        <textarea
                            name="request_remarks"
                            class="correction-form__textarea"
                        >{{ old('request_remarks') }}</textarea>
                        @error('request_remarks')
                            <p class="correction-form__error-message">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </dd>
        </dl>
        @if ($isPending)
            <span class="correction-form__pending-message">
                *承認待ちのため修正はできません。
            </span>
        @else
            <button type="submit" class="correction-form__btn btn">修正</button>
        @endif
    </form>
</div>
@endsection