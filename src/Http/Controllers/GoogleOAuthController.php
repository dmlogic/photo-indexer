<?php

namespace DmLogic\GooglePhotoIndex\Http\Controllers;

use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use DmLogic\GooglePhotoIndex\GoogleOAuth;

class GoogleOAuthController extends Controller
{
    public function __construct()
    {
        if (App::environment('production')) {
            abort(403);
        }
        if (!config('oauth.client_secret')) {
            return abort(500, 'No credentials file found');
        }
    }

    public function start(Request $request)
    {
        $markup = sprintf('<p>When you\'re ready,</p> <form action="%s" method="post"><button>Click here to start</button></form>', route('oauth.generate'));
        return response($markup);
    }

    public function generateRequest(GoogleOAuth $googleClient)
    {
        $authUrl = $googleClient->getAuthenticationUrl();
        return redirect($authUrl);
    }

    public function handleRedirect(Request $request, GoogleOAuth $googleClient)
    {
        try {
            $accessDetails = $googleClient->authenticate($request->input('code'));
        } catch (RuntimeException $e) {
            throw $e;
            return response($e->getMessage(), 400);
        }
        Storage::disk('local')->put('oauth.access', json_encode($accessDetails));
        return response('new access details saved to disk');
    }
}
