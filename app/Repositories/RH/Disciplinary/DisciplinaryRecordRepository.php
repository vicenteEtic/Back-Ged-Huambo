<?php

namespace App\Repositories\RH\Disciplinary;

use App\Models\RH\Disciplinary\DisciplinaryRecord;
use App\Repositories\AbstractRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DisciplinaryRecordRepository extends AbstractRepository
{
    public function __construct(DisciplinaryRecord $model) { parent::__construct($model); }

    public function store(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['evidence_path']) && $data['evidence_path'] instanceof UploadedFile) {
                $file = $data['evidence_path'];
                $data['evidence_path'] = $file->store('disciplinary-evidence', 'public');
            }
            return $this->model->create($data);
        }, 6);
    }

    public function update(array $data, string|int $id): mixed
    {
        return DB::transaction(function () use ($data, $id) {
            $model = $this->model->findOrFail($id);

            if (isset($data['evidence_path']) && $data['evidence_path'] instanceof UploadedFile) {
                if ($model->evidence_path) {
                    Storage::disk('public')->delete($model->evidence_path);
                }
                $file = $data['evidence_path'];
                $data['evidence_path'] = $file->store('disciplinary-evidence', 'public');
            }

            $model->update($data);
            return $model->fresh();
        }, 6);
    }
}
