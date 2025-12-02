<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class Verify2faRequest extends BaseFormRequest
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
        'email' => 'required|email',
'code' => 'required|digits:6'


        ];
    }

    public function attributes()
    {
        return [
            'code' => 'código',
            
            
        ];
    }
}
