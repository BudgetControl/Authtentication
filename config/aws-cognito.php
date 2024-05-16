<?php
$config = [
    'credentials' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],
    'region' => env('AWS_COGNITO_REGION'),
    'version' => env('AWS_COGNITO_VERSION','latest'),

    'app_client_id' => env('AWS_COGNITO_CLIENT_ID'),
    'app_client_secret' => env('AWS_COGNITO_CLIENT_SECRET'),
    'user_pool_id' => env('AWS_COGNITO_USER_POOL_ID'),
    'redirect_uri' => env('AWS_COGNITO_REDIRECT_URI'),
];

$aws = new \Aws\Sdk($config);
$cognitoClient = $aws->createCognitoIdentityProvider();

$awsCognitoClient = new \malirobot\AwsCognito\CognitoClient($cognitoClient);
$awsCognitoClient->setAppClientId($config['app_client_id']);
$awsCognitoClient->setAppClientSecret($config['app_client_secret']);
$awsCognitoClient->setRegion($config['region']);
$awsCognitoClient->setUserPoolId($config['user_pool_id']);
$awsCognitoClient->setAppName(env('APP_NAME'));
$awsCognitoClient->setAppRedirectUri($config['redirect_uri']);

$awsCognitoClient;
