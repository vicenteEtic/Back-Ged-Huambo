<?php

namespace App\Services\RH\Archive;

use App\Repositories\RH\Archive\ArchiveDocumentVersionRepository;
use App\Services\AbstractService;

class ArchiveDocumentVersionService extends AbstractService
{
    public function __construct(ArchiveDocumentVersionRepository $repository)
    {
        parent::__construct($repository);
    }
}
