<?php

namespace App\Http\Requests\Process;

use App\Http\Requests\BaseFormRequest;

class ProcessCommentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
            'comment_type' => ['nullable', 'string', 'in:note,opinion,correction_request,approval'],
            'assignment_id' => ['nullable', 'integer', 'exists:process_assignments,id'],
            'attachment_path' => ['nullable', 'string'],
        ];
    }
}
