<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    public function rules()
    {
        return [
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'breaks' => ['nullable', 'array'],
            'note' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'start_time.required' => '出勤時間は必須です。',
            'end_time.required' => '退勤時間は必須です。',
            'end_time.after' => '出勤時間もしくは退勤時間が不適切な値です。',
            'note.required' => '備考を記入してください。',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $breaks = $this->input('breaks', []);
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            // 休憩バリデーション
            if ($breaks && $startTime && $endTime) {
                foreach ($breaks as $index => $break) {
                    $start = $break['start'] ?? null;
                    $end = $break['end'] ?? null;

                    // どちらか一方のみ入力された場合はエラー
                    if (!empty($start) xor !empty($end)) {
                        $validator->errors()->add("breaks.$index.start", '開始時間と終了時間の両方を入力してください。');
                        continue;
                    }

                    if (!empty($break['start'])) {
                        if ($break['start'] < $startTime) {
                            $validator->errors()->add("breaks.$index.start", '休憩時間が勤務時間外です。');
                        }
                        if ($break['start'] > $endTime) {
                            $validator->errors()->add("breaks.$index.start", '休憩時間が勤務時間外です。');
                        }
                    }

                    if (!empty($break['end'])) {
                        if ($break['end'] < $startTime) {
                            $validator->errors()->add("breaks.$index.end", '休憩時間が勤務時間外です。');
                        }
                        if ($break['end'] > $endTime) {
                            $validator->errors()->add("breaks.$index.end", '休憩時間が勤務時間外です。');
                        }
                    }
                }
            }
        });
    }

}
