# Ticket API: Laravel API Master Class Progress Tracker

Source: the 27 lessons of the "API Master Class in Laravel" reference site.
The course builds a `Product` API; this project applies every lesson to a `Ticket` entity instead.

Statuses: Not Started · In Progress · Completed · Blocked

| # | Lesson | Status |
|---|--------|--------|
| 1 | Git Initialization | Completed |
| 2 | Laravel Installation | Completed |
| 3 | php artisan install:api | Completed |
| 4 | Git Commit to Main | Completed |
| 5 | API Response | Completed |
| 6 | Designing the URL | Completed |
| 7 | How to Version Your API | Completed |
| 8 | Authentication with JWT | Completed |
| 9 | Designing Response Payloads | Completed |
| 10 | Conditionally Omitting and Including Data | Completed |
| 11 | Using Optional Parameters to Load Optional Data | Completed |
| 12 | Writing Filters | Completed |
| 13 | Using (and Filtering) Nested Resources | Completed |
| 14 | Sorting Data | Completed |
| 15 | Creating Resources with POST Requests | Completed |
| 16 | Deleting Resources with DELETE Requests | Completed |
| 17 | Replacing Resources with PUT Requests | Completed |
| 18 | Updating Resources with PATCH Requests | Completed |
| 19 | Using Policies for User Authorization | Completed |
| 20 | Controlling Access with Token Abilities | Not Started |
| 21 | Applying Granular Permissions | Not Started |
| 22 | Implementing the Users Products Controller | Not Started |
| 23 | Managing Users | Not Started |
| 24 | The Principle of Least Privilege | Not Started |
| 25 | Handling Errors | Not Started |
| 26 | API Documentation with Scribe | Not Started |
| 27 | Appendix A: Where Each File Is Finished | Not Started |

---

## Lesson 1: Git Initialization
- **Concepts:** commits, staging, remotes; commit and push after every finished topic.
- **Ticket implementation:** Git was already installed and configured; the GitHub remote `origin` already existed.
- **Files:** none.
- **Testing:** `git --version`, `git config user.name` / `user.email` and `git remote -v` checked.
- **Status:** Completed

## Lesson 2: Laravel Installation
- **Concepts:** MVC, Composer, `.env` database configuration.
- **Ticket implementation:** the project was already a Laravel 13 install. The CLI PHP has no `pdo_sqlite` driver, so the lesson's MySQL option (2.2) is used: database `ticket` for the app and `ticket_testing` for the test suite.
- **Files:** `.env`, `.env.example`, `phpunit.xml` (tests now use the MySQL `ticket_testing` database).
- **Testing:** `php artisan migrate` ran; the existing Pest suite passes on MySQL.
- **Status:** Completed

## Lesson 3: php artisan install:api
- **Concepts:** API routes scaffold, Sanctum (installed but unused because JWT is used from Lesson 8).
- **Ticket implementation:** `php artisan install:api` created `routes/api.php`, registered it in `bootstrap/app.php` and published the Sanctum migration. `HasApiTokens` is deliberately not added (Lesson 20 says not to).
- **Files:** `routes/api.php`, `bootstrap/app.php`, `config/sanctum.php`, `database/migrations/*_create_personal_access_tokens_table.php`, `composer.json`, `composer.lock`.
- **Testing:** migration ran; `routes/api.php` exists and is registered.
- **Status:** Completed

## Lesson 4: Git Commit to Main
- **Concepts:** `git init`, `branch -M main`, `remote add origin`, first push.
- **Ticket implementation:** the repository and `main` branch already existed, so the lesson's commit is made on top of the existing history.
- **Commit:** `Initialize Laravel project`
- **Testing:** push to `origin main` succeeded.
- **Status:** Completed

## Lesson 5: API Response
- **Concepts:** HTTP status code families (2xx/4xx/5xx), 401 vs 403, PHP traits for shared response helpers.
- **Ticket implementation:** `ApiResponses` trait with `ok()`, `success()` and `error()`; every Ticket API response uses the same `data` / `message` / `status` shape.
- **Files:** `app/Traits/ApiResponses.php` (new), `tests/Feature/Traits/ApiResponsesTest.php` (new).
- **Testing:** the lesson's temporary `/ping` route was replaced by a permanent Pest test (3 tests passing).
- **Commit:** `Configure API response`
- **Status:** Completed

