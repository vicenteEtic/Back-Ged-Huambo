<?php

namespace App\Repositories\RH\Archive;

use App\Models\RH\Archive\ArchiveDocument;
use App\Repositories\AbstractRepository;

class ArchiveDocumentRepository extends AbstractRepository
{
    public function __construct(ArchiveDocument $model)
    {
        parent::__construct($model);
    }
}
