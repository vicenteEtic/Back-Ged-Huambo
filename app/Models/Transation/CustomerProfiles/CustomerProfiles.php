<?php

namespace App\Models\Transation\CustomerProfiles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerProfiles extends Model
{
    use HasFactory;
    protected $table = 'customer_profiles';
    protected $primaryKey = 'id';
    protected $fillable = ['entity_id', 'avg_transaction_amount', 'std_transaction_amount', 'avg_transactions_per_month', 'early_redemptions'];
}