<?php

namespace App\Services;

use App\Repositories\AbstractRepository;
use Mews\Purifier\Facades\Purifier;

abstract class AbstractService
{
    protected AbstractRepository $repository;

    public function __construct(AbstractRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        return $this->repository->index($paginate, $filterParams, $orderByParams, $relationships);
    }

    public function store(array $data)
    {
        $data = $this->clean($data);

        $model = $this->repository->store($data);

        return $model->fresh(); // ✔ garante dados atualizados
    }

    public function storeOrUpdate(array $attributes, array $values = [])
    {
        $values = $this->clean($values);
        $attributes = $this->clean($attributes);

        $model = $this->repository->storeOrUpdate($attributes, $values);

        return $model->fresh();
    }

    public function show(int|string $id)
    {
        return $this->repository->show($id);
    }

    public function update(array $data, int $id)
    {
        $data = $this->clean($data);

        return $this->repository->update($data, $id);
    }

    public function destroy(int $id)
    {
        $this->repository->destroy($id);
    }

    public function restore(int $id)
    {
        return $this->repository->restore($id);
    }

    public function findOneBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    public function findBy(array $criteria)
    {
        return $this->repository->findBy($criteria);
    }

    protected function clean(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = strip_tags($value);
                $value = trim($value);
            }
        });

        return $data;
    }
}
