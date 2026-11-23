<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Filters\V1\QueryFilter;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A support ticket raised by a user (its author) and optionally assigned to another user.
 * Fillable lists the columns that may be mass assigned with Ticket::create([...]).
 */
#[Fillable(['title', 'description', 'status', 'priority', 'created_by', 'assigned_to'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * Give every new ticket a ticket number unless one was already set.
     */
    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            $ticket->ticket_number ??= self::generateTicketNumber();
        });
    }

    /**
     * Build a new ticket reference such as TCK-20261123-AB12CD.
     */
    public static function generateTicketNumber(): string
    {
        return 'TCK-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
        ];
    }

    /**
     * The user who raised the ticket, found through the created_by column.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user the ticket is assigned to, found through the assigned_to column.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Query scope: Ticket::filter($filters) applies the filters sent in the URL.
     *
     * @param  Builder<Ticket>  $builder
     * @return Builder<Ticket>
     */
    public function scopeFilter(Builder $builder, QueryFilter $filters): Builder
    {
        return $filters->apply($builder);
    }
}
