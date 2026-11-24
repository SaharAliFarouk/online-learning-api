<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => [
                'required',
                'exists:courses,id',

                Rule::unique('enrollments')
                    ->where(function ($query) {
                        return $query->where('student_id', $this->student_id);
                    })
                    ->ignore($this->route('id')),
            ],
        ];
    }
}