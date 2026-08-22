<?php

namespace App\Http\Controllers\RH\Category;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Category\CategoryRequest;
use App\Services\RH\Category\CategoryService;

class CategoryController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Categoria';
    protected ?string $fieldName = 'name';

    public function __construct(CategoryService $service)
    {
        parent::__construct($service);
    }

    public function store(CategoryRequest $request)
    {
        return $this->handleStore(
            fn () => $this->service->store($request->validated()),
        );
    }

    public function update(CategoryRequest $request, $id)
    {
        return $this->handleUpdate(
            fn () => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
