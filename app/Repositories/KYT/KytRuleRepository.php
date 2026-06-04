<?php

namespace App\Repositories\KYT;

use App\Models\KYT\KytRule;
use App\Repositories\AbstractRepository;

class KytRuleRepository extends AbstractRepository
{
    public function __construct(KytRule $model)
    {
        parent::__construct($model);
    }

    public function store(array $data)
    {
        $products = $data['products'] ?? [];
        unset($data['products']);

        $rule = $this->model->create($data);

        if (!empty($products)) {
            $rule->products()->createMany($products);
        }

        return $rule->load('products');
    }

    public function update(array $data, int $id)
    {
        $rule = $this->model->findOrFail($id);

        $products = $data['products'] ?? null;
        unset($data['products']);

        $rule->update($data);

        if (is_array($products)) {
            $rule->products()->delete();
            if (!empty($products)) {
                $rule->products()->createMany($products);
            }
        }

        return $rule->fresh()->load('products');
    }

    public function show(int|string $id)
    {
        return $this->model->with('products')->findOrFail($id);
    }
}
