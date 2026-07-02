<?php

namespace App\Repositories\RH\Archive;

use App\Models\RH\Archive\ArchiveDocumentShare;
use App\Repositories\AbstractRepository;

class ArchiveDocumentShareRepository extends AbstractRepository
{
    public function __construct(ArchiveDocumentShare $model)
    {
        parent::__construct($model);
    }
}
