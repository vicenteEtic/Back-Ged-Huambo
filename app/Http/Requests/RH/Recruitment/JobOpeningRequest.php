<?php

namespace App\Http\Requests\RH\Recruitment;

use App\Http\Requests\BaseFormRequest;

class JobOpeningRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('job_opening');
        return [
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', "unique:job_openings,code,{$id},id"],
            'department_id' => ['required', 'exists:departments,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'vacancies' => ['integer', 'min:1'],
            'status' => ['string', 'max:30'],
            'published_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:published_at'],
        ];
    }
}
