<?php

namespace App\Repositories\RH\Training;

use App\Models\RH\Training\TrainingSession;
use App\Repositories\AbstractRepository;

class TrainingSessionRepository extends AbstractRepository
{
    public function __construct(TrainingSession $model) { parent::__construct($model); }
}
