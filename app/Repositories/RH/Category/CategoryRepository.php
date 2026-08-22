<?php

namespace App\Repositories\RH\Category;

use App\Models\RH\Category\Category;
use App\Repositories\AbstractRepository;

class CategoryRepository extends AbstractRepository
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }
}
