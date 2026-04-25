<nav class="header__nav">
    <ul class="header__nav-list">
        <li class="header__nav-item">
            <a href="{{ route('attendance.list') }}" class="header__link">今月の出勤一覧</a>
        </li>
        <li class="header__nav-item">
            <a href="{{ route('attendance.corrections.index') }}" class="header__link">申請一覧</a>
        </li>
        <li class="header__nav-item">
            <form action="/logout" method="post" class="header__logout-form">
                @csrf
                <button type="submit" class="header__logout-button">ログアウト</button>
            </form>
        </li>
    </ul>
</nav>