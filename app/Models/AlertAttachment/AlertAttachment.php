<?php

namespace App\Models\AlertAttachment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AlertAttachment extends Model
{
    use HasFactory;
    protected $table = 'alert_attachment';
    protected $primaryKey = 'id';
    protected $fillable = ['alert_id', 'name', 'file'];
}