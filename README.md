# patreon-user-api

Fake API client for Patreon user data.

Since Patreon doesn't have a real API, and you definitely have 2fa enabled, you
can't log in with this package, so you need to log in with a real browser, copy
1 cookie value, and use that for auth:

```php
$client = new Client(new AuthSession("Cookie 'session_id'"));
```

And then you do a 'login' (but not really) and session check:

```php
$loggedIn = $client->logIn(); // bool
```

Or you can make your own real login implementation. See interface `Auth` and class `AuthSession`.

And then you can fetch your user data:

```php
$client->getPledges(); // Pledge[]

$client->getFollows(); // Follow[]

$client->getBills(year: 2020); // Bill[]
print_r($client->billableYears); // int[]
```

All of those will contain `Creator` objects, with `id` and `campaignId`, and you need those to:

```php
$follow = $client->follow($creator); // Follow

$client->unfollow($follow); // void
```
