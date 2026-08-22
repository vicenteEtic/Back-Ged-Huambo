<?php

namespace App\Services\RH\Category;

use App\Repositories\RH\Category\CategoryRepository;
use App\Services\AbstractService;

class CategoryService extends AbstractService
{
    public function __construct(CategoryRepository $repository)
    {
        parent::__construct($repository);
    }
}
