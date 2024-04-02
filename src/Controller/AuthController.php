<?php

namespace Budgetcontrol\Authtentication\Controller;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\Authtentication\Domain\Model\User;
use Psr\Http\Message\ResponseInterface as Response;
use Budgetcontrol\Authtentication\Exception\AuthException;
use Budgetcontrol\Authtentication\Service\AwsClientService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

class AuthController
{

    public function check(Request $request, Response $response, array $args)
    {

        $authToken = $request->getHeader('Authorization')
            ? $request->getHeader('Authorization')[0]
            : null;
        
        if(!$authToken) {
            throw new AuthException('Missing Authorization header', 401);
        }
        $authToken = str_replace('Bearer ', '', $authToken);

        $decodedToken = AwsClientService::decodeAuthToken($authToken);
        
        // Check if the token has expired
        if (isset($decodedToken['exp']) && $decodedToken['exp'] < time()) {
            $refreshToken = new AwsClientService($decodedToken['username']);
            $authToken = $refreshToken->refreshCognitoToken($decodedToken['refresh_token']);
        }

        return response(
            ['message' => 'Token is valid'],
            200,
            ['Content-Type' => 'application/json', 'Authorization' => $authToken]
        );
    }

    public function authUserInfo(Request $request, Response $response, array $args)
    {   
        $authToken = $request->getHeader('Authorization')
            ? $request->getHeader('Authorization')[0]
            : null;
        
        if(!$authToken) {
            throw new AuthException('Missing Authorization header', 401);
        }
        
        $authToken = str_replace('Bearer ', '', $authToken);

        $decodedToken = AwsClientService::decodeAuthToken($authToken);
        $user = User::where("sub", $decodedToken['sub'])->first();
        $userId = $user->id;

        $user = User::find($userId);
        $workspace = DB::select(
            'select * from workspaces as w 
            inner join workspaces_users_mm as ws on ws.workspace_id = w.id
            where ws.workspace_id = ?',
            [$userId]
        );

        $active = '';
        $settings = [];
        // get the current workspace
        foreach ($workspace as $value) {
            if ($value->active == 1) {
                $active = $value->uuid;
                $currentWsId = $value->id;
                $settings = DB::select(
                    "select * from workspace_settings where id = $currentWsId"
                );
                break;
            }
        }

        if(empty($settings)) {
            throw new AuthException('Workspace settings not found', 404);
        }

        $result = array_merge($user->toArray(), ['workspaces' => $workspace], ['current_ws' =>  $active], ['workspace_settings' => $settings[0]] );
        return response($result, 200);
    }
}
