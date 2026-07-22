<?php

namespace App\Http\Requests\RH\Performance;

use App\Http\Requests\BaseFormRequest;

class PerformanceCycleRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:performance_cycles,code,{$id},id"],
            'start_date' => [$this->requiredOnCreate(), 'date'],
            'end_date' => [$this->requiredOnCreate(), 'date', 'after_or_equal:start_date'],
            'status' => ['string', 'max:30'],
        ];
    }
}
