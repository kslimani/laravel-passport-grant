<?php

namespace Sk\Passport;

use DateInterval;
use Illuminate\Contracts\Auth\Authenticatable;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Laravel\Passport\Bridge\User;
use Psr\Http\Message\ServerRequestInterface;

class Grant extends AbstractGrant
{
    /**
     * @var string
     */
    protected $grantType;

    /**
     * @var \Sk\Passport\UserProviderInterface
     */
    protected $userProvider;

    /**
     * Create new grant type handler.
     *
     * @param  string  $grantType
     * @param  \Sk\Passport\UserProviderInterface  $userProvider
     * @param  \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface  $refreshTokenRepository
     */
    public function __construct(
        $grantType,
        UserProviderInterface $userProvider,
        RefreshTokenRepositoryInterface $refreshTokenRepository
    ) {
        $this->grantType = $grantType;
        $this->userProvider = $userProvider;
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->refreshTokenTTL = new DateInterval('P1M');
    }

    /**
     * Ensures user instance is valid.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @param  mixed  $user
     * @return \League\OAuth2\Server\Entities\UserEntityInterface
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */
    protected function validateUserEntity(ServerRequestInterface $request, $user)
    {
        if ($user instanceof Authenticatable) {
            $user = new User($user->getAuthIdentifier());
        }

        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ) {
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));

        // Assume it throws invalid request OAuth server exception on failure
        $this->userProvider->validate($request);

        $user = $this->userProvider->retrieve($request);
        $user = $this->validateUserEntity($request, $user);

        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist new access token
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $finalizedScopes);
        defined('League\OAuth2\Server\RequestEvent::ACCESS_TOKEN_ISSUED') && $this->getEmitter()->emit(new RequestEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request));
        $responseType->setAccessToken($accessToken);

        // Issue and persist new refresh token if given
        $refreshToken = $this->issueRefreshToken($accessToken);
        if ($refreshToken !== null) {
            defined('League\OAuth2\Server\RequestEvent::REFRESH_TOKEN_ISSUED') && $this->getEmitter()->emit(new RequestEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request));
            $responseType->setRefreshToken($refreshToken);
        }

        return $responseType;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->grantType;
    }
}
