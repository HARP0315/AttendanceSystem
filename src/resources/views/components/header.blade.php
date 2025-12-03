<header class="header">
    <div class="header__logo">
        <a href="/"><img src="{{ asset('img/logo.png') }}" alt="ロゴ"></a>
    </div>

    @php
        $user = Auth::user();
    @endphp

    @if ($user && $user->role === 0)
        <nav class="header__nav">
            <ul>
                <li><a href="/admin/attendance/list">勤怠一覧</a></li>
                <li><a href="/admin/staff/list">スタッフ一覧</a></li>
                <li><a href="/admin/stamp_correction_request/list">申請一覧</a></li>
                <li>
                    <form action="/logout" method="post">
                        @csrf
                        <button class="header__logout">ログアウト</button>
                    </form>
                </li>
            </ul>
        </nav>
    @elseif ($user && $user->role === 1)
        <nav class="header__nav">
            <ul>
                @if (isset($headerLink))
                    <li><a href="/attendance/list">今月の出勤一覧</a></li>
                    <li><a href="/stamp_correction_request/list">申請一覧</a></li>
                @else
                    <li><a href="/attendance">勤怠</a></li>
                    <li><a href="/attendance/list">勤怠一覧</a></li>
                    <li><a href="/stamp_correction_request/list">申請</a></li>
                @endif
                <li>
                    <form action="/logout" method="post">
                        @csrf
                        <button class="header__logout">ログアウト</button>
                    </form>
                </li>
            </ul>
        </nav>
    @endif
</header>
