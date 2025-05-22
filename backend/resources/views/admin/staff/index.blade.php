@extends('layouts.app')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/main-content.css')}}">
<link rel="stylesheet" href="{{ asset('css/admin/staff/index.css')}}">
@endsection

@section('content')
<div class="main-content">
  <h1 class="main-content__header">スタッフ一覧</h1>
  <!-- 勤怠データの表示 -->
  <table class="table">
    <thead>
      <tr>
        <th >名前</th>
        <th >メールアドレス</th>
        <th >月次勤怠</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($staffList as $staff)
      <tr>
        <td class="name">{{ $staff['name'] }}</td>
        <td class="email">{{ $staff['email'] }}</td>
        <td class="link">
          <a class="detail" href="{{ route('admin.attendance.staff', ['user_id' => $staff->id]) }}" >詳細</a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection('content')