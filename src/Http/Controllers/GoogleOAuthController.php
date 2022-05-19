<?php

namespace DmLogic\GooglePhotoIndex\Http\Controllers;

use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use DmLogic\GooglePhotoIndex\GoogleOAuth;

class GoogleOAuthController extends Controller
{
    public function __construct()
    {
        if (!config('photos.oauth.client_secret')) {
            abort(500, 'No credentials file found');
        }
    }

    public function start(Request $request): Response
    {
        $markup = sprintf(
            '<p>When you\'re ready,</p> <form action="%s" method="post"><button>Click here to start</button></form>',
            route('oauth.generate')
        );
        return response($markup);
    }

    public function generateRequest(GoogleOAuth $googleClient): RedirectResponse
    {
        $authUrl = $googleClient->getAuthenticationUrl();
        return redirect($authUrl);
    }

    public function handleRedirect(Request $request, GoogleOAuth $googleClient): Response
    {
        try {
            $accessDetails = $googleClient->authenticate($request->input('code'));
        } catch (RuntimeException $e) {
            return response($e->getMessage(), 400);
        }
        Storage::disk('local')->put('oauth.access', json_encode($accessDetails));
        return response('new access details saved to disk');
    }
}
