<?php

namespace Tests;

use Illuminate\Foundation\Auth\User;
use Psr\Http\Message\ServerRequestInterface;
use Sk\Passport\UserProvider;

class AcmeUserProvider extends UserProvider
{
    /**
     * Validate request parameters.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return void
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */
    public function validate(ServerRequestInterface $request)
    {
        $this->validateRequest($request, [
            'acme_name' => ['required', 'string', 'min:1'],
            'acme_score' => ['required', 'integer', 'min:1'],
        ]);
    }

    /**
     * Retrieve user entity instance from request.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return mixed|null
     */
    public function retrieve(ServerRequestInterface $request)
    {
        $inputs = $this->only($request, [
            'acme_score',
            'acme_name',
        ]);

        // Mock Eloquent user entity
        $user = User::make()
            ->forceFill([
                'id' => $inputs['acme_score'],
                'name' => $inputs['acme_name'],
            ]);

        return $user;
    }
}
