<?php

namespace Budgetcontrol\Authtentication\Facade;

use Illuminate\Support\Facades\Facade;


/**
 * This class represents a facade for interacting with the AWS Cognito client.
 * It extends the base Facade class.
 * @see \malirobot\AwsCognito\CognitoClient
 */
class AwsCognitoClient extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'aws-cognito-client';
    }
}
