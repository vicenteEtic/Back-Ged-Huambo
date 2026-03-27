<?php

namespace App\Models\Alert;

use App\Models\Entities\Entities;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Alert extends Model
{
    use HasFactory;
    protected $table = 'alert';
    protected $primaryKey = 'id';
    protected $fillable = [
     'description',
        'name',
        'level',
        'origin_id',
        'entity_id',
        'from_id',
        'score',
        'type',
        'list',
        'is_active',
        'country',
        'birth_date',
        'is_pep',
        'is_sanctioned',
        'is_reported',
        'category',
        'assigned_to'

     
    ];


    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function users()
{
    return $this->belongsToMany(User::class, 'alert_user')
                ->withPivot('is_read')
                ->withTimestamps();
}
}
