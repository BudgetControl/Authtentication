<?php

namespace Budgetcontrol\Authtentication\Controller;

/** ########## DOCUMENTATION
 * 
 * This class is responsible for handling the sign up functionality.
 * follow these steps to signup user
 * - 1. create user
 * - 2. create workspace entry
 * - 3. create account entry
 * - 4. create default settings
 */

use Budgetcontrol\Authtentication\Domain\Model\Token;
use Budgetcontrol\Authtentication\Domain\Model\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Authtentication\Traits\RegistersUsers;
use Budgetcontrol\Authtentication\Facade\AwsCognitoClient;
use Budgetcontrol\Authtentication\Traits\AuthFlow;
use Budgetcontrol\Connector\Factory\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use stdClass;

class SignUpController
{
    use RegistersUsers, AuthFlow;

    const URL_SIGNUP_CONFIRM = '/app/auth/confirm/';
    const PASSWORD_VALIDATION = '/^(?=.*[0-9])(?=.*[!@#$%^&*])(?=.*[A-Z])(?=.*[a-z]).{8,}$/';

    /**
     * Handles the sign up functionality.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return Response The HTTP response object.
     */
    public function signUp(Request $request, Response $response, array $args)
    {
        try {
            Validator::validate([
                'name' => 'required|max:255',
                'email' => 'required|email|max:64|unique:users',
                'password' => 'sometimes|confirmed|min:6|max:64|regex:' . self::PASSWORD_VALIDATION,
            ]);
        } catch (\Throwable $e) {
            return response(['error' => $e->getMessage()], 400);
        }

        $params = $request->getParsedBody();

        //save in cache user password
        $collection = collect([
            'name' => $params["name"],
            'email' => $params["email"],
            'password' => generateRandomPassword()
        ]);

        $data = $collection->only('name', 'email', 'password');
        try {

            if ($cognito = $this->createCognitoUser($data)) { //

                //If successful, create the user in local db
                $user = new User();
                $user->name = $params["name"];
                $user->email = sha1($params["email"]);
                $user->password = sha1($data['password']);
                $user->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
                $user->save();

                $wsPayload = [
                    'name' => "Workspace",
                    'description' => "Default workspace",
                ];

                /** @√ar \Budgetcontrol\Connector\Model\Response $connector */
                $connector = Workspace::init('POST', $wsPayload)->call('/add', $user->id);
                if ($connector->getStatusCode() != 201) {
                    Log::critical("Error creating workspace");
                    throw new \Exception("Error creating workspace");
                }

                $token =$this->generateToken($params, $user->id);

                $mail = new \Budgetcontrol\Authtentication\Service\MailService();
                $mail->send_signUpMail($params["email"], $user->name, $token);
            }
        } catch (\Throwable $e) {
            //If an error occurs, delete the user from cognito
            Log::critical($e->getMessage());
            AwsCognitoClient::deleteUser($params["email"]);
            User::find($user->id)->delete();

            //Redirect to view
            return response([
                "success" => false,
                "error" => "An error occurred try again"
            ], 400);
        }

        //Redirect to view
        return response([
            "success" => "Registration successfully",
            "details" => $cognito
        ], 201);
    }

    public function confirmToken(Request $request, Response $response, array $args)
    {
        $token = $args['token'];

        if (empty($token)) {
            return response(["error" => "Invalid token"], 400);
        }

        $user = Cache::get($token);
        if (empty($user)) {
            Log::critical("User not found");
            return response(["error" => "Ops an error occurred"], 400);
        }

        $password = $user->password;
        try {
            AwsCognitoClient::setUserEmailVerified($user->email);
            AwsCognitoClient::setUserPassword($user->email, $user->password, true);
        } catch (\Throwable $e) {
            Log::critical($e->getMessage());
            Cache::forget($token);
            return response(["error" => "Ops an error occurred"], 400);
        }

        $user = User::find($user->id);
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();

        Cache::forget($token);

        return response([], 200);
    }
}
