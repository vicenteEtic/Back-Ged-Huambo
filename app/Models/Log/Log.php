<?php

namespace App\Models\Log;

use App\Models\Entities\Entities;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Log extends Model
{
    use HasFactory;
    protected $table = 'log';
    protected $primaryKey = 'id';
    protected $fillable = [
        'level',
        'remote_addr',
        'path_info',
        'user_name',
        'type',
        'user_id',
        'http_user_agent',
        'message',
        'entity_id',
        'alert_id'
    ];

    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
