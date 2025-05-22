@extends('layouts.app')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/main-content.css')}}">
<link rel="stylesheet" href="{{ asset('css/common/date-selector.css')}}">
<link rel="stylesheet" href="{{ asset('css/common/attendance-index-table.css')}}">
<link rel="stylesheet" href="{{ asset('css/admin/attendance/index.css')}}">
@endsection

@section('content')
<div class="main-content">
  <h1 class="main-content__header">勤怠一覧</h1>
  <!-- 日にちの選択 -->
  <div class="date-selector">
    <a class="date-selector__link" href="{{ route('admin.attendance.list', ['year' => $prevDay->year, 'month' => $prevDay->month, 'day' => $prevDay->day]) }}">
      <img class="date-selector__image--arrow" src="/images/arrow-back.png" />前日
    </a>
    <div class="date-selector__group">
      <img class="date-selector__image--calendar" src="/images/calendar.png" />
      <h2 class="date-selector__header">{{ $currentDate->format('Y/m/d') }}</h2>
    </div>
    <a class="date-selector__link" href="{{ route('admin.attendance.list', ['year' => $nextDay->year, 'month' => $nextDay->month, 'day' => $nextDay->day]) }}">
      翌日<img class="date-selector__image--arrow" src="/images/arrow-forward.png" />
    </a>
  </div>
  <!-- 勤怠データの表示 -->
  <table class="table">
    <thead>
      <tr>
        <th>名前</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
      </tr>
    </thead>
    <tbody>
      @foreach($attendances as $attendance)
      <tr>
        <td>{{ $attendance ? $attendance->user->name : '' }}</td>
        <td>{{ $attendance ? $attendance->formatted_clock_in : '' }}</td>
        <td>{{ $attendance ? $attendance->formatted_clock_out : '' }}</td>
        <td>{{ $attendance ? $attendance->formatted_total_break : '' }}</td>
        <td>{{ $attendance ? $attendance->formatted_total_work : '' }}</td>
        <td>
          @if ($attendance)
          <a class="detail" href="{{ route('attendance.show', ['attendance_id' => $attendance->id]) }}">詳細</a>
          @else
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection('content')