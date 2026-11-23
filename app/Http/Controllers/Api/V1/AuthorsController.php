<?php

namespace App\Http\Controllers\Api\V1;

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
    public function index(): AnonymousResourceCollection
    {
        // ?include=tickets: load each author's tickets in one extra query
        if ($this->include('tickets')) {
            return UserResource::collection(User::has('tickets')->with('tickets')->paginate());
        }

        return UserResource::collection(User::has('tickets')->paginate());
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
