<?php

namespace App\Http\Requests\RH\Training;

use App\Http\Requests\BaseFormRequest;

class TrainingCourseRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('training_course');
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', "unique:training_courses,code,{$id},id"],
            'description' => ['nullable', 'string'],
            'duration_hours' => ['integer', 'min:0'],
            'provider' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
