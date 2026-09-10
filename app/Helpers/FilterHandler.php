<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;


class FilterHandler
{
    /**
     * Apply a series of filters to the given query based on the provided order params, which may include nested relationships.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyFilter(Builder $query, $filterParams)
    {
        foreach ($filterParams as $field => $filter) {
            // TODO: Refactor the applyFilter function. Eliminate the sequence of if statements and make the function as generic as possible.
            $method = $filter['filterType'];
            $field = strtolower($field);
            $value = isset ($filter['filterValue']) ? $filter['filterValue'] : null;
            if (method_exists($this, $method)) {
                $dotPosition = strrpos($field, '.');
                if ($dotPosition !== false) {
                    $relationName = substr($field, 0, $dotPosition);
                    $relationField = substr($field, $dotPosition + 1);
                    $query->whereHas($relationName, function ($subQuery) use ($relationField, $method, $value) {
                        $this->$method($subQuery, $relationField, $value);
                    });
                } else if ($method == 'ILIKE_OR') {
                    $query->where(function ($query) use ($value, $method) {
                        foreach ($value['fields'] as $item) {
                            $dotPosition = strrpos($item, '.');
                            if ($dotPosition !== false) {
                                $relationName = substr($item, 0, $dotPosition);
                                $relationField = substr($item, $dotPosition + 1);
                                $query->orWhereHas($relationName, function ($subQuery) use ($relationField, $method, $value) {
                                    $this->ILIKE($subQuery, $relationField, $value['value']);
                                });
                            } else {
                                $query = $this->$method($query, $item, $value['value']);
                            }
                        }
                    });
                } else if ($method == 'RELATION_DOESNT_EXIST') {
                    $query = $this->$method($query, $field, $value);
                } else {
                    $query = $this->$method($query, $query->getModel()->getTable() . '.' . $field, $value);
                }
            } else if ($method == 'RELATION_DOESNT_EXIST_OR_NOT_IN_ARRAY') {
                $dotPosition = strrpos($field, '.');
                $relationName = substr($field, 0, $dotPosition);
                $relationField = substr($field, $dotPosition + 1);
                
                $query->where(function ($query) use ($relationName, $relationField, $value) {
                    $query->doesntHave($relationName)
                        ->orWhereHas($relationName, function ($query) use ($relationField, $value) {
                            $query->whereNotIn($relationField, $value);
                        });
                });
            }

        }
        return $query;
    }

    /**
     * Apply a sequence of sorting instructions to the query based on the provided order params, which may include nested relationships.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $order
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyOrder($query, $orderByParams)
    {
        $joinedTables = [];

        foreach ($orderByParams as $field => $direction) {
            $field = strtolower($field);
            $dotPosition = strrpos($field, '.');
            if ($dotPosition !== false) {
                $relationship = substr($field, 0, $dotPosition);
                $column = substr($field, $dotPosition + 1);

                if (method_exists($query->getModel(), $relationship)) {
                    $relationInstance = $query->getModel()->$relationship();

                    // Relações múltiplas não podem ser usadas num JOIN para ordenar:
                    // cada filho repetiria o registo da tabela principal.
                    if (! $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasOne
                        && ! $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                        continue;
                    }

                    if (!in_array($relationship, $joinedTables)) {
                        $joinedTables[] = $relationship;
                        $relatedModel = $relationInstance->getRelated();
                        $relatedTable = $relatedModel->getTable();

                        if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                            $foreignKey = $relationInstance->getForeignKeyName();
                            $localKey = $relationInstance->getLocalKeyName();
                        } elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                            $foreignKey = $relationInstance->getOwnerKeyName();
                            $localKey = $relationInstance->getForeignKeyName();
                        }

                        $query->leftJoin($relatedTable, $query->getModel()->getTable() . '.' . $localKey, '=', $relatedTable . '.' . $foreignKey)
                            ->select($query->getModel()->getTable() . '.*');
                    }
                    if ($column === 'codigo') {
                        $query->orderByRaw("CAST($relatedTable.$column AS INTEGER) $direction");
                    } else {
                        $query->orderBy($relatedTable . '.' . $column, $direction);
                    }
                }
            } else {
                if ($field === 'codigo') {
                    $query->orderByRaw("CAST($field AS INTEGER) $direction");
                } else {
                    $query->orderBy($field, $direction);
                }
    
            }
        }

        if (! empty($joinedTables)) {
            $query->distinct();
        }

        return $query;
    }

    /**
     * Apply a case-insensitive ILIKE filter to the given field
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function ILIKE($query, $field, $value)
    {
        return $query->where(DB::raw('unaccent(CAST(' . $field . ' AS TEXT))'), 'ilike', DB::raw('unaccent(\'%' . $value . '%\')'));
    }

    /**
     * Apply a case-insensitive ILIKE_OR filter to the given field
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function ILIKE_OR($query, $field, $value)
    {
        return $query->orWhere($field, 'ilike', '%' . $value . '%');
    }

    /**
     * Apply an EQUALS filter to the given field
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function EQUALS($query, $field, $value)
    {
        return $query->where($field, '=', $value);
    }

    /**
     * Apply a GREATER_THAN filter to the given field
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function GREATER_THAN($query, $field, $value)
    {
        return $query->where($field, '>', $value);
    }

    /**
     * Apply a LESS_THAN filter to the given field
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function LESS_THAN($query, $field, $value)
    {
        return $query->where($field, '<', $value);
    }

    /**
     * Apply a range filter to the given field with a minimum and maximum value
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $range
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function VALUE_RANGE($query, $field, $value)
    {
        return $query->whereBetween($field, [$value['min'], $value['max']]);
    }

    /**
     * Apply a date time range filter to the given field with a start and end date
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $range
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function DATE_TIME_RANGE($query, $field, $value)
    {
        $startDate = Carbon::parse($value['startDate'], 'America/Sao_Paulo')->setTimezone('UTC')->toDateTimeString();
        $endDate = Carbon::parse($value['endDate'], 'America/Sao_Paulo')->setTimezone('UTC')->toDateTimeString();

        return $query->whereBetween($field, [$startDate, $endDate]);
    }

    /**
     * Apply a date range filter to the given field with a start and end date
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $range
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function DATE_RANGE($query, $field, $value)
    {
        return $query->whereBetween($field, [$value['startDate'], $value['endDate']]);
    }

    /**
     * Apply an IN_ARRAY filter to the given field with an array of values
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $values
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function IN_ARRAY($query, $field, $values)
    {
        return $query->whereIn($field, $values);
    }
    
    /**
     * Apply an NOT_IN_ARRAY filter to the given field with an array of values
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $values
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function NOT_IN_ARRAY($query, $field, $values)
    {
        return $query->whereNotIn($field, $values);
    }

    /**
     * Apply a NULL filter to the given field
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function NULL($query, $field)
    {
        return $query->whereNull($field);
    }

    /**
     * Apply a NOT_NULL filter to the given field
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function NOT_NULL($query, $field)
    {
        return $query->whereNotNull($field);
    }

    /**
     * Apply a filter to check the absence of a relationship
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function RELATION_DOESNT_EXIST($query, $field)
    {
        return $query->doesntHave($field);
    }

    /**
     * Summary of COLUMN_EQUALS
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function COLUMN_EQUALS($query, $field, $value)
    {
        return $query->whereColumn($field, $value);
    }

    /**
     * Summary of COLUMN_NOT_EQUAL
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $field
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function COLUMN_NOT_EQUAL($query, $field, $value)
    {
        return $query->whereColumn($field, '!=', $value);
    }

    private function FIXED($query, $field, $value)
    {
        return $query->where($field, '!=', $value);
    }

    private function NFE($query, $field) {
        return $query->where(function ($query) {
            $query->where('modulo', '=', '2')->orWhere(function ($query) {
                $query->where('modulo', '=', '4')
                    ->whereHas('vendaWithDfe');
            });
        });
    }

    private function LENGTH_EQUALS(Builder $query, $field, $length)
    {
        return $query->whereRaw('LENGTH(' . $this->escapeField($field) . ') = ?', [$length]);
    }

    private function LENGTH_GREATER_THAN(Builder $query, $field, $length)
    {
        return $query->whereRaw('LENGTH(' . $this->escapeField($field) . ') > ?', [$length]);
    }

    private function LENGTH_LESS_THAN(Builder $query, $field, $length)
    {
        return $query->whereRaw('LENGTH(' . $this->escapeField($field) . ') < ?', [$length]);
    }

    private function escapeField($field)
    {
        return preg_replace('/[^a-zA-Z0-9_.]/', '', $field);
    }
}
