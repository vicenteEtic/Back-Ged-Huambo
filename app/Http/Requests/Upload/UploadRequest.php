<?php

namespace App\Http\Requests\Upload;

use App\Http\Requests\BaseFormRequest;

class UploadRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? null;

        return [
            'files' => ['required', 'array', 'max:10'],
            'files.*' => [
                'file',
                'max:' . config('upload.max_file_size', 20480),
                'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,txt,csv',
            ],
            'directory' => ['nullable', 'string', 'max:100'],
            'create_zip' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'É obrigatório selecionar pelo menos um ficheiro.',
            'files.array' => 'Os ficheiros devem ser enviados como array.',
            'files.max' => 'É possível carregar no máximo 10 ficheiros por vez.',
            'files.*.file' => 'O campo deve ser um ficheiro válido.',
            'files.*.max' => 'O ficheiro excede o tamanho máximo permitido.',
            'files.*.mimes' => 'O tipo de ficheiro não é permitido. Formatos aceites: PDF, JPG, JPEG, PNG, WebP, GIF, DOC, DOCX, XLS, XLSX, TXT, CSV.',
        ];
    }
}
