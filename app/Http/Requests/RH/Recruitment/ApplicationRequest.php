<?php

namespace App\Http\Requests\RH\Recruitment;

use App\Http\Requests\BaseFormRequest;

class ApplicationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('application');
        return [
            'job_opening_id' => ['required', 'exists:job_openings,id'],
            'candidate_id' => ['required', 'exists:candidates,id'],
            'status' => ['string', 'max:30'],
            'cover_letter' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
