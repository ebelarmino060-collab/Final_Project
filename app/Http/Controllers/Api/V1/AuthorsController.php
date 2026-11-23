<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Filters\V1\AuthorFilter;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only view of the users who have raised tickets (the "authors").
 */
class AuthorsController extends APIController
{
    /**
     * GET /api/v1/authors
     * Lists only users who raised at least one ticket, 15 per page.
     */
    public function index(AuthorFilter $filters): AnonymousResourceCollection
    {
        // Authors only, with the filters, sorting and ?include=tickets from the URL applied
        return UserResource::collection(
            User::has('tickets')->filter($filters)->paginate()->withQueryString()
        );
    }

    /**
     * GET /api/v1/authors/{author}
     * Returns one author (route model binding loads the User).
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
