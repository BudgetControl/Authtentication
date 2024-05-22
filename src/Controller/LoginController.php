<?php

namespace Budgetcontrol\Authentication\Controller;

use Carbon\Carbon;
use Budgetcontrol\Authentication\Facade\Cache;
use Budgetcontrol\Authentication\Traits\Crypt;
use Psr\Http\Message\ResponseInterface as Response;
use Budgetcontrol\Authentication\Domain\Model\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Authentication\Exception\AuthException;
use Budgetcontrol\Authentication\Facade\AwsCognitoClient;

class LoginController
{
    use Crypt;

    public function authenticate(Request $request, Response $response, array $args)
    {
        $user = $request->getParsedBody()['email'];
        $password = $request->getParsedBody()['password'];

        try {
            $userAuth = AwsCognitoClient::setBoolClientSecret()->authenticate($user, $password);

            // decode auth token
            $decodedToken = AwsCognitoClient::decodeAccessToken($userAuth['AccessToken']);
            $sub = $decodedToken['sub'];

            if (!empty($userAuth['error'])) {
                return response([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
        } catch (\Throwable $e) {
            return response([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        $cryptedMail = $this->encrypt($user);
        $user = User::where('email', $cryptedMail)->with('workspaces')->first();
        $user->sub = $sub;
        $user->save();

        // put refresh token in cache
        $refreshToken = $userAuth['RefreshToken'];
        $idToken = $userAuth['IdToken'];

        Cache::put($sub.'refresh_token', $refreshToken, Carbon::now()->addDays(30));
        Cache::put($sub.'id_token', $idToken, Carbon::now()->addDays(30));
        
        return response([
            'success' => true,
            'message' => 'User authenticated',
            'token' => $userAuth['AccessToken'],
            'workspaces' => $user->workspaces
        ]);

    }

    public function logout(Request $request, Response $response, array $args)
    {
        $authToken = $request->getHeader('Authorization')
            ? $request->getHeader('Authorization')[0]
            : null;
        
        if(!$authToken) {
            throw new AuthException('Missing Authorization header', 401);
        }
        
        $authToken = str_replace('Bearer ', '', $authToken);
        $decodedToken = AwsCognitoClient::decodeAccessToken($authToken);
        
        Cache::forget($decodedToken['sub'].'refresh_token');
        Cache::forget($decodedToken['sub'].'id_token');
        Cache::forget($decodedToken['sub'].'user_info');
        
        return response([
            'success' => true,
            'message' => 'User logged out'
        ]);
        
    }
}
