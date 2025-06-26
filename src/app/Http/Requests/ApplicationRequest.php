<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    public function rules()
    {
        return [
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after_or_equal:start_time'],
            'breaks' => ['nullable', 'array'],
            'note' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'start_time.required' => '出勤時間は必須です。',
            'end_time.required' => '退勤時間は必須です。',
            'end_time.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です。',
            'note.required' => '備考を記入してください。',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $breaks = $this->input('breaks', []);
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            // 出勤が退勤より後の場合
            if ($startTime && $endTime && $startTime > $endTime) {
                $validator->errors()->add('end_time', '出勤時間もしくは退勤時間が不適切な値です。');
            }

            // 休憩バリデーション
            if ($breaks && $endTime) {
                foreach ($breaks as $index => $break) {
                    if (!empty($break['start']) && $break['start'] > $endTime) {
                        $validator->errors()->add("breaks.$index.start", '休憩時間が勤務時間外です。');
                    }
                    elseif (!empty($break['end']) && $break['end'] > $endTime) {
                        $validator->errors()->add("breaks.$index.end", '休憩時間が勤務時間外です。');
                    }
                }
            }
        });
    }

}
