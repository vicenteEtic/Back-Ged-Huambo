<?php

namespace App\Services\RH\Training;

use App\Repositories\RH\Training\TrainingSessionRepository;
use App\Services\AbstractService;

class TrainingSessionService extends AbstractService
{
    public function __construct(TrainingSessionRepository $repository) { parent::__construct($repository); }
}
