{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', '申請一覧画面（管理者）')

{{-- Header --}}
@section('header-logo-link', route('admin.attendance.list'))

{{-- Header-Nav --}}
@section('header-nav')
@include('components.nav.admin')
@endsection

{{-- CSS --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/request-list.css') }}">
@endsection

{{-- Content --}}
@section('content')
<div class="request-list">
    <h1 class="request-list__page-title page-title">申請一覧</h1>

    {{-- タブ切り替え部分 --}}
    <div class="tab-menu">
        <a
            href="{{ route('admin.attendance.corrections.index', ['tab' => 'pending']) }}" class="tab-menu__link {{ $tab === 'pending' ? 'tab-menu__link--active' : '' }}"
        >
            承認待ち
        </a>
        <a
            href="{{ route('admin.attendance.corrections.index', ['tab' => 'approved']) }}" class="tab-menu__link {{ $tab === 'approved' ? 'tab-menu__link--active' : '' }}"
        >
            承認済み
        </a>
    </div>

    {{-- 申請一覧 --}}
    <table class="request-table">
        <thead>
            <tr class="request-table__row">
                <th class="request-table__header">状態</th>
                <th class="request-table__header">名前</th>
                <th class="request-table__header">対象日時</th>
                <th class="request-table__header">申請理由</th>
                <th class="request-table__header">申請日時</th>
                <th class="request-table__header">詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($corrections as $correction)
                <tr class="request-table__row">
                    <td class="request-table__item">
                        {{ $correction->statusLabel }}
                    </td>
                    <td class="request-table__item">
                        {{ $correction->attendance?->user?->name }}
                    </td>
                    <td class="request-table__item request-table__item--date">
                        {{ $correction->attendance?->work_date?->format('Y/m/d') }}
                    </td>
                    <td class="request-table__item request-table__item--remarks">
                        {{ $correction->request_remarks }}
                    </td>
                    <td class="request-table__item request-table__item--date">
                        {{ $correction->created_at?->format('Y/m/d') }}
                    </td>
                    <td class="request-table__item">
                        <a
                            href="{{ route('admin.attendance.correction.detail', [
                                'attendance_correct_request_id' => $correction->id
                            ]) }}"
                            class="request-table__detail-link"
                        >
                            詳細
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection