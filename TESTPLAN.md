# Test Plan

Walk through this list before tagging a release or merging a change that touches `composer.json`, the analyzers, or the test suite. Run every command from the repository root.

## Setup

- [ ] `composer install` finishes without errors and without asking to change the lock file.
- [ ] `composer validate` reports the manifest is valid.
- [ ] `working/` exists after the first run and is not shown in `git status`.

## Automated checks

- [ ] `composer phpcs` reports no errors and no warnings for `src/`, `bundle/`, and `tests/`.
- [ ] `composer phpstan` reports `No errors` at level 10 for `src/`, `bundle/`, and `tests/`.
- [ ] `composer test` passes with no warnings, deprecations, or notices in the output.
- [ ] `composer check` runs all three in order and exits 0.

## Functional coverage

Confirm each area has passing tests under `tests/` that cover the listed behaviors.

### Package and bundle

- [ ] `BrightspaceApi`: exposes the `EnrollmentApi`, `OrgUnitApi`, `OutcomeApi`, and `UserApi` it was built with.
- [ ] `BrightspaceApiBundle`: extends `Bundle` and exposes `BrightspaceApiExtension` under the alias `brightspace_api`.
- [ ] `BrightspaceApiExtension`: registers `BrightspaceApi` and the four API classes as autowired, autoconfigured services; a container compiled on top of the core and gadget-http extensions, with Guzzle under the PSR interfaces, a PSR-6 pool, and the `D2L_*` env vars set, builds a `BrightspaceApi` whose four properties are the right classes.

### Users

- [ ] `User`: `create()` maps every field, keeps `OrgDefinedId`, `ExternalEmail`, and `LastAccessedDate` null when they are null, parses `LastAccessedDate` into a `DateTimeInterface`, and accepts a `User` object (keeping its date) as well as an array; `new User()` is active with id 0; `getCreatePayload()` carries the seven common fields plus `IsActive`, `RoleId`, and `SendCreationEmail`; `getUpdatePayload()` carries the common fields plus the `Activation` object, which serializes as `{"IsActive":...}`.
- [ ] `UserActivation`: reads `IsActive`; serializes as Brightspace expects.
- [ ] `WhoAmI`: maps the six fields.
- [ ] `GetWhoAmI`: `GET /users/whoami` with the bearer token; non-200 is a `ResponseException`.
- [ ] `GetUser`: `GET /users/{id}`; 404 is `null`; anything else is a `ResponseException`.
- [ ] `ListUsers`: with no filters, walks `/users/` page by page using `bookmark` and yields users keyed by `UserId`; a given bookmark is sent on the first request; `userName` reads a single record and drops `externalEmail` and `bookmark`; `orgDefinedId` reads an unpaged list and drops every other filter; `externalEmail` reads an unpaged list and drops `bookmark`; a non-200 throws when the list is iterated.
- [ ] `CreateUser`: `POST /users/` with the create payload as JSON; non-200 is a `ResponseException`.
- [ ] `UpdateUser`: `PUT /users/{UserId}` with the update payload (no `RoleId`); non-200 is a `ResponseException`.
- [ ] `DeleteUser`: `DELETE /users/{id}` returns `true`; non-200 is a `ResponseException`.
- [ ] `UserApi`: `whoAmI()`, `list()`, `get(int)` (null on 404), `get(string)` (user name lookup, null when the body is `null`), `create()`, `update()`, and `delete()` send the expected requests.

### Org units

- [ ] `OrgUnit`: `create()` maps every field, falls back to `Id` when `Identifier` is missing, keeps `Code` and `Path` null when null, and accepts an `OrgUnit` object; defaults are empty.
- [ ] `OrgUnitType` and `RootOrgUnit`: `create()` maps every field; `OrgUnitType` defaults are empty.
- [ ] `GetPrimaryUrl`: `GET /organization/primary-url` returns the raw body; non-200 is a `ResponseException`.
- [ ] `GetRootOrgUnit`: `GET /organization/info` then `GET /organization/primary-url`, and the result carries both; non-200 is a `ResponseException`.
- [ ] `GetOrgUnit` and `GetOrgUnitType`: `GET /orgstructure/{id}` and `GET /outypes/{id}`; 404 is `null`; anything else is a `ResponseException`.
- [ ] `ListOrgUnits`: walks `/orgstructure/` page by page keyed by `Identifier`; sends `orgUnitType`, `orgUnitCode`, `orgUnitName`, and `bookmark`; an exact code or name replaces the loose one; a non-200 throws on iteration.
- [ ] `ListOrgUnitAncestors`: `GET /orgstructure/{id}/ancestors/` (or `/parents/` when only parents are wanted) with `ouTypeId`; unpaged; keyed by `Identifier`; a non-200 throws on iteration.
- [ ] `ListOrgUnitDescendants`: `GET /orgstructure/{id}/descendants/paged/` (or `/children/paged/`) with `ouTypeId` and `bookmark`; walks every page; a non-200 throws on iteration.
- [ ] `ListOrgUnitTypes`: `GET /outypes/` keyed by `Id`; a non-200 throws on iteration.
- [ ] `CreateOrgUnitRelationship`: `POST /orgstructure/{child}/parents/` with the parent id as the JSON body; `DeleteOrgUnitRelationship`: `DELETE /orgstructure/{child}/parents/{parent}`; non-200 is a `ResponseException`.
- [ ] `OrgUnitApi`: every method sends the request its handler would, and `listAncestors`/`listParents` and `listDescendants`/`listChildren` hit their own endpoints.

### Enrollments

