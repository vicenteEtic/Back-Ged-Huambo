<?php

namespace App\Models\RH\Training;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'training_sessions';

    protected $fillable = [
        'course_id', 'name', 'description', 'start_date',
        'end_date', 'location', 'instructor', 'max_participants', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function trainingCourse()
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class, 'session_id');
    }

    public function trainingEnrollments()
    {
        return $this->hasMany(TrainingEnrollment::class, 'session_id');
    }
}
