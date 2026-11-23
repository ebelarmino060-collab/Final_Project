<?php

namespace App\Http\Filters\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use ReflectionMethod;

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

    /**
     * Columns the client may sort by. Child classes fill this in.
     * 'createdAt' => 'created_at' maps a public name to a real column.
     *
     * @var array<int|string, string>
     */
    protected array $sortable = [];

    public function __construct(protected Request $request) {}

    /**
     * Columns that sort in a fixed, meaningful order instead of alphabetically.
     * Child classes override this, for example 'priority' => ['low', 'medium', 'high', 'urgent'].
     *
     * @return array<string, list<string>>
     */
    protected function sortOrders(): array
    {
        return [];
    }

    /**
     * Loop over the query parameters and call the matching filter method (if it exists).
     * Parameters without a method are ignored.
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->request->query() as $key => $value) {
            // Array values such as ?status[]=x are not accepted by any filter
            if (! is_string($value)) {
                continue;
            }

            if ($key === 'sort') {
                $this->sort($value);
            } elseif ($this->isFilterMethod($key)) {
                $this->$key($value);
            }
        }

        return $builder;
    }

    /**
     * Only public filter methods of the child class may be called from the query string,
     * never apply(), the constructor or a protected helper.
     */
    protected function isFilterMethod(string $name): bool
    {
        if ($name === 'apply' || str_starts_with($name, '__') || ! method_exists($this, $name)) {
            return false;
        }

        return (new ReflectionMethod($this, $name))->isPublic();
    }

    /**
     * ?sort=title              ascending
     * ?sort=-createdAt         descending (leading minus)
     * ?sort=status,title       several columns, in order
     */
    protected function sort(string $value): void
    {
        foreach (explode(',', $value) as $sortAttribute) {
            $direction = 'asc';

            // A leading minus means descending order
            if (str_starts_with($sortAttribute, '-')) {
                $direction = 'desc';
                $sortAttribute = substr($sortAttribute, 1);
            }

            // Ignore anything that is not on the whitelist
            if (! in_array($sortAttribute, $this->sortable, true)
                && ! array_key_exists($sortAttribute, $this->sortable)) {
                continue;
            }

            // Translate the public name (createdAt) to the real column (created_at)
            $columnName = $this->sortable[$sortAttribute] ?? $sortAttribute;

            $this->orderBy($columnName, $direction);
        }
    }

    /**
     * Order by a whitelisted column, using its fixed order when one is defined.
     */
    protected function orderBy(string $columnName, string $direction): void
    {
        $order = $this->sortOrders()[$columnName] ?? null;

        if ($order === null) {
            $this->builder->orderBy($columnName, $direction);

            return;
        }

        // CASE priority WHEN 'low' THEN 0 WHEN 'medium' THEN 1 ... END: values are bound, the column is whitelisted
        $cases = implode(' ', array_map(fn (int $position): string => "when ? then {$position}", array_keys($order)));

        $this->builder->orderByRaw(
            "case {$this->builder->getQuery()->getGrammar()->wrap($columnName)} {$cases} else ".count($order)." end {$direction}",
            $order
        );
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
