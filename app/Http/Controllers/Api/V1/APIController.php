<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base class for the version 1 API controllers. Shared helpers live here.
 */
class APIController extends Controller
{
    // ok(), success(), error() and notAuthorized() helpers, plus authorize() for policies
    use ApiResponses, AuthorizesRequests;

    /**
     * Returns true when the client asked for a relationship with ?include=...
     * Example: /tickets?include=author  ->  include('author') is true.
     */
    public function include(string $relationship): bool
    {
        // Read the "include" query parameter (null when it is not in the URL)
        $param = request()->query('include');

        if (! is_string($param)) {
            return false;
        }

        // ?include=author,assignee  ->  ['author', 'assignee']
        $includeValues = explode(',', strtolower($param));

        return in_array(strtolower($relationship), $includeValues, true);
    }

    /**
     * Asks the policy whether the logged-in user may do $ability on $target.
     * Returns true or false instead of throwing an exception.
     *
     * Example: isAble('update', $ticket) calls TicketPolicy::update()
     */
    public function isAble(string $ability, mixed $target): bool
    {
        try {
            // authorize() throws AuthorizationException when the policy says no
            $this->authorize($ability, $target);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }
}
