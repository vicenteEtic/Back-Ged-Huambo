<?php

namespace App\Models\RH\Recruitment;

use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOpening extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_openings';

    protected $fillable = [
        'title', 'code', 'department_id', 'position_id',
        'description', 'requirements', 'vacancies',
        'status', 'published_at', 'closes_at',
    ];

    protected $casts = [
        'published_at' => 'date',
        'closes_at' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
