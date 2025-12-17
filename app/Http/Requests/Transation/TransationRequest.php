<?php

namespace App\Http\Requests\Transation;

use App\Http\Requests\BaseFormRequest;

class TransationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

public function rules(): array
{
    return [
        '*.transaction_uid'     => 'required|string|max:64',
        '*.transaction_date'    => 'required|date',
        '*.transaction_type'    => 'required|string',
        '*.amount'              => 'required|numeric|min:0.01',
        '*.currency'            => 'required|string',
        '*.payment_channel'     => 'required|string',
        '*.status'              => 'required|string',
        '*.client_id'           => 'required|string',
        '*.policy_number'       => 'required|string',
        '*.product_code'        => 'required|string',
        '*.beneficiary_id'      => 'nullable|string',
        '*.origin_account'      => 'nullable|string',
        '*.destination_account' => 'nullable|string',
        '*.risk_score'          => 'nullable|numeric|min:0|max:100',
        '*.description'         => 'nullable|string|max:255',
        '*.category'            => 'nullable|string|max:100',
        '*.ip_address'          => 'nullable|ip',
        '*.device'              => 'nullable|string|max:100',
        '*.notes'               => 'nullable|string|max:500',
    ];
}




    /**
     * Mensagens personalizadas (opcional, mas recomendado)
     */
  public function messages(): array
{
    return [
        '*.transaction_uid.required'     => 'O ID único da transação é obrigatório.',
        '*.transaction_type.required'    => 'O tipo da transação é obrigatório.',
        '*.payment_channel.required'     => 'O canal de pagamento é obrigatório.',
        '*.status.required'              => 'O estado da transação é obrigatório.',
        '*.client_id.required'           => 'O cliente (client_id) é obrigatório.',
        '*.policy_number.required'       => 'A apólice é obrigatória.',
        '*.product_code.required'        => 'O produto é obrigatório.',
    ];
}


    /**
     * Normaliza dados antes de validar (opcional mas recomendado)
     */
    protected function prepareForValidation()
    {
        if (is_array($this->all())) {
            $this->merge(
                collect($this->all())->map(function ($tx) {
                    return array_merge($tx, [
                        'currency' => strtoupper($tx['currency'] ?? 'AOA'),
                        'status' => strtolower($tx['status'] ?? 'pending'),
                    ]);
                })->toArray()
            );
        }
    }
}
