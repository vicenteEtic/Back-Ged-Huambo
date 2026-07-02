<?php

namespace App\Services\RH\Training;

use App\Repositories\RH\Training\TrainingCourseRepository;
use App\Services\AbstractService;

class TrainingCourseService extends AbstractService
{
    public function __construct(TrainingCourseRepository $repository) { parent::__construct($repository); }
}
