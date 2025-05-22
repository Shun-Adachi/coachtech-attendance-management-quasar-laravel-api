@section('link')

@if($user->role_id === config('constants.ROLE_ADMIN'))
<a class="header-link__link" href="/admin/attendance/list">勤怠一覧</a>
<a class="header-link__link" href="/admin/staff/list">スタッフ一覧</a>
<a class="header-link__link" href="/stamp_correction_request/list">申請一覧</a>
@else
<a class="header-link__link" href="/attendance">勤怠</a>
<a class="header-link__link" href="/attendance/list">勤怠一覧</a>
<a class="header-link__link" href="/stamp_correction_request/list">申請</a>
@endif
<a class="header-link__link" href="/logout">ログアウト</a>
@endsection