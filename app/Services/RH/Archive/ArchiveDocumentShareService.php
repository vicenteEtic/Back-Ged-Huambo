<?php

namespace App\Services\RH\Archive;

use App\Repositories\RH\Archive\ArchiveDocumentShareRepository;
use App\Services\AbstractService;

class ArchiveDocumentShareService extends AbstractService
{
    public function __construct(ArchiveDocumentShareRepository $repository)
    {
        parent::__construct($repository);
    }
}
