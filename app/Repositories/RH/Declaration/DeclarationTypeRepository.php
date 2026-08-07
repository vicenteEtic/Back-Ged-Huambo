<?php

namespace App\Repositories\RH\Declaration;

use App\Models\RH\Declaration\DeclarationType;
use App\Repositories\AbstractRepository;

class DeclarationTypeRepository extends AbstractRepository
{
    public function __construct(DeclarationType $model)
    {
        parent::__construct($model);
    }
}
