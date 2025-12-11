<?php

namespace App\Models\Alert;

use App\Models\Entities\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Alert extends Model
{
    use HasFactory;
    protected $table = 'alert';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'level',
        'origin_id',
        'entity_id',
        'score',
        'type',
        'list',
        'is_active',
        'country'
        ,'birth_date'
    ];


    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }
}
