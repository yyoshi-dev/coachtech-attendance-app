{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', 'スタッフ一覧画面（管理者）')

{{-- Header --}}
@section('header-logo-link', route('admin.attendance.list'))

{{-- Header-Nav --}}
@section('header-nav')
@include('components.nav.admin')
@endsection

{{-- CSS --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/staff.css') }}">
@endsection

{{-- Content --}}
@section('content')
<div class="staff-list">
    <h1 class="staff-list__page-title page-title">スタッフ一覧</h1>

    <table class="staff-table">
        <thead>
            <tr class="staff-table__row">
                <th class="staff-table__header">名前</th>
                <th class="staff-table__header">メールアドレス</th>
                <th class="staff-table__header">月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($staffs as $staff)
                <tr data-testid="staff-row-{{ $staff->id }}" class="staff-table__row">
                    <td class="staff-table__item">{{ $staff->name }}</td>
                    <td class="staff-table__item">{{ $staff->email }}</td>
                    <td class="staff-table__item">
                        <a href="{{ route('admin.attendance.staff.monthly', ['id' => $staff->id]) }}"
                            class="staff-table__detail-link">詳細</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection