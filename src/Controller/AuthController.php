<?php

namespace Budgetcontrol\Authtentication\Controller;

use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Budgetcontrol\Authtentication\Domain\Model\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Authtentication\Exception\AuthException;
use Budgetcontrol\Authtentication\Facade\AwsCognitoClient;
use Budgetcontrol\Authtentication\Service\AwsClientService;
use Budgetcontrol\Authtentication\Traits\AuthFlow;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AuthController
{
    use AuthFlow;

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

    /**
     * Resets the password for a user.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return Response The updated HTTP response object.
     */
    public function resetPassword(Request $request, Response $response, array $args)
    {
        try {
            Validator::validate([
                'name' => 'required|max:255',
                'email' => 'required|email|max:64|unique:users',
                'password' => 'sometimes|confirmed|min:6|max:64|regex:' . SignUpController::PASSWORD_VALIDATION,
            ]);
        } catch (\Throwable $e) {
            return response(['error' => $e->getMessage()], 400);
        }

        $email = $request->getParsedBody()['email'];
        $newPassword = $request->getParsedBody()['password'];
        $token = $args['token'];

        if(!Cache::has($token)) {
            throw new AuthException('Invalid token', 401);
        }

        $user = User::where('email', sha1($email))->first();
        if ($user) {
            AwsCognitoClient::setUserPassword($email, $newPassword, true);
            $user->password=sha1($newPassword);
            $user->save();
        }

        return response([], 200);
    }

    /**
     * Sends a verification email.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route arguments.
     * @return void
     */
    public function sendVerifyEmail(Request $request, Response $response, array $args)
    {
        $email = $request->getParsedBody()['email'];
        $user = User::where('email', sha1($email))->first();
        if ($user) {
            $token = $this->generateToken(['email' => $email], $user->id, 'verify_email');
            $mail = new \Budgetcontrol\Authtentication\Service\MailService();
            $mail->send_signUpMail($email, $user->name, $token);
        }

        return response([
            'message' => 'Email sent'
        ], 200);
    }

    /**
     * Sends a reset password email.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return void
     */
    public function sendResetPasswordMail(Request $request, Response $response, array $args)
    {
        $email = $request->getParsedBody()['email'];
        $user = User::where('email', sha1($email))->first();
        if ($user) {
            $token = $this->generateToken(['email' => $email], $user->id, 'reset_password');
            $mail = new \Budgetcontrol\Authtentication\Service\MailService();
            $mail->send_resetPassowrdMail($email, $user->name, $token);
        }

        return response([
            'message' => 'Email sent'
        ], 200);
    }
}
