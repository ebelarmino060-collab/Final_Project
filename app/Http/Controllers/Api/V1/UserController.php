<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Filters\V1\AuthorFilter;
use App\Http\Requests\Api\V1\ReplaceUserRequest;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Users
 *
 * User management. Any logged-in user can list and view users; only managers can create,
 * replace, update and delete them.
 */
class UserController extends APIController
{
    /**
     * List users
     *
     * Lists every user (not only authors), with the filters and sorting from the URL.
     *
     * @queryParam name string Filter by name. Use * as a wildcard. Example: *john*
     * @queryParam email string Filter by email. Use * as a wildcard. Example: *@example.com
     * @queryParam sort string Sort by name, email, createdAt or updatedAt. Prefix with - for descending. Example: -createdAt
     * @queryParam include string Include the tickets each user raised. Example: tickets
     *
     * @apiResourceCollection App\Http\Resources\V1\UserResource
     *
     * @apiResourceModel App\Models\User paginate=15
     */
    public function index(AuthorFilter $filters): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::filter($filters)->paginate()->withQueryString()
        );
    }

    /**
     * Create a user
     *
     * Managers only. The password is hashed before it is stored.
     *
     * @apiResource App\Http\Resources\V1\UserResource status=201
     *
     * @apiResourceModel App\Models\User
     *
     * @response 403 scenario="Not a manager" {"errors":[{"status":403,"message":"You are not authorized to create that resource.","source":""}]}
     */
    public function store(StoreUserRequest $request): UserResource|JsonResponse
    {
        if ($this->isAble('store', User::class)) {
            return new UserResource(User::create($request->mappedAttributes()));
        }

        return $this->notAuthorized('You are not authorized to create that resource.');
    }

    /**
     * Show a user
     *
     * @urlParam user integer required The user id. Example: 1
     *
     * @queryParam include string Include the tickets this user raised. Example: tickets
     *
     * @apiResource App\Http\Resources\V1\UserResource
     *
     * @apiResourceModel App\Models\User
     */
    public function show(User $user): UserResource
    {
        if ($this->include('tickets')) {
            return new UserResource($user->load('tickets'));
        }

        return new UserResource($user);
    }

    /**
     * Update a user
     *
     * Managers only. Changes only the fields that are sent, for example isManager to promote a user.
     *
     * @urlParam user integer required The user id. Example: 1
     *
     * @apiResource App\Http\Resources\V1\UserResource
     *
     * @apiResourceModel App\Models\User
     */
    public function update(UpdateUserRequest $request, User $user): UserResource|JsonResponse
    {
        if ($this->isAble('update', $user)) {
            $user->update($request->mappedAttributes());

            return new UserResource($user);
        }

        return $this->notAuthorized('You are not authorized to update that resource.');
    }

    /**
     * Replace a user
     *
     * Managers only. Every field is required.
     *
     * @urlParam user integer required The user id. Example: 1
     *
     * @apiResource App\Http\Resources\V1\UserResource
     *
     * @apiResourceModel App\Models\User
     */
    public function replace(ReplaceUserRequest $request, User $user): UserResource|JsonResponse
    {
        if ($this->isAble('replace', $user)) {
            $user->update($request->mappedAttributes());

            return new UserResource($user);
        }

        return $this->notAuthorized('You are not authorized to replace that resource.');
    }

    /**
     * Delete a user
     *
     * Managers only. The tickets the user raised are deleted with them, and tickets assigned
     * to them become unassigned.
     *
     * @urlParam user integer required The user id. Example: 1
     *
     * @response 200 {"data":null,"message":"User successfully deleted","status":200}
     */
    public function destroy(User $user): JsonResponse
    {
        if ($this->isAble('delete', $user)) {
            $user->delete();

            return $this->ok('User successfully deleted');
        }

        return $this->notAuthorized('You are not authorized to delete that resource.');
    }
}
