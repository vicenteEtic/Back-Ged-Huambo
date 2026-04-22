<?php

namespace App\Exceptions;
use Illuminate\Support\Str;
use Exception; 
use Illuminate\Support\Facades\Log;

class CascadeSoftDeleteException extends Exception
{
    public static function softDeleteNotImplemented($class)
    {
        return new static(sprintf('%s não implementa Illuminate\Database\Eloquent\SoftDeletes', $class));
    }

    public static function invalidRelationships($relationships)
    {
        return new static(sprintf(
            '%s [%s] deve existir e retornar um objeto do tipo Illuminate\Database\Eloquent\Relations\Relation',
            Str::plural('Relacionamento', count($relationships)),
            implode(', ', $relationships)
        ));
    }
}
