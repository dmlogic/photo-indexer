<?php

namespace DmLogic\GooglePhotoIndex;

use Google\Client;
use RuntimeException;
use Illuminate\Support\Facades\Storage;
use Google\Auth\Credentials\UserRefreshCredentials;

class GoogleOAuth
{
    private Client $oauthClient;

    /**
     * @see https://developers.google.com/photos/library/guides/authorization
     */
    private array $authScopes = [
        'https://www.googleapis.com/auth/photoslibrary.readonly',
    ];

    public function __construct()
    {
        $this->createGoogleClient(config('photos.oauth'), $this->authScopes, route('oauth.handle'));
    }

    public function createGoogleClient($config, $scopes, $redirectRoute): void
    {
        $this->oauthClient = new Client;
        $this->oauthClient->setAuthConfig($config);
        $this->oauthClient->addScope($scopes);
        $this->oauthClient->setRedirectUri($redirectRoute);
        $this->oauthClient->setAccessType('offline');
        $this->oauthClient->setApprovalPrompt('force');
    }

    public function getAuthenticationUrl(): string
    {
        return $this->oauthClient->createAuthUrl();
    }

    /**
     * The OAuth procedure must have been completed before this will work
     * @throws RuntimeException
     */
    public function getCredentials(): UserRefreshCredentials
    {
        if (!$accessToken = Storage::disk('local')->get('oauth.access')) {
            throw new RuntimeException('No access token found');
        }
        $this->oauthClient->setAccessToken($accessToken);

        return new UserRefreshCredentials(
            $this->authScopes,
            [
                'client_id' => config('photos.oauth.client_id'),
                'client_secret' => config('photos.oauth.client_secret'),
                'refresh_token' => $this->oauthClient->getRefreshToken(),
            ]
        );
    }

    public function authenticate(string $code): array
    {
        $this->oauthClient->fetchAccessTokenWithAuthCode($code);
        return $this->oauthClient->getAccessToken();
    }
}
