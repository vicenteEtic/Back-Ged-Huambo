<?php

namespace App\Repositories\RH\Archive;

use App\Models\RH\Archive\ArchiveCategory;
use App\Repositories\AbstractRepository;

class ArchiveCategoryRepository extends AbstractRepository
{
    public function __construct(ArchiveCategory $model)
    {
        parent::__construct($model);
    }
}