## Lesson 6: Designing the URL
- **Concepts:** noun-based REST URLs, HTTP methods vs CRUD, and the Route / Controller / Model / Migration / Factory / Seeder pieces.
- **Ticket implementation:** `Ticket` model with `#[Fillable]`, `tickets` migration (`ticket_number`, `title`, `description`, `status`, `priority`, `created_by`, `assigned_to`), string-backed enums `TicketStatus` (open, in_progress, pending, resolved, closed) and `TicketPriority` (low, medium, high, urgent) instead of the course's A/C/H/X codes, an auto-generated `ticket_number` (TCK-YYYYMMDD-XXXXXX), a factory with an `assigned()` state, and a seeder with 10 users and 100 tickets. The lesson's temporary `GET /api/tickets` route was added.
- **Adjustment:** the suggested `category_id` is not included, because it would need a new categories table that no lesson covers.
- **Files:** `app/Models/Ticket.php`, `app/Enums/TicketStatus.php`, `app/Enums/TicketPriority.php`, `database/migrations/*_create_tickets_table.php`, `database/factories/TicketFactory.php`, `database/seeders/DatabaseSeeder.php`, `routes/api.php`, `tests/Pest.php` (RefreshDatabase enabled), `tests/Feature/Models/TicketTest.php`, `tests/Feature/Api/TicketRoutesTest.php`.
- **Testing:** `migrate` and `db:seed` ran (100 tickets, 10 users); 9 tests passing (ticket-number generation, enum casts, `GET /api/tickets`).
- **Commit:** `URL Designing`
- **Status:** Completed

## Lesson 7: How to Version Your API
- **Concepts:** the API as a data contract; URL versioning (`/api/v1`) with one route file per version registered in the `then:` callback.
- **Ticket implementation:** `Api/V1/TicketController@index`, a new `routes/api_v1.php`, and `bootstrap/app.php` loading it under `api/v1`. The temporary `/api/tickets` route was removed. The project's existing JSON-rendering rule (`api/*` **or** `expectsJson()`) was kept rather than narrowed to the lesson's `api/*` only.
- **Files:** `app/Http/Controllers/Api/V1/TicketController.php` (new), `routes/api_v1.php` (new), `bootstrap/app.php`, `routes/api.php`, `tests/Feature/Api/V1/TicketControllerTest.php` (moved from `tests/Feature/Api/TicketRoutesTest.php`).
- **Testing:** `GET /api/v1/tickets` returns 200; `/api/tickets` returns 404 (10 tests passing).
- **Commit:** `API Versioning`
- **Status:** Completed

## Lesson 8: Authentication with JWT
- **Concepts:** stateless authentication, JWT structure (header / payload / signature), Bearer tokens, token blacklist for logout and refresh, bcrypt hashing, Form Requests for validation.
- **Ticket implementation:** `php-open-source-saver/jwt-auth` v2.10, `config/jwt.php`, a `JWT_SECRET`, a JWT `api` guard as the default, `User implements JWTSubject`, Register/Login Form Requests, and `AuthController` (register, login, me, refresh, logout). All ticket routes now sit behind `auth:api`.
- **Adjustments:** `lcobucci/jwt` needs PHP's `sodium` extension, so it was enabled in the local CLI `php.ini` (with the user's approval). The default guard keeps the `AUTH_GUARD` env override (`env('AUTH_GUARD', 'api')`). `phpunit.xml` gets a test-only `JWT_SECRET`, and `.env.example` documents the variable.
- **Files:** `composer.json`, `composer.lock`, `config/jwt.php` (new), `config/auth.php`, `app/Models/User.php`, `app/Http/Requests/Api/RegisterUserRequest.php` (new), `app/Http/Requests/Api/LoginUserRequest.php` (new), `app/Http/Controllers/Api/V1/AuthController.php` (new), `routes/api_v1.php`, `phpunit.xml`, `.env.example`, `tests/Pest.php` (`jwtFor()` helper), `tests/Feature/Api/V1/AuthControllerTest.php` (new), `tests/Feature/Api/V1/TicketControllerTest.php`.
- **Testing:** 20 tests passing (register, duplicate email / unconfirmed password 422, login, wrong password 401, me, 401 without a token, refresh blacklists the old token, logout revokes the token). Live check with `php artisan serve`: register → me → tickets returned 200, and 401 without a token.
- **Commit:** `Authenticate/Revoking Authenticated Tokens using JWT`
- **Status:** Completed

