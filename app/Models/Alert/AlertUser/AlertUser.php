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
    use HasFactory, SoftDeletes;

    protected $table = 'alert_user';
    protected $primaryKey = 'id';
    protected $fillable = ['alert_id', 'user_id', 'is_read', 'entity_id'];

    // Relacionamento com o usuário
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relacionamento com o alert
    public function alert()
    {
        return $this->belongsTo(Alert::class, 'alert_id');
    }

    // Relacionamento com a entidade (se existir)
    public function entity()
    {
        return $this->belongsTo(Entities::class, 'entity_id');
    }

    // Removidos os métodos users() e alerts() que causavam conflito
}