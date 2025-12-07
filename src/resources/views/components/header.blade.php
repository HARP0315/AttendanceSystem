<header class="header">

    @php
        $user = Auth::user();
    @endphp

    @if ($user && $user->role === 0)
        <div class="header__logo">
            <a href="/admin/attendance/list"><img src="{{ asset('img/logo.png') }}" alt="ロゴ"></a>
        </div>
        <nav class="header__nav">
            <ul>
                <li><a href="{{ route('admin.attendance.list') }}">勤怠一覧</a></li>
                <li><a href="{{ route('admin.staff.list') }}">スタッフ一覧</a></li>
                <li><a href="{{ route('admin.correction.list') }}">申請一覧</a></li>
                <li>
                    <form action="/logout" method="post">
                        @csrf
                        <button class="header__logout">ログアウト</button>
                    </form>
                </li>
            </ul>
        </nav>
    @elseif ($user && $user->role === 1)
        <div class="header__logo">
            <a href="/attendance"><img src="{{ asset('img/logo.png') }}" alt="ロゴ"></a>
        </div>
        <nav class="header__nav">
            <ul>
                @if (isset($headerLink))
                    <li><a href="{{ route('attendance.monthly') }}">今月の出勤一覧</a></li>
                    <li><a href="{{ route('attendance.corrections.requests') }}">申請一覧</a></li>
                @else
                    <li><a href="{{ route('attendance.view') }}">勤怠</a></li>
                    <li><a href="{{ route('attendance.monthly') }}">勤怠一覧</a></li>
                    <li><a href="{{ route('attendance.corrections.requests') }}">申請</a></li>
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
