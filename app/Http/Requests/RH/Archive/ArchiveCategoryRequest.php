<?php

namespace App\Http\Requests\RH\Archive;

use App\Http\Requests\BaseFormRequest;

class ArchiveCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'parent_id' => ['nullable', 'exists:archive_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', "unique:archive_categories,code,{$id},id"],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:processo_individual,administrativo,relatorio,avaliacao,despacho'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
