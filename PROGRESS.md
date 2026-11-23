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
| 10 | Conditionally Omitting and Including Data | Not Started |
| 11 | Using Optional Parameters to Load Optional Data | Not Started |
| 12 | Writing Filters | Not Started |
| 13 | Using (and Filtering) Nested Resources | Not Started |
| 14 | Sorting Data | Not Started |
| 15 | Creating Resources with POST Requests | Not Started |
| 16 | Deleting Resources with DELETE Requests | Not Started |
| 17 | Replacing Resources with PUT Requests | Not Started |
| 18 | Updating Resources with PATCH Requests | Not Started |
| 19 | Using Policies for User Authorization | Not Started |
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
