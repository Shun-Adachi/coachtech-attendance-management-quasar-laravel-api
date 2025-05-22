@extends('layouts.app')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/register.css')}}">
@endsection

@section('content')
<form class="form" action="/attendance" method="post" novalidate>
  @csrf
  @if($attendance->status_id===config('constants.STATUS_ATTENDANCE'))
  <p class="form__text">勤務外</p>
  @elseif($attendance->status_id===config('constants.STATUS_WORKING'))
  <p class="form__text">勤務中</p>
  @elseif($attendance->status_id===config('constants.STATUS_BREAK'))
  <p class="form__text">休憩中</p>
  @else
  <p class="form__text">退勤済</p>
  @endif
  <input class="form__input--date" type="text" name="current_date" id="currentDate" readonly>
  <input class="form__input--time" type="text" name="current_time" id="currentTime" readonly>
  <div class="form__group">
    @if($attendance->status_id===config('constants.STATUS_ATTENDANCE'))
    <input class="form__button--clock-in" type="submit" value="出勤" formaction="/attendance">
    @elseif($attendance->status_id===config('constants.STATUS_WORKING'))
    <input class="form__button--clock-out" type="submit" value="退勤" formaction="/attendance">
    <input class="form__button--break" type="submit" value="休憩入" formaction="/attendance/break">
    @elseif($attendance->status_id===config('constants.STATUS_BREAK'))
    <input class="form__button--break" type="submit" value="休憩戻" formaction="/attendance/break">
    @else
    <p class="form__text--thanks">お疲れ様でした。<div class=""></div></p>
    @endif
  </div>
</form>

<script>
  // 日本語の曜日を取得する配列
  const weekdays = ["日", "月", "火", "水", "木", "金", "土"];

  // 現在の日付と時間を更新する関数
  function updateDateTime() {
    const now = new Date();

    // 日付をフォーマット: 2023年6月1日(木)
    const year = now.getFullYear();
    const month = now.getMonth() + 1; // 月は0始まり
    const day = now.getDate();
    const weekday = weekdays[now.getDay()];
    const formattedDate = `${year}年${month}月${day}日(${weekday})`;

    // 時間をフォーマット: 08:00
    const hours = String(now.getHours()).padStart(2, '0'); // 2桁で表示
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const formattedTime = `${hours}:${minutes}`;

    // フォームの表示と送信値を更新
    document.getElementById('currentDate').value = formattedDate;
    document.getElementById('currentTime').value = formattedTime;
  }

  // ページロード時とその後毎分更新
  window.onload = function() {
    updateDateTime();
    setInterval(updateDateTime, 1000); // 毎秒更新
  };
</script>

@if(app()->environment('testing'))
<script>
  // テスト環境では、Dateを固定する
  (function() {
    const fixedTime = new Date('2025-02-13T14:30:00+00:00');
    const OriginalDate = Date;
    Date = class extends OriginalDate {
      constructor(...args) {
        if (args.length === 0) {
          super(fixedTime);
        } else {
          super(...args);
        }
      }
      static now() {
      return fixedTime.getTime();
      }
    };
  })();
</script>
@endif

@endsection('content')