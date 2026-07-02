<?php

namespace App\Repositories\RH\Training;

use App\Models\RH\Training\TrainingEnrollment;
use App\Repositories\AbstractRepository;

class TrainingEnrollmentRepository extends AbstractRepository
{
    public function __construct(TrainingEnrollment $model) { parent::__construct($model); }
}
