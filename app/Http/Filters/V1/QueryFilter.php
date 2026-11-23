<?php

namespace App\Http\Filters\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Base class for every filter. It turns query-string parameters into query conditions.
 * Example: ?status=open calls the status('open') method of the child filter class.
 */
abstract class QueryFilter
{
    /**
     * The Eloquent query being built (set in apply()).
     */
    protected Builder $builder;

    public function __construct(protected Request $request) {}

    /**
     * Loop over the query parameters and call the matching method (if it exists).
     * Parameters without a method are ignored.
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->request->query() as $key => $value) {
            // Skip "apply", names starting with __ (so clients cannot call internal methods)
            // and array values such as ?status[]=x, which the filters do not accept
            if ($key !== 'apply' && ! str_starts_with($key, '__') && is_string($value) && method_exists($this, $key)) {
                $this->$key($value);
            }
        }

        return $builder;
    }

    /**
     * Turn a * wildcard into SQL's % for a LIKE search: *printer* -> %printer%.
     */
    protected function likeValue(string $value): string
    {
        return str_replace('*', '%', $value);
    }

    /**
     * ?column=2026-08-19 matches one day; ?column=2026-08-01,2026-08-31 a date range (from,to).
     */
    protected function filterDate(string $column, string $value): Builder
    {
        $dates = explode(',', $value);

        if (count($dates) > 1) {
            return $this->builder->whereBetween($column, $dates);
        }

        return $this->builder->whereDate($column, $value);
    }
}
