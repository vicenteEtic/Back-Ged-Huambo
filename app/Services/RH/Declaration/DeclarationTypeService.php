<?php

namespace App\Services\RH\Declaration;

use App\Repositories\RH\Declaration\DeclarationTypeRepository;
use App\Services\AbstractService;

class DeclarationTypeService extends AbstractService
{
    public function __construct(DeclarationTypeRepository $repository)
    {
        parent::__construct($repository);
    }
}
