<?php
namespace Budgetcontrol\Authentication\Traits;

use Illuminate\Support\Carbon;
use Budgetcontrol\Authentication\Facade\Cache;
use Budgetcontrol\Authentication\Domain\Model\User;
use Budgetcontrol\Authentication\Domain\Model\Token;

trait AuthFlow {

    /**
     * Generates a token for Authentication.
     *
     * @param array $params The parameters for generating the token.
     * @param int $userId The ID of the user.
     * @param string $type The type of token to generate (default: 'signup').
     * @return string The generated token.
     */
    public function generateToken(array $params, $userId, string $type = 'signup'): string
    {
        // save token in cache
        $token = Token::create(array_merge([
            'user_id' => $userId,
            'type' => $type
        ], $params));

        $userData = new \stdClass();
        $userData->email = $params["email"];
        $userData->password = $params['password'];
        $userData->id = $userId;

        Cache::put($token->getToken(), $userData, Carbon::now()->addMinutes(10));

        return $token->getToken();
        
    }
}