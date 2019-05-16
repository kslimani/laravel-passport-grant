<?php

namespace Tests;

use Illuminate\Support\Facades\Event;
use Laravel\Passport\Events\RefreshTokenCreated;
use Laravel\Passport\Events\AccessTokenCreated;
use Sk\Passport\GrantTypesServiceProvider;

class PassportGrantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPassport();
    }

    public function test_it_not_grant()
    {
        Event::fake();
        Event::assertNotDispatched(RefreshTokenCreated::class);
        Event::assertNotDispatched(AccessTokenCreated::class);

        $response = $this->json('POST', '/oauth/token', [
            'grant_type' => 'acme',
            'client_id' => $this->grantClient->id,
            'client_secret' => $this->grantClient->secret,
            'acme_name' => 'Joe',
            'acme_score' => 1337,
            'scope' => '',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'unsupported_grant_type',
            ]);

        Event::assertNotDispatched(RefreshTokenCreated::class);
        Event::assertNotDispatched(AccessTokenCreated::class);
    }

    public function test_it_grant()
    {
        // Enable "acme" grant type
        $this->app->config->set('passportgrant.grants', [
            'acme' => AcmeUserProvider::class,
        ]);

        Event::fake();
        Event::assertNotDispatched(RefreshTokenCreated::class);
        Event::assertNotDispatched(AccessTokenCreated::class);

        $response = $this->json('POST', '/oauth/token', [
            'grant_type' => 'acme',
            'client_id' => $this->grantClient->id,
            'client_secret' => $this->grantClient->secret,
            'acme_name' => 'Joe',
            'acme_score' => 1337,
            'scope' => '',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'token_type' => 'Bearer',
            ])
            ->assertJsonStructure([
                'token_type',
                'expires_in',
                'access_token',
                'refresh_token',
            ]);

        Event::assertDispatched(RefreshTokenCreated::class);
        Event::assertDispatched(AccessTokenCreated::class);
    }
}
