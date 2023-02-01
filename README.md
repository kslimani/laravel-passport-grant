![](https://github.com/kslimani/laravel-passport-grant/workflows/Integration%20tests/badge.svg) [![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

# laravel-passport-grant

This library provide a very simple way to add __custom grant types__ for [Laravel Passport](https://github.com/laravel/passport) OAuth2 server.

## Installation

Note: this documentation assumes [Laravel Passport installation](https://laravel.com/docs/master/passport#introduction) is completed.

To get started, install package via the Composer package manager :

```bash
composer require kslimani/laravel-passport-grant
```

Publish the `passportgrant.php` configuration file using `vendor:publish` Artisan command :

```bash
php artisan vendor:publish --provider="Sk\Passport\GrantTypesServiceProvider" --tag="config"
```

## Configuration

In your [config/passportgrant.php](https://github.com/kslimani/laravel-passport-grant/blob/master/config/passportgrant.php) configuration file, enable any custom grant types providing user provider class.

```php
// "grants" is an array of user provider class indexed by grant type

'grants' => [
    // 'acme' => 'App\Passport\AcmeUserProvider',
],
```

## User provider

User provider class roles are :

* validate `/oauth/token` request custom parameters
* provide user entity instance

User provider class must implements the `Sk\Passport\UserProviderInterface` :

```php
/**
 * Validate request parameters.
 *
 * @param  \Psr\Http\Message\ServerRequestInterface  $request
 * @return void
 * @throws \League\OAuth2\Server\Exception\OAuthServerException
 */
public function validate(ServerRequestInterface $request);

/**
 * Retrieve user instance from request.
 *
 * @param  \Psr\Http\Message\ServerRequestInterface  $request
 * @return mixed|null
 */
public function retrieve(ServerRequestInterface $request);
```

If request validation fails, the `validate()` method must throw a `League\OAuth2\Server\Exception\OAuthServerException` invalid parameter exception.

On success, the `retrieve()` method must return a `League\OAuth2\Server\Entities\UserEntityInterface` or `Illuminate\Contracts\Auth\Authenticatable` instance. Otherwise `null` on failure.

## User provider example

For convenience, the [UserProvider](https://github.com/kslimani/laravel-passport-grant/blob/master/src/UserProvider.php) class provide methods to validate and retrieve request custom parameters.

Therefore, creating a user provider becomes simple :

```php
<?php

namespace App\Passport;

use App\User;
use Psr\Http\Message\ServerRequestInterface;
use Sk\Passport\UserProvider;

class AcmeUserProvider extends UserProvider
{
    /**
     * {@inheritdoc}
     */
    public function validate(ServerRequestInterface $request)
    {
        // It is not necessary to validate the "grant_type", "client_id",
        // "client_secret" and "scope" expected parameters because it is
        // already validated internally.

        $this->validateRequest($request, [
            'acme_id' => ['required', 'integer', 'min:1'],
            'acme_name' => ['required', 'string', 'min:1'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve(ServerRequestInterface $request)
    {
        $inputs = $this->only($request, [
            'acme_id',
            'acme_name',
        ]);

        // Here insert your logic to retrieve user entity instance

        // For example, let's assume that users table has "acme_id" column
        $user = User::where('acme_id', $inputs['acme_id'])->first();

        return $user;
    }
}
```

## Token request example

Request an access token for "acme" grant type :

```php
// Assuming $http is \GuzzleHttp\Client instance

$response = $http->post('https://your-app.com/oauth/token', [
    'form_params' => [
        'grant_type' => 'acme',
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'acme_id' => 1337,
        'acme_name' => 'Joe',
        'scope' => '',
    ],
]);
```

## License

The MIT License (MIT). Please see [License File](https://github.com/kslimani/laravel-passport-grant/blob/master/LICENSE) for more information.
