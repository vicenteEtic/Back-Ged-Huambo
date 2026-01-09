<?php

namespace App\Models\Transation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\User;

class transaionControl extends Model
{
    use HasFactory;
    protected $table = 'transaion_control';
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'total'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
