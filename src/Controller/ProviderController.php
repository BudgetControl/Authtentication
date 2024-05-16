<?php
namespace Budgetcontrol\Authtentication\Controller;

use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Budgetcontrol\Authtentication\Facade\Cache;
use Psr\Http\Message\ResponseInterface as Response;
use Budgetcontrol\Authtentication\Domain\Model\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Authtentication\Domain\Entity\Provider;
use Budgetcontrol\Authtentication\Facade\AwsCognitoClient;
use Illuminate\Support\Facades\Log;

class ProviderController {

    /**
     * Authenticates the provider.
     *
     * @param Request $request The request object.
     * @param Response $response The response object.
     * @param array $args The arguments passed to the method.
     * @return void
     */
    public function authenticateProvider(Request $request, Response $response, array $args)
    {
        $providerName = $args['provider'];

        try {
            $provider = AwsCognitoClient::provider();
            $uri = $provider->$providerName(env('COGNITO_REDIRECT_URI'));

        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            return response([
                'success' => false,
                'message' => "Provider not found"
            ], 400);
        }

        return response([
            'success' => true,
            'uri' => $uri
        ]);
    }

    /**
     * Handles the provider token request.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return void
     */
    public function providerToken(Request $request, Response $response, array $args)
    {
        $provider = $args['provider'];
        if(!$request->getQueryParams()['code']) {
            return response([
                'success' => false,
                'message' => 'Missing code'
            ], 400);
        }

        try {
            $authResponse = $this->authenticate($request->getQueryParams()['code'],$provider);

        } catch (\Throwable $e) {
            return response([
                'success' => false,
                'message' => "Authentication failed"
            ], 401);
        }

        return response([
            'success' => true,
            'message' => 'User authenticated',
            'token' => $authResponse['token'],
            'workspaces' => $authResponse['workspaces']
        ]);
    }

    /**
     * Authenticates the provided code.
     *
     * @param string $code The code to authenticate.
     * @return array The authentication result and workspace result.
     */
    private function authenticate(string $code, string $provider): array
    {
        $provider = AwsCognitoClient::provider();
        $params = $provider->getParams($provider);
        $tokens =AwsCognitoClient::authenticateProvider($code, $params['redirect_uri']);

        // Decode ID Token
        $content = AwsCognitoClient::decodeAccessToken($tokens['AccessToken']);
        $userEmail = $content['email'];

        $user = User::where('email', sha1($userEmail))->with('workspaces')->first();
        if(!$user) {
            $user = new User();
            $user->email = sha1($userEmail);
            $user->name = $content['name'];
            $user->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $user->sub = $content['sub'];
            $user->save();
        } else {
            // Update user information sub
            $user->sub = $content['sub'];
            $user->save();
        }

        Cache::put($user->sub.'refresh_token', $content['RefreshToken'], Carbon::now()->addDays(30));
        Cache::put($user->sub.'id_token', $content['IdToken'], Carbon::now()->addDays(30));
            
        return [
            'token' => $tokens['AccessToken'],
            'workspaces' => $user->workspaces
        ];
    }
}