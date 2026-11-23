<?php

namespace App\Http\Filters\V1;

use Illuminate\Database\Eloquent\Builder;

/**
 * The filters allowed on the tickets endpoints.
 */
class TicketFilter extends QueryFilter
{
    /**
     * ?createdAt=2026-08-19   or   ?createdAt=2026-08-01,2026-08-31
     */
    public function createdAt(string $value): Builder
    {
        return $this->filterDate('created_at', $value);
    }

    /**
     * ?updatedAt=... works exactly like createdAt, on the updated_at column.
     */
    public function updatedAt(string $value): Builder
    {
        return $this->filterDate('updated_at', $value);
    }

    /**
     * ?include=author,assignee loads those relationships. Only names on the
     * allowed list are accepted, so clients cannot request anything else.
     */
    public function include(string $value): Builder
    {
        $allowed = array_intersect(explode(',', strtolower($value)), ['author', 'assignee']);

        return $this->builder->with($allowed);
    }

    /**
     * ?status=open   or   ?status=open,in_progress   (one or several statuses)
     */
    public function status(string $value): Builder
    {
        return $this->builder->whereIn('status', explode(',', $value));
    }

    /**
     * ?priority=high   or   ?priority=high,urgent
     */
    public function priority(string $value): Builder
    {
        return $this->builder->whereIn('priority', explode(',', $value));
    }

    /**
     * ?title=*printer*   (the * wildcard becomes SQL's % in a LIKE search)
     */
    public function title(string $value): Builder
    {
        return $this->builder->where('title', 'like', $this->likeValue($value));
    }

    /**
     * ?ticketNumber=TCK-20261123-*
     */
    public function ticketNumber(string $value): Builder
    {
        return $this->builder->where('ticket_number', 'like', $this->likeValue($value));
    }

    /**
     * ?search=printer   finds tickets whose ticket number or title contains the text.
     */
    public function search(string $value): Builder
    {
        $term = '%'.$this->likeValue($value).'%';

        return $this->builder->where(function (Builder $query) use ($term): void {
            $query->where('ticket_number', 'like', $term)
                ->orWhere('title', 'like', $term);
        });
    }

    /**
     * ?createdBy=5   or   ?createdBy=5,6   (tickets raised by those users)
     */
    public function createdBy(string $value): Builder
    {
        return $this->builder->whereIn('created_by', explode(',', $value));
    }

    /**
     * ?assignedTo=5   or   ?assignedTo=5,6   (tickets assigned to those users)
     */
    public function assignedTo(string $value): Builder
    {
        return $this->builder->whereIn('assigned_to', explode(',', $value));
    }
}
