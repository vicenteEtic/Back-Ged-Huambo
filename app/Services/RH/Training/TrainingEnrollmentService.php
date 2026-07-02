<?php

namespace App\Services\RH\Training;

use App\Repositories\RH\Training\TrainingEnrollmentRepository;
use App\Services\AbstractService;

class TrainingEnrollmentService extends AbstractService
{
    public function __construct(TrainingEnrollmentRepository $repository) { parent::__construct($repository); }
}
