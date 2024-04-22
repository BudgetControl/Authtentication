<?php

namespace Budgetcontrol\Authtentication\Controller;

use Budgetcontrol\Authtentication\Domain\Model\User;
use Carbon\Carbon;
use Budgetcontrol\Authtentication\Facade\Cache;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Authtentication\Facade\AwsCognitoClient;

class LoginController
{

    public function authenticate(Request $request, Response $response, array $args)
    {
        $user = $request->getParsedBody()['email'];
        $password = $request->getParsedBody()['password'];

        try {
            $userAuth = AwsCognitoClient::setBoolClientSecret()->authenticate($user, $password);
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

        $user = User::where('email', sha1($user))->first();

        // put refresh token in cache
        $refreshToken = $userAuth['RefreshToken'];
        Cache::put($user->sub.'refresh_token', $refreshToken, Carbon::now()->addDays(30));
        Cache::put($user->sub.'id_token', $refreshToken, Carbon::now()->addDays(30));
        
        return response([
            'success' => true,
            'message' => 'User authenticated',
            'token' => $userAuth['AccessToken'],
        ]);

    }
}
