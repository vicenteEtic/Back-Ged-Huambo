<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait ApiResponseTrait
{
    /**
     * Resposta padrão da API
     */
    protected function success($data = null, int $status = 200)
    {
        return response()->json(
            $this->normalize($data),
            $status
        );
    }

    /**
     * Normaliza qualquer tipo de retorno
     */
    protected function normalize($data)
    {
        if ($data instanceof Model) {
            return $this->sanitize($data->toArray());
        }

        if ($data instanceof Collection) {
            return $data->map(fn ($item) => $this->normalize($item))->toArray();
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->normalize($value);
            }
            return $data;
        }

        if (is_string($data)) {
            return strip_tags($data);
        }

        return $data;
    }

    /**
     * Remove HTML/XSS
     */
    protected function sanitize(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = strip_tags($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }

    /**
     * Acesso seguro (evita erro array vs object)
     */
    protected function safeGet($data, string $key, $default = null)
    {
        if (is_array($data)) {
            return $data[$key] ?? $default;
        }

        if (is_object($data)) {
            return $data->{$key} ?? $default;
        }

        return $default;
    }
}