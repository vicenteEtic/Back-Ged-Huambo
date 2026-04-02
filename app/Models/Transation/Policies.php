<?php

namespace App\Models\Transation;

use App\Models\Entities\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Policies extends Model
{
    use HasFactory;

    protected $table = 'policies_staging';

    protected $primaryKey = 'id';

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'next_renewal_date' => 'datetime',
        'next_expiry_date' => 'datetime',
    ];

    // Relacionamento com entities
    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }

    protected $fillable = [
        
        'contract_number',               // Numero_Apolice
        'branch_code',                   // Codigo_Ramo
        'branch_desc',                   // Descricao_Ramo
        'product_code',                  // Codigo_Produto
        'product_desc',                  // Descricao_Produto
        'channel_code',                  // Codigo_Canal
        'channel_desc',                  // Descricao_Canal
        'agent_code',                    // Codigo_Agente
        'agent_desc',                    // Descricao_Agente
        'status',                        // Estado_Apolice
        'start_date',                    // Data_Inicio
        'end_date',                      // Data_Fim
        'next_renewal_date',             // Data_Proxima_Renovacao
        'next_expiry_date',              // Data_Proximo_Vencimento
        'currency',                      // Moeda
        'capital',                       // Capital
        'capital_cosign',                // Capital_Liquido_Cosseguro
        'premium_simple',                // Premio_Simples
        'premium_total',                 // Premio_Total
        'charges',                       // Encargos
        'other_charges',                 // Outros_Encargos
        'interest',                      // Juros
        
    ];
}