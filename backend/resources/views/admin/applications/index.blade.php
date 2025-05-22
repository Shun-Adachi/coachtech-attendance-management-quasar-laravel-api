@extends('layouts.app')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/main-content.css')}}">
<link rel="stylesheet" href="{{ asset('css/common/tab.css')}}">
<link rel="stylesheet" href="{{ asset('css/common/application-index-table.css')}}">
<link rel="stylesheet" href="{{ asset('css/admin/applications/index.css')}}">
@endsection

@section('content')
<div class="main-content">
  <h1 class="main-content__header">申請一覧</h1>
  <!-- タブ -->
  <div class="tab">
    <a class="tab__link--{{ $tab === 'approved' ? 'inactive' : 'active' }}" href="/stamp_correction_request/list">承認待ち</a>
    <a class="tab__link--{{ $tab === 'approved' ? 'active' : 'inactive' }}" href="/stamp_correction_request/list?tab=approved">承認済み</a>
  </div>
  <!-- 勤怠データの表示 -->
  <table class="table">
    <thead>
      <tr>
        <th class="status">状態</th>
        <th class="name">名前</th>
        <th class="date">対象日時</th>
        <th class="note">申請理由</th>
        <th class="date">申請日時</th>
        <th class="detail">詳細</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($attendances as $attendance)
      <tr>
        <td class="status">{{ $attendance->status->name }}</td>
        <td class="name">{{ $attendance->user->name }}</td>
        <td class="date">{{ $attendance->formatted_attendance_at }}</td>
        <td class="note">{{ $attendance->note }}</td>
        <td class="date">{{ $attendance->formatted_requested_at }}</td>
        <td>
          <a class="link" href="/stamp_correction_request/approve/{{ $attendance->id }}">詳細</a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection('content')