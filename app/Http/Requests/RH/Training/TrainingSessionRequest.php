<?php

namespace App\Http\Requests\RH\Training;

use App\Http\Requests\BaseFormRequest;

class TrainingSessionRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:training_courses,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string', 'max:255'],
            'instructor' => ['nullable', 'string', 'max:255'],
            'max_participants' => ['integer', 'min:0'],
            'status' => ['string', 'max:30'],
        ];
    }
}
