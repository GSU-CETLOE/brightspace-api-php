# brightspace-api-php

Typed PHP clients for the parts of the D2L Brightspace REST API we use at Georgia State: users, enrollments, org units, and learning outcome registries. Each call is a small `MessageHandler` class that builds the request and turns the JSON into a model object, and each area has an API class that wraps those handlers in plain methods. A `BrightspaceApi` object holds the four API classes so a Symfony service can type-hint one thing. The login, token refresh, and `d2l://` URL rewriting all come from [brightspace-api-core-php](https://github.com/GSU-CETLOE/brightspace-api-core-php), which this package builds on.

CETLOE at Georgia State University maintains this package. It started as `mjfk23/brightspace-api`, written by Melody Kimball.

## Requirements

- PHP 8.1
- `gsu-cetloe/brightspace-api-core-php` 1.x, which Composer pulls in for you. Its README explains the `Config` object, the web login, and how to build the HTTP client and cache it needs.
- A Brightspace account that can sign in through the web login page, and an OAuth 2.0 application registered in Brightspace. The outcomes calls also need the account to be able to open the Outcomes Management page in the web UI.

## Setup

Install [Composer](https://getcomposer.org/) if you don't have it, then add the package to your project:

```sh
composer require gsu-cetloe/brightspace-api-php
```

To work on the library itself:

```sh
git clone https://github.com/GSU-CETLOE/brightspace-api-php.git
cd brightspace-api-php
composer install
```

Your machine does not have to run PHP 8.1. Composer resolves dependencies as if it were PHP 8.1.0 no matter what version runs it, so the lock file always reflects what the library targets. Before a release, run the suite on an actual 8.1 build.

## Development commands

| Command | What it does |
| --- | --- |
| `composer test` | Runs the PHPUnit suite in `tests/`. |
| `composer phpstan` | Runs PHPStan at level 10 over `src/`, `bundle/`, and `tests/`. |
| `composer phpcs` | Checks `src/`, `bundle/`, and `tests/` against PSR-12. |
| `composer check` | Runs all three in order. Run this before you commit. |

Anything generated, such as the PHPStan and PHPUnit caches, lands in `working/`. Git ignores that directory.

## Building the clients

Every API class takes the same three things the core package's `ApiClient` takes: a gadget-http `Client`, a `Config`, and a `CoreClient`. The core README shows how to build those. From there:

```php
use Brightspace\Api\BrightspaceApi;
use Brightspace\Api\Enrollment\EnrollmentApi;
use Brightspace\Api\OrgUnit\OrgUnitApi;
use Brightspace\Api\Outcome\OutcomeApi;
use Brightspace\Api\User\UserApi;

$api = new BrightspaceApi(
    new EnrollmentApi($http, $config, $core),
    new OrgUnitApi($http, $config, $core),
    new OutcomeApi($http, $config, $core),
    new UserApi($http, $config, $core)
);

$me = $api->user->whoAmI();
```

If you only need one area, build that one class and skip `BrightspaceApi`.

## Users

```php
$me = $api->user->whoAmI();              // WhoAmI
$user = $api->user->get(42);             // User, or null on a 404
$user = $api->user->get('jdoe');         // looks the user name up
$user = $api->user->create($newUser);    // POST, returns the created User
$user = $api->user->update($user);       // PUT to /users/{UserId}
$ok = $api->user->delete(42);
```

`list()` takes four optional filters and Brightspace treats them very differently, so the handler does too. With no filters it walks the paged `/users/` endpoint and yields every user, keyed by `UserId`. With `userName` it reads the single record Brightspace returns for that name. With `orgDefinedId` or `externalEmail` it reads the unpaged list Brightspace returns for those. The filters have a pecking order: `orgDefinedId` wins over everything, then `userName`, then `externalEmail`, and the loser is dropped from the request. `bookmark` only means something for the unfiltered list.

```php
foreach ($api->user->list() as $userId => $user) {
    // every user in the org, one page at a time
}

foreach ($api->user->list(externalEmail: 'jdoe@example.edu') as $user) {
    // ...
}
```

`User` has public properties named the way Brightspace names them (`UserId`, `OrgDefinedId`, `Activation->IsActive`, and so on). `getCreatePayload()` adds `IsActive`, `RoleId`, and `SendCreationEmail` for a POST; `getUpdatePayload()` sends the `Activation` block instead. `LastAccessedDate` is a `DateTimeInterface` or `null`.

## Org units

```php
$root = $api->orgUnit->getRoot();        // RootOrgUnit, with PrimaryUrl filled in by a second call
$ou = $api->orgUnit->get(1234);          // OrgUnit, or null
$types = $api->orgUnit->listTypes();     // keyed by type id
$type = $api->orgUnit->getType(3);

$api->orgUnit->list(orgUnitType: 3, exactOrgUnitCode: 'ENGL-1101-2026SP');
$api->orgUnit->listAncestors(1234);      // every ancestor, unpaged
$api->orgUnit->listParents(1234);        // direct parents only
$api->orgUnit->listDescendants(1234);    // every descendant, paged
$api->orgUnit->listChildren(1234, 3);    // direct children of one type, paged

$api->orgUnit->createRelationship(childOrgUnitId: 1234, parentOrgUnitId: 5678);
$api->orgUnit->deleteRelationship(1234, 5678);
```

Every list method yields `OrgUnit` objects keyed by `Identifier`. In `list()`, passing `exactOrgUnitCode` drops `orgUnitCode` from the request, and `exactOrgUnitName` does the same to `orgUnitName`.

`OrgUnitType` carries constants for the built-in type ids (`ORGANIZATION`, `COURSE_OFFERING`, `SEMESTER`, and the rest). Check them against your instance before relying on them; see the test plan.

## Enrollments

```php
$api->enrollment->listOrgUnits($user, orgUnitType: [3, 4], roleId: 110);  // keyed by org unit id
$api->enrollment->listUsers($orgUnit, roleId: 110, isActive: true);      // keyed by user id
$enrollment = $api->enrollment->get($orgUnit, $user);                    // UserEnrollment, or null

$api->enrollment->enroll(orgUnitId: 1234, userId: 42, roleId: 110);
$api->enrollment->unenroll(1234, 42);
$api->enrollment->pin(1234, 42);
$api->enrollment->unpin(1234, 42);
```

`listOrgUnits`, `listUsers`, and `get` accept either an id or the model object. Brightspace's enrollment responses only carry one side of the pair, so the handler fills in the other: `listOrgUnits(42)` fetches user 42 once and attaches it to every `UserEnrollment` it yields, and `get(1234, 42)` fetches both the org unit and the user. Pass the objects you already have and those extra requests go away. Each `UserEnrollment` has `OrgUnit`, `User`, and `Role`.

## Outcomes

The learning outcomes registry does not live behind the normal REST API. The registry id is only visible on the Outcomes Management page, and the registry itself is served by D2L's LORES service.

```php
$registryId = $api->outcome->getId(1234);      // scrapes /d2l/le/lo/1234/outcomes-management
$registry = $api->outcome->get(1234);          // getId() then the registry, or null
$registry = $api->outcome->get($registryId);   // straight to the registry
$ok = $api->outcome->update($registry);
```

`getId()` signs in to the web UI through the core package's login flow, reads the page, and pulls the `registry-id` attribute off the `<d2l-outcomes-management>` element. If D2L changes that page, this breaks; the error message says which part went missing. `get()` and `update()` talk to `lores-us-east-1.brightspace.com`, which is hard-coded. If your instance is in another region, that will need to change.

`OutcomeRegistry` is a tree of `OutcomeDetail` objects, each with `id`, `description`, `children`, and `parentId`. Two helpers work on the tree:

- `$target->merge($source)` adds every outcome in `$source` that `$target` is missing, at any depth, and returns `true` if anything was added.
- `$a->equals($b)` is `true` when both hold the same ids with the same children, in any order.

`update()` sends only ids and children; descriptions live on the outcomes themselves and are not part of the registry PUT.

## Symfony bundle

Add the bundle after the core bundle in `config/bundles.php`:

```php
return [
    // ...
    Gadget\Cache\GadgetCacheBundle::class => ['all' => true],
    Gadget\Http\GadgetHttpBundle::class => ['all' => true],
    Gadget\Oauth\GadgetOauthBundle::class => ['all' => true],
    Brightspace\Api\Core\BrightspaceApiCoreBundle::class => ['all' => true],
    Brightspace\Api\BrightspaceApiBundle::class => ['all' => true],
];
```

It registers everything under `Brightspace\Api\` in `src/` as autowired, autoconfigured services. Type-hint `BrightspaceApi` or any of the four API classes and Symfony builds them on top of the core bundle's `Config` and `CoreClient`, which read the `D2L_*` environment variables described in the core README.

## Errors and paging

| What happened | What you get |
| --- | --- |
| A `get()` call hit a 404 | `null` |
| Any other non-200 on a single-record call | `Gadget\Http\Exception\ResponseException`, with a `RuntimeException` as `getPrevious()` |
| A non-200 on a list call | A `RuntimeException` thrown when you start iterating, not when you call the method |
| The outcomes page could not be parsed | `ResponseException` whose cause names the missing element or attribute |

List methods return generators. Nothing is requested until you iterate, and the next page is requested when the current one runs out. `iterator_to_array()` works if you want the whole thing at once; the keys are ids, so they stay unique.

The `RuntimeException` these handlers throw for a bad status carries no message. If you need the status code, it is in the `ResponseException` message that wraps it.

## Testing

`composer test` runs the suite. Every handler and API class goes through a real gadget-http `Client` over Guzzle with a `MockHandler` transport, so the tests check the exact requests, query strings, JSON bodies, and headers, and nothing goes over a socket. The extension test compiles a real container on top of the core and gadget-http extensions. [TESTPLAN.md](TESTPLAN.md) has the manual walkthrough, including the steps against a real Brightspace instance.

## License

MIT. See [LICENSE](LICENSE).