## Lesson 9: Designing Response Payloads
- **Concepts:** API Resources as transformers that decouple JSON from table columns, the JSON:API shape (type / id / attributes / relationships / links), and `paginate()` with `links` and `meta`.
- **Ticket implementation:** `TicketResource` with camelCase attributes (`ticketNumber`, `title`, `description`, `status`, `priority`, `createdAt`, `updatedAt`) and two relationships: `author` (`created_by`) and `assignee` (`assigned_to`, `null` when unassigned). `index()` paginates 15 per page; `show(Ticket $ticket)` uses route model binding.
- **Files:** `app/Http/Resources/V1/TicketResource.php` (new), `app/Http/Controllers/Api/V1/TicketController.php`, `routes/api_v1.php`, `tests/Feature/Api/V1/TicketControllerTest.php`.
- **Testing:** 24 tests passing (resource structure, 15 per page, page 2, show, null assignee, 404 for a missing ticket, 401 without a token).
- **Commit:** `Designing Response Payloads`
- **Status:** Completed

## Lesson 10: Conditionally Omitting and Including Data
- **Concepts:** `when()`, `mergeWhen()` and `whenLoaded()` for per-endpoint fields; `belongsTo` / `hasMany` relationships; a read-only authors view kept apart from user management.
- **Ticket implementation:** `Ticket::author()` (`created_by`) and `Ticket::assignee()` (`assigned_to`); `User::tickets()` and `User::assignedTickets()`. `UserResource` shows the email-verified date and timestamps only on `authors.*` and `users.*` routes. `AuthorsController` lists users who raised at least one ticket. `TicketResource` hides `description` on `tickets.index` and `users.tickets.index`, and links the author (and the assignee, when there is one) to `/authors/{id}`.
- **Files:** `app/Models/Ticket.php`, `app/Models/User.php`, `app/Http/Resources/V1/UserResource.php` (new), `app/Http/Resources/V1/TicketResource.php`, `app/Http/Controllers/Api/V1/AuthorsController.php` (new), `routes/api_v1.php`, `tests/Feature/Api/V1/AuthorsControllerTest.php` (new), `tests/Feature/Api/V1/TicketControllerTest.php`, `tests/Feature/Models/TicketTest.php`.
- **Testing:** 30 tests passing (no description on the list, description on the detail, author/assignee links, authors list filtered to ticket authors, author detail timestamps, 404, 401, relationships).
- **Commit:** `Conditionally Omitting and Including Data`
- **Status:** Completed

## Lesson 11: Using Optional Parameters to Load Optional Data
- **Concepts:** optional `?include=` query parameters, eager loading with `with()` to avoid N+1, and a base controller for shared helpers.
- **Ticket implementation:** `APIController::include()` parses `?include=a,b`. `TicketController` and `AuthorsController` extend it. Tickets accept `?include=author,assignee` (an allow-list, eager loaded); authors accept `?include=tickets`. `TicketResource` adds an `includes` object with `author` / `assignee` only when one is loaded; `UserResource` adds `includes` with the user's tickets.
- **Files:** `app/Http/Controllers/Api/V1/APIController.php` (new), `app/Http/Controllers/Api/V1/TicketController.php`, `app/Http/Controllers/Api/V1/AuthorsController.php`, `app/Http/Resources/V1/TicketResource.php`, `app/Http/Resources/V1/UserResource.php`, `tests/Feature/Api/V1/TicketControllerTest.php`, `tests/Feature/Api/V1/AuthorsControllerTest.php`.
- **Testing:** 37 tests passing (includes absent by default, author on the list, author + assignee on detail, a single eager-load query for a page, author tickets on detail and list).
- **Commit:** `Using Optional Parameters to Load Optional Data`
- **Status:** Completed

