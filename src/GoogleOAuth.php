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
        $this->oauthClient = new Client;
        $this->oauthClient->setAuthConfig(config('oauth'));
        $this->oauthClient->addScope($this->authScopes);
        $this->oauthClient->setRedirectUri(route('oauth.handle'));
        $this->oauthClient->setAccessType('offline');
        $this->oauthClient->setApprovalPrompt('force');
    }

    public function getAuthenticationUrl()
    {
        return $this->oauthClient->createAuthUrl();
    }

    public function getCredentials()
    {
        if (!$accessToken = Storage::disk('local')->get('oauth.access')) {
            throw new RuntimeException('No access token found');
        }
        $this->oauthClient->setAccessToken($accessToken);
        // if ($this->oauthClient->isAccessTokenExpired()) {
        //     return $this->oauthClient->fetchAccessTokenWithRefreshToken($this->oauthClient->getRefreshToken());
        // }
        // // return $this->oauthClient->getAccessToken();
        return new UserRefreshCredentials(
            $this->authScopes,
            [
                'client_id' => config('oauth.client_id'),
                'client_secret' => config('oauth.client_secret'),
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
