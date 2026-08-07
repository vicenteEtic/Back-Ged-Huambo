<?php

namespace App\Repositories\RH\Declaration;

use App\Models\RH\Declaration\DeclarationRequest;
use App\Repositories\AbstractRepository;

class DeclarationRequestRepository extends AbstractRepository
{
    public function __construct(DeclarationRequest $model)
    {
        parent::__construct($model);
    }
}
