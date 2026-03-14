{{-- Layout --}}
@extends('layouts.app')

{{-- Title --}}
@section('title', 'メール認証誘導画面（一般ユーザー）')

{{-- Header --}}
@section('header-logo-link', url('/login'))

{{-- CSS --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

{{-- Content --}}
@section('content')
<div class="verification-content">
    <span class="verification-content__message">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </span>

    <a href="{{ route('verification.mailhog') }}" class="verification-content__link">
        認証はこちらから
    </a>

    <form action="{{ route('verification.send') }}" method="post" class="verification-form">
        @csrf
        <button type="submit" class="verification-form__notification-btn">認証メールを再送する</button>
    </form>

</div>
@endsection