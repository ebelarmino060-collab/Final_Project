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
| 5 | API Response | Not Started |
| 6 | Designing the URL | Not Started |
| 7 | How to Version Your API | Not Started |
| 8 | Authentication with JWT | Not Started |
| 9 | Designing Response Payloads | Not Started |
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
