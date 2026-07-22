<?php

namespace App\Models\RH\Training;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingCourse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'training_courses';

    protected $fillable = [
        'name', 'code', 'description', 'duration_hours',
        'provider', 'category', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function sessions()
    {
        return $this->hasMany(TrainingSession::class, 'course_id');
    }

    public function trainingSessions()
    {
        return $this->hasMany(TrainingSession::class, 'course_id');
    }
}
