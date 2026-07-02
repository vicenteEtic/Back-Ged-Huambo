<?php

namespace App\Repositories\RH\Training;

use App\Models\RH\Training\TrainingCourse;
use App\Repositories\AbstractRepository;

class TrainingCourseRepository extends AbstractRepository
{
    public function __construct(TrainingCourse $model) { parent::__construct($model); }
}
