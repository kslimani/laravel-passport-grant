<?php

namespace Sk\Passport;

use Illuminate\Support\Facades\Validator;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

abstract class UserProvider implements UserProviderInterface
{
    /**
     * Get all request inputs.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return array
     */
    public function inputs(ServerRequestInterface $request)
    {
        return (array) $request->getParsedBody();
    }

    /**
     * Get a subset containing the provided keys with values from the request inputs.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @param  array  $keys
     * @return array
     */
    public function only(ServerRequestInterface $request, array $keys)
    {
        $inputs = $this->inputs($request);
        $filtered = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $inputs)) {
                $filtered[$key] = $inputs[$key];
            }
        }

        return $filtered;
    }

    /**
     * Create request validator.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @param  array  $rules
     * @return \Illuminate\Validation\Validator
     */
    public function makeValidator(ServerRequestInterface $request, array $rules)
    {
        return Validator::make(
            $this->inputs($request),
            $rules
        );
    }

    /**
     * Trigger an invalid OAuth request parameter exception.
     *
     * @param  string  $name
     * @param  string  $hint
     * @param  \Throwable  $error
     * @return void
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */
    public function invalidRequestParameter($name, $hint = null, Throwable $error = null)
    {
        throw OAuthServerException::invalidRequest($name, $hint, $error);
    }

    /**
     * Validate request parameters.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @param  array  $rules
     * @return void
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */
    public function validateRequest(ServerRequestInterface $request, array $rules)
    {
        $validator = $this->makeValidator($request, $rules);

        if ($validator->fails()) {
            // Set first validation error message as hint
            $firstKey = $validator->errors()->keys()[0];

            $this->invalidRequestParameter(
                $firstKey,
                $validator->errors()->first($firstKey)
            );
        }
    }
}
