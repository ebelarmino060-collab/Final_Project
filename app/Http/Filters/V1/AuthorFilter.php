<?php

namespace App\Http\Filters\V1;

use Illuminate\Database\Eloquent\Builder;

/**
 * The filters and sort columns allowed on the authors and users endpoints.
 */
class AuthorFilter extends QueryFilter
{
    /**
     * The columns clients may sort users by.
     *
     * @var array<int|string, string>
     */
    protected array $sortable = [
        'name',
        'email',
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at',
    ];

    /**
     * ?id=1,2,3   (one or several ids)
     */
    public function id(string $value): Builder
    {
        return $this->builder->whereIn('id', explode(',', $value));
    }

    /**
     * ?name=*john*   (wildcard search)
     */
    public function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', $this->likeValue($value));
    }

    /**
     * ?email=*@example.com   (wildcard search)
     */
    public function email(string $value): Builder
    {
        return $this->builder->where('email', 'like', $this->likeValue($value));
    }

    /**
     * ?createdAt=2026-08-19   or   ?createdAt=from,to
     */
    public function createdAt(string $value): Builder
    {
        return $this->filterDate('created_at', $value);
    }

    /**
     * ?updatedAt=2026-08-19   or   ?updatedAt=from,to
     */
    public function updatedAt(string $value): Builder
    {
        return $this->filterDate('updated_at', $value);
    }

    /**
     * ?include=tickets loads each user's tickets (allowed relationships only).
     */
    public function include(string $value): Builder
    {
        $allowed = array_intersect(explode(',', strtolower($value)), ['tickets']);

        return $this->builder->with($allowed);
    }
}
