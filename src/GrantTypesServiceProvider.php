<?php

namespace Sk\Passport;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;

class GrantTypesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/passportgrant.php' => $this->app->configPath().'/passportgrant.php',
        ], 'config');
    }

    public function register()
    {
        $this->app
            ->afterResolving(AuthorizationServer::class, function (AuthorizationServer $server) {
                $this->makeGrants($server);
            });
    }

    public function makeGrants(AuthorizationServer $server)
    {
        $grants = $this->app->config->get('passportgrant.grants', []);

        foreach ($grants as $grantType => $userProviderClass) {
            $grant = new Grant(
                $grantType,
                $this->app->make($userProviderClass),
                $this->app->make(RefreshTokenRepository::class)
            );
            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
            $server->enableGrantType($grant, Passport::tokensExpireIn());
        }
    }
}
