<?php

namespace App\Http\Requests\OverTimeRequest;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'overtime_date' => ['required', 'date', 'after:today'],
            'reason' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'user_id' => ['sometimes', 'exists:users,id'],
        ];
    }

    public function messages()
    {
        return [
            'overtime_date.after' => 'Overtime date must be a future date.',
            'end_time.after' => 'End time must be after start time.',
        ];
    }
}
