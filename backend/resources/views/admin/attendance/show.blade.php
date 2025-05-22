@extends('layouts.app')
@extends('layouts.link')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common/main-content.css')}}">
<link rel="stylesheet" href="{{ asset('css/common/attendance-detail-table.css')}}">
<link rel="stylesheet" href="{{ asset('css/admin/attendance/show.css')}}">
@endsection

@section('content')
<div class="main-content">
  <h1 class="main-content__header">勤怠詳細</h1>
  @error('year')
    <div class="error-message">
    {{ $message }}
    </div>
  @enderror
  @error('date')
    <div class="error-message">
    {{ $message }}
    </div>
  @enderror
  @error('clock_in')
    <div class="error-message">
    {{ $message }}
    </div>
  @enderror
  @error('clock_out')
    <div class="error-message">
    {{ $message }}
    </div>
  @enderror
  @foreach($breakTimes as $index => $break)
    @error("breakTimes.$break->id.break_in")
      <div class="error-message">
      休憩{{$index + 1}}:{{ $message }}
      </div>
    @enderror
    @error("breakTimes.$break->id.break_out")
      <div class="error-message">
      休憩{{$index + 1}}:{{ $message }}
      </div>
    @enderror
  @endforeach
  @error("breakTimes.new.break_in")
    <div class="error-message">
    休憩{{count($breakTimes) + 1 }}:{{ $message }}
    </div>
  @enderror
  @error("breakTimes.new.break_out")
    <div class="error-message">
    休憩{{count($breakTimes) + 1 }}:{{ $message }}
    </div>
  @enderror
  @error('note')
    <div class="error-message">
    {{ $message }}
    </div>
  @enderror
  @error('attendance')
    <div class="error-message">
    {{ $message }}
    </div>
  @enderror
  <!-- 勤怠の詳細表示 -->
  <form class="form" action="/attendance/stamp_correction_request" method="post" novalidate>
    @csrf
    <table class="table">
      <tbody>
        <tr>
          <th>名前</th>
          <td colspan="3" class="name">{{ $attendance->user->name }}</td>
        </tr>
        <tr>
          <th>日付
          <td>
            <input
              class="input--{{!$isPending ? 'inactive' : 'active'}}"
              type="text"
              name="year"
              value="{{ old('year') ?? $attendance->formatted_year }}"
              {{ $isApproved ? 'readonly' : ''}}>
          </td>
          <td></td>
          <td>
            <input
              class="input--{{!$isPending ? 'inactive' : 'active'}}"
              type="text"
              name="date"
              value="{{ old('date') ?? $attendance->formatted_date }}"
              {{ $isApproved ? 'readonly' : ''}}>
          </td>
        </tr>
        <tr>
          <th>出勤・退勤</th>
          <td class="start-time">
            <input
              class="input--{{!$isPending ? 'inactive' : 'active'}}"
              type="text"
              name="clock_in"
              value="{{ old('clock_in') ?? $attendance->formatted_clock_in }}"
              {{ $isApproved ? 'readonly' : ''}}>
          </td>
          <td class="tilde">～</td>
          <td class="end-time">
            <input
              class="input--{{!$isPending ? 'inactive' : 'active'}}"
              type="text"
              name="clock_out"
              value="{{ old('clock_out') ?? $attendance->formatted_clock_out }}"
              {{ $isApproved ? 'readonly' : ''}}>
          </td>
        </tr>
        @foreach($breakTimes as $index => $break)
        <tr>
          <th>休憩{{$index + 1}}</th>
          <td class="start-time">
            <input
              class="input--{{!$isPending ? 'inactive' : 'active'}}"
              type="text"
              name="breakTimes[{{ $break->id }}][break_in]"
              value="{{ old("breakTimes.{$break->id}.break_in", $break->break_in) }}"
              {{ $isApproved ? 'readonly' : ''}}>
          </td>
          <td class="tilde">～</td>
          <td class="end-time">
            <input
              class="input--{{!$isPending ? 'inactive' : 'active'}}"
              type="text"
              name="breakTimes[{{ $break->id }}][break_out]"
              value="{{ old("breakTimes.{$break->id}.break_out", $break->break_out) }}"
              {{ $isApproved ? 'readonly' : ''}}>
          </td>
        </tr>
        @endforeach
        <!-- 空欄の休憩欄を追加 -->
        <tr>
          <th>休憩{{ count($breakTimes) + 1 }}</th>
          <td class="start-time">
            <input
              class="input--{{!$isPending ? 'inactive' : 'active'}}"
              type="text"
              name="breakTimes[new][break_in]"
              value="{{ old('breakTimes.new.break_in') }}"
              {{ $isApproved ? 'readonly' : ''}}>
          </td>
          <td class="tilde">～</td>
          <td class="end-time">
            <input
              class="input--{{!$isPending ? 'inactive' : 'active'}}"
              type="text"
              name="breakTimes[new][break_out]"
              value="{{ old('breakTimes.new.break_out') }}"
              {{ $isApproved ? 'readonly' : ''}}>
          </td>
        </tr>
        <tr>
          <th>備考</th>
          <td colspan="3">
            <textarea
              class="textarea--{{!$isPending ? 'inactive' : 'active'}}"
              type="text"
              name="note"
              {{ !$isPending ? 'readonly' : ''}}>{{ old('note') ?? $attendance->note }}</textarea>
          </td>
        </tr>
      </tbody>
    </table>
    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
    <input type="hidden" name="updated_at" value="{{ $attendance->updated_at }}">
    @if($isPending)
    <input class="button" type="submit" value="修正">
    @elseif($isApproved)
    <p class="text--inactive">*{{$attendance->status->name}}のため修正はできません。</p>
    @else
    <p class="text--inactive">*ユーザー提出前のため修正はできません。</p>
    @endif
  </form>
</div>
@endsection('content')