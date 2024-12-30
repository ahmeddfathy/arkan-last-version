<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalarySheetUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add your authorization logic here
    }

    public function rules(): array
    {
        return [
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:pdf,xlsx,xls,csv|max:10240'
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Please select files to upload',
            'files.*.mimes' => 'Only PDF, Excel, and CSV files are allowed',
            'files.*.max' => 'File size should not exceed 10MB'
        ];
    }
}