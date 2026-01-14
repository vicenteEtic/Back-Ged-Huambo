<?php

namespace App\Http\Requests\Alert;

use Illuminate\Foundation\Http\FormRequest;

class AlertUpdateStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required'], 
                'is_pep' => ['nullable'], 
                    'is_sanctioned' => ['nullable'], 
                        'is_reported' => ['nullable'], 
                        'comment' => ['nullable'],

        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required' => 'O campo is_active é obrigatório.',

        ];
    }
}