## Lesson 12: Writing Filters
- **Concepts:** a reusable filter engine. A `QueryFilter` base calls one method per query parameter; a child filter class lists the allowed parameters; a model `scopeFilter()`. A comma means a list or range, and `*` is a wildcard.
- **Ticket implementation:** `QueryFilter` (with shared `likeValue()` and `filterDate()` helpers) and `TicketFilter` with `status`, `priority`, `title`, `ticketNumber`, `search` (ticket number **or** title), `createdBy`, `assignedTo`, `createdAt`, `updatedAt` and `include` (allow-list: author, assignee). `Ticket::scopeFilter()`. `TicketController@index(TicketFilter $filters)`.
- **Adjustments (hardening):** the filter reads only the query string (`query()` rather than `all()`) and skips array values (`?status[]=x`), which would otherwise cause a 500. Pagination links keep the filters (`withQueryString()`).
- **Files:** `app/Http/Filters/V1/QueryFilter.php` (new), `app/Http/Filters/V1/TicketFilter.php` (new), `app/Models/Ticket.php`, `app/Http/Controllers/Api/V1/TicketController.php`, `tests/Feature/Http/Filters/V1/TicketFilterTest.php` (new).
- **Testing:** 51 tests passing (each filter, a combination, the include allow-list, unknown/array parameters ignored, filters kept on pagination links).
- **Commit:** `Writing Filters`
- **Status:** Completed

