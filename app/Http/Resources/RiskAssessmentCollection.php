<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RiskAssessmentResourceCollection extends ResourceCollection
{
    public $collects = RiskAssessmentResourceGET::class;

    public function toArray($request)
    {
        return parent::toArray($request); // mantém os dados transformados por RiskAssessmentResourceGET
    }

    public function with($request)
    {
        // Adiciona informações de paginação
        return [
            'current_page' => $this->resource->currentPage(),
            'first_page_url' => $this->resource->url(1),
            'from' => $this->resource->firstItem(),
            'last_page' => $this->resource->lastPage(),
            'last_page_url' => $this->resource->url($this->resource->lastPage()),
            'links' => $this->resource->linkCollection()->toArray(),
            'next_page_url' => $this->resource->nextPageUrl(),
            'path' => $this->resource->path(),
            'per_page' => $this->resource->perPage(),
            'prev_page_url' => $this->resource->previousPageUrl(),
            'to' => $this->resource->lastItem(),
            'total' => $this->resource->total(),
        ];
    }
}
