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
        $id = $this->route('interview');
        return [
            'application_id' => ['required', 'exists:applications,id'],
            'interviewer_id' => ['nullable', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'type' => ['string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'feedback' => ['nullable', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'status' => ['string', 'max:30'],
        ];
    }
}
