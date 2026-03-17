<nav class="header__nav">
    <ul class="header__nav-list">
        <li class="header__nav-item">
            <a href="/attendance" class="header__link">勤怠</a>
        </li>
        <li class="header__nav-item">
            <a href="/attendance/list" class="header__link">勤怠一覧</a>
        </li>
        <li class="header__nav-item">
            <a href="/stamp_correction_request/list" class="header__link">申請</a>
        </li>
        <li class="header__nav-item">
            <form action="/logout" method="post" class="logout__form">
                @csrf
                <button type="submit" class="logout__btn">ログアウト</button>
            </form>
        </li>
    </ul>
</nav>