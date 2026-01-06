<?php
namespace App\Http\Requests\AlertAttachment;

use App\Http\Requests\BaseFormRequest;

class AlertAttachmentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachments'   => 'required|array|min:1',
            'attachments.*' => 'required|file|max:5120', // max 5MB por arquivo
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.required'   => 'É necessário enviar pelo menos um anexo.',
            'attachments.array'      => 'Os anexos devem ser enviados em formato de lista.',
            'attachments.*.required' => 'Cada anexo é obrigatório.',
            'attachments.*.string'   => 'Cada anexo deve estar em Base64.',
        ];
    }
}
