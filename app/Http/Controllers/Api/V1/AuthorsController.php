<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only view of the users who have raised tickets (the "authors").
 */
class AuthorsController extends Controller
{
    /**
     * GET /api/v1/authors
     * Lists only users who raised at least one ticket, 15 per page.
     */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::has('tickets')->paginate());
    }

    /**
     * GET /api/v1/authors/{author}
     * Returns one author (route model binding loads the User).
     */
    public function show(User $author): UserResource
    {
        return new UserResource($author);
    }
}
