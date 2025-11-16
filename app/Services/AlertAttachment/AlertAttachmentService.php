<?php
namespace App\Services\AlertAttachment;

use App\Repositories\AlertAttachment\AlertAttachmentRepository;
use App\Services\AbstractService;

class AlertAttachmentService extends AbstractService
{
    public function __construct(AlertAttachmentRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createComplaintAttachment(array $data,int $alertID){
     return $this->repository->createComplaintAttachment($data,$alertID);
    }

      public function showFile($id)
    {
        return $this->repository->showFile($id);
    }

  
}