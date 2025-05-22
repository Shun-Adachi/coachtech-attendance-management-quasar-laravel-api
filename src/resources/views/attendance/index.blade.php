@extends('layouts.app')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/main-content.css')}}">
<link rel="stylesheet" href="{{ asset('css/common/attendance-index-table.css')}}">
<link rel="stylesheet" href="{{ asset('css/common/date-selector.css')}}">
<link rel="stylesheet" href="{{ asset('css/attendance/index.css')}}">
@endsection

@section('content')
<div class="main-content">
  <h1 class="main-content__header">勤怠一覧</h1>
  <!-- 年月の選択 -->
  <div class="date-selector">
    <a class="date-selector__link" href="{{ route('attendance.list', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}">
      <img class="date-selector__image--arrow" src="/images/arrow-back.png" />前月
    </a>
    <div class="date-selector__group">
      <img class="date-selector__image--calendar" src="/images/calendar.png" />
      <h2 class="date-selector__header">{{ $currentDate->format('Y/m') }}</h2>
    </div>
    <a class="date-selector__link" href="{{ route('attendance.list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}">
      翌月<img class="date-selector__image--arrow" src="/images/arrow-forward.png" />
    </a>
  </div>
  <!-- 勤怠データの表示 -->
  <table class="table">
    <thead>
      <tr>
        <th>日付</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
      </tr>
    </thead>
    <tbody>
      @foreach($daysInMonth as $date => $attendance)
      @php
      $dayOfWeekJP = ['日', '月', '火', '水', '木', '金', '土'][\Carbon\Carbon::parse($date)->dayOfWeek];
      @endphp
      <tr>
        <td>{{ \Carbon\Carbon::parse($date)->format('m/d') }}({{ $dayOfWeekJP }})</td>
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