<?php

namespace App\Models\Alert\AlertUser;

use App\Models\Alert\Alert;
use App\Models\Entities\Entities;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertUser extends Model
{
    use HasFactory , SoftDeletes;
    protected $table = 'alert_user';
    protected $primaryKey = 'id';
    protected $fillable = ['alert_id', 'user_id', 'is_read'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function alert()
    {
        return $this->belongsTo(Alert::class, 'alert_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'alert_user', 'alert_id', 'user_id')
            ->withTimestamps()
            ->withPivot('is_read', 'created_at');
    }
    public function alerts()
{
    return $this->belongsToMany(\App\Models\Alert\Alert::class, 'alert_user', 'user_id', 'alert_id')
        ->withTimestamps()
        ->withPivot('is_read', 'created_at');
}

 public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }

}