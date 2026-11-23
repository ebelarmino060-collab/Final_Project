<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Filters\V1\AuthorFilter;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Authors
 *
 * Read-only view of the users who have raised tickets.
 */
class AuthorsController extends APIController
{
    /**
     * List authors
     *
     * Lists only users who raised at least one ticket, 15 per page.
     *
     * @queryParam id string Filter by one or several ids. Example: 1,2
     * @queryParam name string Filter by name. Use * as a wildcard. Example: *john*
     * @queryParam email string Filter by email. Use * as a wildcard. Example: *@example.com
     * @queryParam createdAt string A date, or a range as from,to. Example: 2026-01-01,2026-12-31
     * @queryParam sort string Sort by name, email, createdAt or updatedAt. Prefix with - for descending. Example: name
     * @queryParam include string Include the tickets each author raised. Example: tickets
     *
     * @apiResourceCollection App\Http\Resources\V1\UserResource
     *
     * @apiResourceModel App\Models\User paginate=15
     */
    public function index(AuthorFilter $filters): AnonymousResourceCollection
    {
        // Authors only, with the filters, sorting and ?include=tickets from the URL applied
        return UserResource::collection(
            User::has('tickets')->filter($filters)->paginate()->withQueryString()
        );
    }

    /**
     * Show an author
     *
     * @urlParam author integer required The user id. Example: 1
     *
     * @queryParam include string Include the tickets this author raised. Example: tickets
     *
     * @apiResource App\Http\Resources\V1\UserResource
     *
     * @apiResourceModel App\Models\User
     */
    public function show(User $author): UserResource
    {
        // ?include=tickets: load this author's tickets
        if ($this->include('tickets')) {
            return new UserResource($author->load('tickets'));
        }

        return new UserResource($author);
    }
}
