<?php
namespace App\Repositories\Alert\GrupoAlertEmails;

use App\Models\Alert\GrupoAlertEmails\GrupoAlertEmails;
use App\Models\Alert\GrupoType\GrupoType;
use App\Repositories\AbstractRepository;

class GrupoAlertEmailsRepository extends AbstractRepository
{
    public function __construct(GrupoAlertEmails $model)
    {
        parent::__construct($model);
    }
    public function store(array $data)
    {
        $grup = $this->model->create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        if (!empty($data['grupo_Type']) && is_array($data['grupo_Type'])) {
            $grup->grupoTypes()->createMany(
                collect($data['grupo_Type'])->map(fn($type) => [
                    'name' => $type,
                   
                ])->toArray()
            );
        }
    
        return $grup->load('grupoTypes');
    }
    
    public function update(array $data, int $id)
{
    // 🔹 busca o grupo
    $grup = $this->model->findOrFail($id);

    // 🔹 atualiza os dados principais
    $grup->update([
        'name'        => $data['name'],
        'description' => $data['description'] ?? null,
    ]);

    // 🔹 atualiza os tipos do grupo
    if (isset($data['grupo_Type']) && is_array($data['grupo_Type'])) {

        // remove os tipos antigos
        $grup->grupoTypes()->delete();

        // cria os novos tipos
        $grup->grupoTypes()->createMany(
            collect($data['grupo_Type'])->map(fn ($type) => [
                'name' => $type,
            ])->toArray()
        );
    }

    return $grup->load('grupoTypes');
}


    
}