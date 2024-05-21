<?php

namespace Budgetcontrol\Authentication\Controller;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Authentication\Traits\AuthFlow;
use Psr\Http\Message\ResponseInterface as Response;
use Budgetcontrol\Authentication\Domain\Model\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Authentication\Exception\AuthException;
use Budgetcontrol\Authentication\Facade\AwsCognitoClient;
use Budgetcontrol\Authentication\Traits\Crypt;

class AuthController
{
    use AuthFlow, Crypt;

    public function check(Request $request, Response $response, array $args)
    {
        $authToken = $request->getHeader('Authorization')
            ? $request->getHeader('Authorization')[0]
            : null;
        
        if(!$authToken) {
            throw new AuthException('Missing Authorization header', 401);
        }
        $authToken = str_replace('Bearer ', '', $authToken);
        $decodedToken = AwsCognitoClient::decodeAccessToken($authToken);
        
        // Check if the token has expired
        if (isset($decodedToken['exp']) && $decodedToken['exp'] < time()) {
            try {
                $refresh_token = Cache::get($decodedToken['sub'].'refresh_token');
                $tokens = AwsCognitoClient::refreshAuthentication($decodedToken['username'], $refresh_token);
                $authToken = $tokens['AccessToken'];
            } catch (\Throwable $e) {
                throw new AuthException('Token has expired', 401);
            }
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

        //get Worksace UUID from the request
        $workspaceUUID = $request->getHeader('X-WS')[0];
        
        $authToken = str_replace('Bearer ', '', $authToken);
        $decodedToken = AwsCognitoClient::decodeAccessToken($authToken);

        $idToken = Cache::get($decodedToken['sub'].'id_token');
        if(empty($idToken)) {
            throw new AuthException('Invalid id token token', 401);
        }
        $decodedIdToken = AwsCognitoClient::decodeAccessToken($idToken);

        $user = User::where("mail", $decodedIdToken['mail'])->first();
        $userId = $user->id;

        $user = User::find($userId);
        $workspace = DB::select(
            'select * from workspaces as w 
            inner join workspaces_users_mm as ws on ws.workspace_id = w.id
            where ws.user_id = ?',
            [$userId]
        );

        $active = '';
        $settings = [];
        // get the current workspace
        foreach ($workspace as $value) {
            if ($value->uuid == $workspaceUUID) {
                $active = $value->uuid;
                $currentWsId = $value->workspace_id;
                $settings = DB::select(
                    "select * from workspace_settings where workspace_id = $currentWsId"
                );
                break;
            }
        }

        if(empty($settings)) {
            throw new AuthException('Workspace settings not found', 404);
        }

        $result = array_merge($user->toArray(), ['workspaces' => $workspace], ['current_ws' =>  $active], ['workspace_settings' => $settings[0]] );
        // save in cache
        Cache::put($decodedToken['sub'].'user_info', $result, Carbon::now()->addDays(1));
        
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

        $user = User::where('email', $this->encrypt($email))->first();
        if ($user) {
            AwsCognitoClient::setUserPassword($email, $newPassword, true);
            $user->password= $newPassword;
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
        $user = User::where('email', $this->encrypt($email))->first();
        if ($user) {
            $token = $this->generateToken(['email' => $email], $user->id, 'verify_email');
            $mail = new \Budgetcontrol\Authentication\Service\MailService();
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
        $user = User::where('email', $this->encrypt($email))->first();
        if ($user) {
            $token = $this->generateToken(['email' => $email], $user->id, 'reset_password');
            $mail = new \Budgetcontrol\Authentication\Service\MailService();
            $mail->send_resetPassowrdMail($email, $user->name, $token);
        }

        return response([
            'message' => 'Email sent'
        ], 200);
    }

    /**
     * Retrieves user information by email.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return Response The HTTP response object.
     */
    public function userInfoByEmail(Request $request, Response $response, array $args)
    {
        $email = $args['email'];
        $user = User::where('email', $this->encrypt($email))->first();
        if (!$user) {
            throw new AuthException('User not found', 404);
        }

        return response(
            $user->toArray()
        , 200);
    }
}
