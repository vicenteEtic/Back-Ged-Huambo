<?php

namespace App\Http\Requests\RH\Recruitment;

use App\Http\Requests\BaseFormRequest;

class InterviewRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'application_id' => [$this->requiredOnCreate(), 'integer', 'exists:applications,id'],
            'interviewer_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_at' => [$this->requiredOnCreate(), 'date'],
            'type' => ['string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'feedback' => ['nullable', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'status' => ['string', 'max:30'],
        ];
    }
}
