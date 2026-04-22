<?php

namespace App\Http\Controllers\Permission;

use App\Http\Controllers\AbstractController;
use App\Services\Permission\PermissionRoleService;
use App\Http\Requests\Permission\PermissionRoleRequest;
use Exception; 
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class PermissionRoleController extends AbstractController
{
    public function __construct(PermissionRoleService $service)
    {
        $this->service = $service;
    }
}
