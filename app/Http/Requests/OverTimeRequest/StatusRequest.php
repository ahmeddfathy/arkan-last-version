<?php

namespace App\Http\Requests\OverTimeRequest;

use Illuminate\Foundation\Http\FormRequest;

class StatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:255'],
        ];
    }
}
