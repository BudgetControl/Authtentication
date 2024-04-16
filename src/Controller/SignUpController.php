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

use Budgetcontrol\Authtentication\Domain\Model\User;
use Budgetcontrol\Authtentication\Service\BCConnectorService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Support\Facades\Validator;
use Budgetcontrol\Authtentication\Traits\RegistersUsers;
use Budgetcontrol\Authtentication\Facade\AwsCognitoClient;
use Illuminate\Support\Facades\Log;

class SignUpController
{
    use RegistersUsers;

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
            return response()->json(['error' => $e->getMessage()], 400);
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

            if ($cognito = $this->createCognitoUser($data)) {

                //If successful, create the user in local db
                $user = new User();
                $user->name = $params["name"];
                $user->email = $params["email"];
                $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
                $user->save();

                BCConnectorService::AddWorkspace_api($user->id);

                $mail = new \Budgetcontrol\Authtentication\Service\MailService();
                $mail->send_signUpMail($user->email, $user->name, $user->id);
            }
            
        } catch (\Throwable $e) {
            //If an error occurs, delete the user from cognito
            // AwsCognitoClient::deleteUser($params["email"]);

            Log::critical($e->getMessage());
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
}
