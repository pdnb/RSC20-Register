<?php

namespace App\Auth;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class Auth0Provider extends AbstractProvider
{
    /**
     * @var list<string>
     */
    protected $scopes = ['openid', 'profile', 'email'];

    /**
     * @var string
     */
    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->baseUrl().'/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->baseUrl().'/oauth/token';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->baseUrl().'/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        /** @var array<string, mixed> $user */
        $user = json_decode((string) $response->getBody(), true) ?? [];

        return $user;
    }

    /**
     * @param  array<string, mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        $name = trim((string) ($user['name'] ?? ''));

        if ($name === '') {
            $name = trim(((string) Arr::get($user, 'given_name', '')).' '.((string) Arr::get($user, 'family_name', '')));
        }

        if ($name === '') {
            $name = (string) ($user['nickname'] ?? $user['email'] ?? '');
        }

        return (new User)->setRaw($user)->map([
            'id' => $user['sub'] ?? null,
            'nickname' => $user['nickname'] ?? null,
            'name' => $name !== '' ? $name : null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['picture'] ?? null,
        ]);
    }

    private function baseUrl(): string
    {
        $domain = rtrim((string) config('services.auth0.domain'), '/');

        if (str_starts_with($domain, 'https://') || str_starts_with($domain, 'http://')) {
            return $domain;
        }

        return 'https://'.$domain;
    }
}
