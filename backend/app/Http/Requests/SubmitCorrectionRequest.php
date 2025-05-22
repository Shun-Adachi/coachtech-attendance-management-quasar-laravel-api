<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class SubmitCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'year' => ['required', 'regex:/^\d{4}年$/'],
            'date' => ['required', 'regex:/^(0?[1-9]|1[0-2])月(0?[1-9]|[12]\d|3[01])日$/'],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],
            'breakTimes.*.break_in'  => ['nullable', 'date_format:H:i'],
            'breakTimes.*.break_out' => ['nullable', 'date_format:H:i'],
            'note' => ['required'],
            'updated_at' => ['required'],
        ];
    }

    /**
     * バリデーション後の追加チェックを行います。
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // リクエストから出勤時間・退勤時間を取得
            $clockInStr = $this->input('clock_in');
            $clockOutStr = $this->input('clock_out');

            try {
                $clockIn = Carbon::createFromFormat('H:i', $clockInStr);
                $clockOut = Carbon::createFromFormat('H:i', $clockOutStr);
            } catch (\Exception $e) {
                // 既に date_format ルールでエラーになるので、ここでは処理不要
                $clockIn = null;
                $clockOut = null;
            }

            //  出勤・退勤時間の整合性チェック
            if ($clockIn && $clockOut && $clockIn->gt($clockOut)) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }
            // 送信された各休憩データのチェック
            $data = $this->input('breakTimes', []);
            foreach ($data as $key => $break) {
                if (isset($break['break_in'], $break['break_out'])) {
                    try {
                        // 各休憩時間を作成（同じ日付を仮定）
                        $breakIn = Carbon::createFromFormat('H:i', $break['break_in']);
                        $breakOut = Carbon::createFromFormat('H:i', $break['break_out']);
                    } catch (\Exception $e) {
                        // date_format ルールでエラーになるので、ここではスキップ
                        continue;
                    }
                    // 休憩開始時間が出勤時間よりも前の場合エラー
                    if ($clockIn && $breakIn->lt($clockIn)) {
                        $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                    }
                    // 休憩開始時間が退勤時間よりも後の場合エラー
                    if ($clockOut && $breakIn->gt($clockOut)) {
                        $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                    }
                    // 休憩終了時間が出勤時間よりも前の場合エラー
                    if ($clockIn && $breakOut->lt($clockIn)) {
                        $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                    }
                    // 休憩終了時間が退勤時間よりも後の場合エラー
                    if ($clockOut && $breakOut->gt($clockOut)) {
                        $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                    }
                    // 休憩開始時間が休憩終了時間よりも前の場合エラー
                    if ($breakIn && $breakOut && $breakOut->lt($breakIn)) {
                        $validator->errors()->add("breakTimes.$key.break_in", '休憩開始時間もしくは休憩終了時間が不適切な値です');
                    }
                }
            }
        });
    }

    /**
     * カスタムエラーメッセージを定義する場合
     *
     * @return array
     */
    public function messages()
    {
        return [
            'year.required' => '年を記入してください',
            'year.regex' => '「20XX年」形式で記入してください',
            'date.required' => '月日を記入してください',
            'date.regex' => '「〇月△日」形式で記入してください',
            'clock_in.required' => '出勤時間を記入してください',
            'clock_in.date_format' => '出勤時間は「HH:MM」形式で入力してください',
            'clock_out.required' => '退勤時間を記入してください',
            'clock_out.date_format' => '退勤時間は「HH:MM」形式で入力してください',
            'breakTimes.*.break_in.required' => '休憩開始時間を記入してください',
            'breakTimes.*.break_in.date_format' => '休憩開始時間は「HH:MM」形式で記入してください',
            'breakTimes.*.break_out.required' => '休憩終了時間を記入してください',
            'breakTimes.*.break_out.date_format' => '休憩終了時間は「HH:MM」形式で記入してください',
            'note.required' => '備考を記入してください',
        ];
    }
}
