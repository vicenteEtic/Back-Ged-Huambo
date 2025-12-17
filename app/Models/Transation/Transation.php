<?php

namespace App\Models\Transation;

use App\Models\AmlAlert\AmlAlert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transation extends Model
{
    use HasFactory;
    protected $table = 'transation';
    protected $primaryKey = 'id';
   protected $fillable = [
        'transaction_uid','transaction_date','transaction_type',
        'amount','currency','payment_channel',
        'origin_account','destination_account',
        'status','client_id','policy_number',
        'product_code','beneficiary_id','risk_score'
    ];

    public function alerts()
    {
        return $this->hasMany(AmlAlert::class);
    }
        
}