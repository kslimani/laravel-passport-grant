<?php

namespace Sk\Passport;

use Psr\Http\Message\ServerRequestInterface;

interface UserProviderInterface
{
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
}
