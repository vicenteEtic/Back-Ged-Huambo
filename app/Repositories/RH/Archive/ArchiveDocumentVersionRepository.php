<?php

namespace App\Repositories\RH\Archive;

use App\Models\RH\Archive\ArchiveDocumentVersion;
use App\Repositories\AbstractRepository;

class ArchiveDocumentVersionRepository extends AbstractRepository
{
    public function __construct(ArchiveDocumentVersion $model)
    {
        parent::__construct($model);
    }
}
