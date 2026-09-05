<?php

namespace App\Services\RH\Attendance;

use App\Repositories\RH\Attendance\AttendanceRequestTypeRepository;
use App\Services\AbstractService;
use DomainException;

class AttendanceRequestTypeService extends AbstractService
{
    public function __construct(AttendanceRequestTypeRepository $repository)
    {
        parent::__construct($repository);
    }

    public function store(array $data)
    {
        return parent::store($this->normalize($data));
    }

    public function update(array $data, int $id)
    {
        $type = $this->repository->show($id);

        if ($type->requests()->withTrashed()->exists() && ! empty($data['code']) && $data['code'] !== $type->code) {
            throw new DomainException('O código do tipo de solicitação não pode ser alterado depois de existirem solicitações associadas.');
        }

        return parent::update($this->normalize($data), $id);
    }

    public function destroy(int $id)
    {
        $type = $this->repository->show($id);

        if ($type->requests()->withTrashed()->exists()) {
            throw new DomainException('Tipo de solicitação em utilização em solicitações: não pode ser removido. Desactive-o para impedir novos pedidos ou use um novo tipo.');
        }

        return parent::destroy($id);
    }

    protected function normalize(array $data): array
    {
        $data['required_documents'] = array_values(array_unique(array_filter($data['required_documents'] ?? [])));

        return $data;
    }
}