## Lesson 13: Using (and Filtering) Nested Resources
- **Concepts:** nested resource URLs (`/users/{user}/tickets`) via dot notation (`users.tickets`), reusing the same filter class.
- **Ticket implementation:** `UserTicketsController@index(User $user, TicketFilter $filters)` lists the tickets a user raised (`created_by`); every ticket filter works on the nested list, and the description is hidden there as on `/tickets`. Tickets **assigned** to a user are available through `/tickets?assignedTo={id}` (Lesson 12).
- **Files:** `app/Http/Controllers/Api/V1/UserTicketsController.php` (new), `routes/api_v1.php`, `tests/Feature/Api/V1/UserTicketsControllerTest.php` (new).
- **Testing:** 55 tests passing (only that user's tickets, nested list + filter, 404 for a missing user, 401).
- **Commit:** `Using (and Filtering) Nested Resources`
- **Status:** Completed

## Lesson 14: Sorting Data
- **Concepts:** the `?sort=a,-b` convention, a `$sortable` whitelist that maps public names to columns (never pass client text to `orderBy()`), and a filter plus scope for users.
- **Ticket implementation:** `QueryFilter::sort()` with the whitelist; `TicketFilter` can sort by `ticketNumber`, `title`, `status`, `priority`, `createdAt` and `updatedAt`. `AuthorFilter` (`id`, `name`, `email`, `createdAt`, `updatedAt`, `include=tickets`, sorting), `User::scopeFilter()`, and `AuthorsController@index(AuthorFilter $filters)`.
- **Adjustments:** `priority` and `status` sort in their real order (low → urgent; open → closed) through a bound `CASE` expression, not alphabetically. The filter engine now calls only **public** filter methods (plus `sort`), which fixes a 500 a client could trigger by naming a protected helper such as `?filterDate=x`.
- **Files:** `app/Http/Filters/V1/QueryFilter.php`, `app/Http/Filters/V1/TicketFilter.php`, `app/Http/Filters/V1/AuthorFilter.php` (new), `app/Models/User.php`, `app/Http/Controllers/Api/V1/AuthorsController.php`, `tests/Feature/Http/Filters/V1/TicketFilterTest.php`, `tests/Feature/Http/Filters/V1/AuthorFilterTest.php` (new).
- **Testing:** 66 tests passing (asc/desc, priority and status order, multi-column, whitelist, protected helpers not callable, author name/email/id filters, author sort, include).
- **Commit:** `Sorting Data`
- **Status:** Completed

## Lesson 15: Creating Resources with POST Requests
- **Concepts:** CRUD ↔ HTTP methods, never trusting client input (Form Request validation returns 422 automatically), mass-assignment protection, and a base request that maps the nested JSON:API body to columns.
- **Ticket implementation:** `APIController` now uses `ApiResponses`. `BaseTicketRequest::mappedAttributes()` maps `data.attributes.{title,description,status,priority}` and `data.relationships.{author,assignee}.data.id` to `title`, `description`, `status`, `priority`, `created_by` and `assigned_to`, with custom enum messages. `StoreTicketRequest` handles `tickets.store` (author id in the body) and `users.tickets.store` (author id merged from the URL in `prepareForValidation()`). `TicketController@store` returns 201.
- **Adjustments:** status and priority are optional on create and default to `open` / `medium` (model `$attributes`, matching the column defaults). `ticket_number` is never client-settable. Validation uses `Rule::enum()` instead of the course's `in:A,C,H,X`.
- **Files:** `app/Http/Requests/Api/V1/BaseTicketRequest.php` (new), `app/Http/Requests/Api/V1/StoreTicketRequest.php` (new), `app/Http/Controllers/Api/V1/APIController.php`, `app/Http/Controllers/Api/V1/TicketController.php`, `app/Models/Ticket.php`, `routes/api_v1.php`, `tests/Pest.php` (`ticketPayload()` helper), `tests/Feature/Api/V1/TicketControllerTest.php`.
- **Testing:** 73 tests passing (201 + persisted state, defaults, client ticket number ignored, 422 for missing fields, 422 with the custom enum messages, 422 for an unknown author/assignee, 401).
- **Commit:** `Creating Resources with Post Request`
- **Status:** Completed

## Lesson 16: Deleting Resources with DELETE Requests
- **Concepts:** DELETE has no body and is idempotent; route model binding answers 404 before the controller runs.
- **Ticket implementation:** `TicketController@destroy(Ticket $ticket)` returns `ok('Ticket successfully deleted')`; the `destroy` route is enabled.
- **Files:** `app/Http/Controllers/Api/V1/TicketController.php`, `routes/api_v1.php`, `tests/Feature/Api/V1/TicketControllerTest.php`.
- **Testing:** 76 tests passing (200 + row removed, a second DELETE and a GET return 404, 401 leaves the row in place).
- **Commit:** `Deleting Resources with Delete Request`
- **Status:** Completed

## Lesson 17: Replacing Resources with PUT Requests
- **Concepts:** PUT replaces the whole resource (every field `required`, idempotent); PATCH changes only what is sent. They are split into `replace()` and `update()`.
- **Ticket implementation:** `ReplaceTicketRequest` requires title, description, status, priority, author and an assignee key (`present` + `nullable`, so a PUT can unassign). `TicketController@replace` and a manual `PUT tickets/{ticket}` route. `ticket_number` is never replaced.
- **Files:** `app/Http/Requests/Api/V1/ReplaceTicketRequest.php` (new), `app/Http/Controllers/Api/V1/TicketController.php`, `routes/api_v1.php`, `tests/Feature/Api/V1/TicketControllerTest.php`.
- **Testing:** 79 tests passing (full replace including unassign and author change, ticket number preserved, 422 for missing fields leaves the row unchanged, 404).
- **Commit:** `Replacing Resources with Put Requests`
- **Status:** Completed

## Lesson 18: Updating Resources with PATCH Requests
- **Concepts:** PATCH changes only the sent fields; `sometimes` rules; `mappedAttributes()` already returns only the keys present.
- **Ticket implementation:** `UpdateTicketRequest` (every rule `sometimes`; assignee `nullable` to unassign), `TicketController@update`, and a manual `PATCH tickets/{ticket}` route. This delivers **change status**, **change priority** and **assign/unassign** as single-field PATCH requests.
- **Files:** `app/Http/Requests/Api/V1/UpdateTicketRequest.php` (new), `app/Http/Controllers/Api/V1/TicketController.php`, `routes/api_v1.php`, `tests/Feature/Api/V1/TicketControllerTest.php`.
- **Testing:** 84 tests passing (status-only change keeps the other fields, priority-only change, assign then unassign, 422 for an invalid status leaves the row unchanged, 422 for an unknown assignee).
- **Commit:** `Updating Resources with Patch Requests`
- **Status:** Completed

## Lesson 19: Using Policies for User Authorization
- **Concepts:** authentication vs authorization; a Policy per model; registering a versioned policy with `Gate::policy()`; `AuthorizesRequests::authorize()`; an `isAble()` helper that returns a bool; 403 Forbidden.
- **Ticket implementation:** `TicketPolicy::update()` (only the ticket's creator, for now), registered in `AppServiceProvider`. `APIController` uses `AuthorizesRequests` and adds `isAble()`. `ApiResponses::notAuthorized()` returns 403. `TicketController@update` asks the policy first.
- **Files:** `app/Policies/V1/TicketPolicy.php` (new), `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/Api/V1/APIController.php`, `app/Traits/ApiResponses.php`, `app/Http/Controllers/Api/V1/TicketController.php`, `tests/Feature/Policies/V1/TicketPolicyTest.php` (new), `tests/Feature/Api/V1/TicketControllerTest.php` (update tests now use the caller's own ticket).
- **Testing:** 87 tests passing (policy allow/deny, PATCH of someone else's ticket returns 403 and leaves it unchanged, owner PATCH returns 200).
- **Commit:** `Using Policies for User Authorization`
- **Status:** Completed
