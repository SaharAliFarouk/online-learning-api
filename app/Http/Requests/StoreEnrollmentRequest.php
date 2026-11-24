<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Course ID
            |--------------------------------------------------------------------------
            |
            | الطالب يرسل فقط الـ course_id.
            | أما student_id فيتم تحديده تلقائيًا من الـ logged-in user
            | داخل EnrollmentController.
            |
            */

            'course_id' => [
                'required',

                /*
                |--------------------------------------------------------------------------
                | Course must exist and be active
                |--------------------------------------------------------------------------
                |
                | الكورس لازم:
                | 1. يكون موجود في جدول courses
                | 2. يكون is_active = 1
                |
                */

                Rule::exists('courses', 'id')
                    ->where('is_active', 1),
            ],
        ];
    }
}