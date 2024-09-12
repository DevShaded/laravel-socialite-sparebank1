<?php

namespace SpareBank1;

use Exception;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class SpareBank1Provider extends AbstractProvider implements ProviderInterface
{
    protected string $authUrl = 'https://api.sparebank1.no/oauth/authorize';
    protected string $tokenUrl = 'https://api.sparebank1.no/oauth/token';
    protected string $userInfoUrl = 'https://api.sparebank1.no/common/user/info';

    public function __construct(protected string $fi)
    {
        parent::__construct(
            request(),
            config('services.sb1.client_id'),
            config('services.sb1.client_secret'),
            config('services.sb1.redirect')
        );
        $this->fi = config('services.sb1.finInstId');
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->authUrl, $state) . '&' . http_build_query([
                'client_id' => $this->clientId,
                'state' => mt_rand(1000000, 9999999),
                'redirect_uri' => $this->redirectUrl,
                'finInst' => $this->fi,
                'response_type' => 'code',
            ]);
    }

    protected function getTokenUrl(): string
    {
        return $this->tokenUrl;
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->userInfoUrl, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        if ($response->getStatusCode() === 401) {
            throw new Exception('Token is either invalid or expired');
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'] ?? null,
            'name' => $user['name'] ?? null,
            'nickname' => $user['nickname'] ?? null,
            'first_name' => $user['firstname'],
            'last_name' => $user['lastname'],
            'email' => $user['email'],
            'sub' => $user['sub'],
            'dob' => $user['dateOfbirth'],
            'phone' => $user['mobilePhoneNumber'],
        ]);
    }

    public function getAccessTokenResponse($code): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUrl,
            ],
        ])->getBody()->getContents();

        return json_decode($response, true);
    }

    public function user(): User|\Laravel\Socialite\Contracts\User|null
    {
        $code = request('code');
        $accessTokenResponse = $this->getAccessTokenResponse($code);

        $token = $accessTokenResponse['access_token'];
        $refreshToken = $accessTokenResponse['refresh_token'] ?? null;
        $expiresIn = $accessTokenResponse['expires_in'] ?? null;

        $userData = $this->getUserByToken($token);
        $user = $this->mapUserToObject($userData);

        $user->setToken($token)
            ->setRefreshToken($refreshToken)
            ->setExpiresIn($expiresIn);

        return $user;
    }
}
