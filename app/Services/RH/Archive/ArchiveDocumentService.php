<?php

namespace App\Services\RH\Archive;

use App\Repositories\RH\Archive\ArchiveDocumentRepository;
use App\Services\AbstractService;

class ArchiveDocumentService extends AbstractService
{
    public function __construct(ArchiveDocumentRepository $repository)
    {
        parent::__construct($repository);
    }
}