- [ ] `UserEnrollment` and `UserEnrollmentRole`: `create()` maps the nested `OrgUnit`, `User`, and `Role`; missing parts are null; role `Code` and `Name` are optional.
- [ ] `EnrollUser`: `POST /enrollments/` with `orgUnitId`, `userId`, and `roleId` as JSON; `UnenrollUser`: `DELETE /enrollments/orgUnits/{ou}/users/{user}/`; `PinEnrollment` and `UnpinEnrollment`: `POST` and `DELETE` on `.../pin`; each returns `true` and a non-200 is a `ResponseException`.
- [ ] `GetEnrollment`: `GET /enrollments/orgUnits/{ou}/users/{user}`; with ids it then fetches the org unit and the user and attaches them under the right keys; with objects it sends one request and uses them; 404 is `null`; anything else is a `ResponseException`.
- [ ] `ListEnrollmentOrgUnits`: `GET /enrollments/users/{user}/orgUnits/`; fetches the user once when given an id and not at all when given a `User`; walks every page keyed by org unit id; several `orgUnitType` values are joined with commas; items without an `OrgUnit` are skipped; a non-200 throws on iteration.
- [ ] `ListEnrollmentUsers`: `GET /enrollments/orgUnits/{ou}/users/`; fetches the org unit once when given an id; walks every page keyed by user id; sends `roleId`, `isActive`, and `bookmark`; items without a `User` are skipped; a non-200 throws on iteration.
- [ ] `EnrollmentApi`: every method sends the request its handler would.

### Outcomes

- [ ] `OutcomeDetail`: `create()` maps the tree and sets each child's `parentId` to its parent's id; a `parentId` in the data is kept; `jsonSerialize()` emits only `id` and `children`, as a list.
- [ ] `OutcomeRegistry`: `create()` maps the id and objectives; no objectives is an empty list; `merge()` adds missing top-level outcomes and missing children under outcomes both registries have, returns `false` when nothing was added, and leaves both registries' arrays as plain lists; a merge failure is a `RuntimeException` naming both ids; `equals()` is order-independent, false when any outcome or child differs, and does not re-key either registry.
- [ ] `GetRegistry`: `GET https://lores-us-east-1.brightspace.com/api/lores/1.0/registries/{id}` with the bearer token; 404 is `null`; anything else is a `ResponseException`.
- [ ] `UpdateRegistry`: `PUT` to the same URL with the `Host` header and `{"objectives": [...]}` holding ids and children only; non-200 is a `ResponseException`.
- [ ] `GetRegistryId`: `GET https://<host>/d2l/le/lo/{ou}/outcomes-management` with the session cookies and no bearer token; runs the web login first when there is no session; reads `registry-id` off `<d2l-outcomes-management>`; a page without the element or the attribute is a `ResponseException` whose cause names what is missing; non-200 is a `ResponseException`.
- [ ] `OutcomeApi`: `getId()` scrapes the page; `get(string)` goes straight to the registry; `get(int)` scrapes first; `update()` puts the registry.

## Consuming the package

- [ ] In a scratch project, `composer require gsu-cetloe/brightspace-api-php` installs `gsu-cetloe/brightspace-api-core-php` and its gadget dependencies.
- [ ] Against a real Brightspace instance, with the clients built as the core README describes, `UserApi::whoAmI()` returns the service account's record and the request went to `/d2l/api/lp/<latest lp version>/users/whoami`.
- [ ] Against that instance, `UserApi::get('<a known user name>')` returns that user, and `UserApi::get(<an id that does not exist>)` returns `null`.
- [ ] Against that instance, `OrgUnitApi::getRoot()` returns the org with a `PrimaryUrl`, and `OrgUnitApi::listTypes()` lists the instance's org unit types. Compare the ids against the `OrgUnitType` constants and note any that differ.
- [ ] Against that instance, `EnrollmentApi::listOrgUnits(<a user id>)` yields that user's enrollments, each with `User` filled in, and the request log shows exactly one `/users/<id>` call.
- [ ] Against that instance, with an account that can open Outcomes Management for a course, `OutcomeApi::getId(<that course's org unit id>)` returns a registry id and `OutcomeApi::get()` with that id returns a registry.
- [ ] In a scratch Symfony app with the five bundles enabled and the `D2L_*` variables set, a service that type-hints `Brightspace\Api\BrightspaceApi` autowires and `->user->whoAmI()` works.

## Known gaps

- The transport in the suite is Guzzle's `MockHandler`. The requests Guzzle would send are checked exactly, but nothing goes over a socket and no real Brightspace instance is involved. The manual steps above cover the real wire.
- The fixtures are what the handlers expect, not captures from a real instance. If Brightspace sends a field as `null` that a model reads with `Cast::toString` or `Cast::toInt` (for example a null `Pronouns`, or a type with no `Code`), `create()` throws a `CastException`. Whether a real instance ever does that has not been checked.
- `UserApi::get(string)` returns `null` only when the lookup body is the JSON `null`. Brightspace answers an unknown user name with a 404, and that comes out as a `ResponseException`, not `null`. The suite pins the current behavior.
- `GetPrimaryUrl` returns the response body as-is. If Brightspace returns a JSON string, the quotes are part of the value.
- The LORES host for outcomes is hard-coded to `lores-us-east-1.brightspace.com`.
- `OrgUnitType::GROUP` and `OrgUnitType::SECTION` are both `4`. The constants were not checked against D2L's documentation; the manual step above records what the instance says.
- `OutcomeRegistry::merge()` treats the registry it is called on as the target. The nested merge in the original code had the two sides swapped, so children were never added; this was fixed and the exception message now reads "merging <source> into <target>".
- Tests run on whatever PHP is installed locally. Composer resolves dependencies as if PHP were 8.1.0 and PHPStan analyzes for 8.1, but the behavior checked here is that of the local interpreter.
