<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait NormalizesResponse
{
    protected function normalize($data)
    {
        if ($data instanceof Model) {
            return $data;
        }

        if ($data instanceof Collection) {
            return $data->map(fn($item) => $this->normalize($item));
        }

        if (is_array($data)) {
            return array_map(fn($item) => $this->normalize($item), $data);
        }

        if (is_string($data)) {
            return strip_tags($data);
        }

        return $data;
    }

    protected function safeArray($data)
    {
        if ($data instanceof Model) {
            return $data->toArray();
        }

        if ($data instanceof Collection) {
            return $data->toArray();
        }

        return $data;
    }
}