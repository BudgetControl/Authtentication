<?php
namespace Budgetcontrol\Authtentication\Traits;

use Illuminate\Support\Carbon;
use Budgetcontrol\Authtentication\Facade\Cache;
use Budgetcontrol\Authtentication\Domain\Model\User;
use Budgetcontrol\Authtentication\Domain\Model\Token;

trait AuthFlow {

    /**
     * Generates a token for authentication.
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