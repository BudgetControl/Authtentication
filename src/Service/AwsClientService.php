<?php
namespace Budgetcontrol\Authtentication\Service;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Ellaisys\Cognito\AwsCognitoClient;
use Budgetcontrol\Authtentication\Domain\Model\User;
use Budgetcontrol\Authtentication\Exception\AuthException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

class AwsClientService {

    protected AwsCognitoClient $client;
    private string $username;

    public function __construct(string $username) {
        $this->username = $username;

        $options = [
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'region' => env('COGNITO_USER_POOL_SECRET'),
            'version' => 'latest',
        ];

        $provider= new CognitoIdentityProviderClient($options);
        $this->client = new AwsCognitoClient(
            $provider,
            env('COGNITO_CLIENT_ID'),
            env('COGNITO_USER_POOL_SECRET'),
            env('COGNITO_USER_POOL_ID'),
            env('COGNITO_CLIENT_SECRET_ALLOW', true)
        );
    }

    public function refreshCognitoToken(string $token): string
    {
        $username = $this->username;
        $result = $this->client->refreshToken($this->username, $token);

        $idToken = $result->get('AuthenticationResult')['IdToken'];
        $accessToken = $result->get('AuthenticationResult')['AccessToken'];
        $refreshToken = $result->get('AuthenticationResult')['RefreshToken'];

        Cache::add($username . '_id_token', $idToken, 3600);
        Cache::forever($username . '_refresh_token', $refreshToken);

        return $accessToken;

    }

    public static function decodeAuthToken(string $token): array
    {
        $userPoolId = env('COGNITO_USER_POOL_ID');
        $region = env('AWS_REGION');
        
        try {
            $accessToken = self::decodeAccessToken($token);
    
            // Check if the token was issued by Cognito for the correct client ID
            $clientSub = User::where('sub', $accessToken['sub'])->first();
            if (empty($clientSub)) {
                throw new AuthException('ID client del token non corrispondente.');
            }
    
            // Check if the token was issued by Cognito to the correct user pool
            if ($accessToken['iss'] != "https://cognito-idp.$region.amazonaws.com/$userPoolId") {
                throw new AuthException('Emettitore (issuer) del token non corrispondente.');
            }
    
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage());
        }

        return (array) $accessToken;
    }

        /**
     * get public key
     */
    private static function getPublicKey()
    {
        $region = env('AWS_REGION');
        $userPoolId = env('COGNITO_USER_POOL_ID');

        $url = "https://cognito-idp.$region.amazonaws.com/$userPoolId/.well-known/jwks.json";
        $jwks = json_decode(file_get_contents($url), true);
        
        return $jwks;
    }
    
       /**
     * decode a JWT cognito token
     */
    public static function decodeAccessToken(string $jwt_json): array
    {
        $keys = self::getPublicKey();
        try {
            $pk = self::jwkToPem((array) $keys['keys'][1]);

            $pay_load = JWT::decode($jwt_json, $pk);

            if ($pay_load !== '') {
                return  get_object_vars($pay_load);
            }
        } catch (\Exception $e) {
            throw new AuthException("Invalid JWT token ( AccessToken )", 401);
        }
    }

    /**
     * decode a JWT cognito token
     */
    public static function decodeIdToken(string $jwt_json): array
    {
        $keys = self::getPublicKey();
        try {
            $pk = self::jwkToPem((array) $keys->keys[0]);

            $pay_load = JWT::decode($jwt_json, $pk);

            if ($pay_load !== '') {
                return  get_object_vars($pay_load);
            }
        } catch (\Exception $e) {
            throw new AuthException("Invalid JWT token ( IdToken )", 401);
        }
    }

    /**
     *  convert JWK to pem key
     *  @param array $pK
     * 
     *  @return Key
     */
    private static function jwkToPem(array $pK): Key
    {
        return JWK::parseKey($pK, 'RS256');
    }
}