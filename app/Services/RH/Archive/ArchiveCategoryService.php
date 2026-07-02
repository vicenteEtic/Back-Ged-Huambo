<?php

namespace App\Services\RH\Archive;

use App\Repositories\RH\Archive\ArchiveCategoryRepository;
use App\Services\AbstractService;

class ArchiveCategoryService extends AbstractService
{
    public function __construct(ArchiveCategoryRepository $repository)
    {
        parent::__construct($repository);
    }
}
