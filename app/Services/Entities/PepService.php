<?php

namespace App\Services\Entities;

use App\External\PepExternalApi;
use App\External\SanctionExternalApi;

use App\External\getDataSanctionExternal;
use App\Repositories\Entities\EntitiesRepository;
use App\Repositories\Entities\PepRepository;
use App\Services\AbstractService;

class PepService extends AbstractService
{
    private EntitiesService $entitiesService;
    public function __construct(PepRepository $repository, private readonly PepExternalApi $pepExternalApi, EntitiesService $entitiesService)
    {
        parent::__construct($repository);
        $this->entitiesService = $entitiesService;
    }

    public function GetPepExternal(array $data)
    {
        $name = $data['name'] ?? null;
        return $this->pepExternalApi::getAllPep($name);
    }

    public function getAllSanctions(array $data)
    {
        $name = $data['name'] ?? null;

        return SanctionExternalApi::getAllSanctions($name); // chama direto
    }
  public function createEntityPep($entityID)
{
    $entity = $this->entitiesService->show($entityID);

    if (!$entity) {
        throw new \Exception("Entidade com ID {$entityID} não encontrada.");
    }

    // Se vier array, converte para objeto
    if (is_array($entity)) {
        $entity = (object) $entity;
    }

    if (empty($entity->social_denomination)) {
        throw new \Exception("Nome da entidade não encontrado no retorno.");
    }

    $pep = $this->repository->storeOrUpdate([
        'name' => $entity->social_denomination,
    ]);

    return $pep;
}

}
