<?php

/**
 * workspace application routes
 */
$app->get('/check', \Budgetcontrol\Authtentication\Controller\AuthController::class . ':check');
$app->get('/user-info', \Budgetcontrol\Authtentication\Controller\AuthController::class . ':authUserInfo');
$app->post('/sign-up', \Budgetcontrol\Authtentication\Controller\SignUpController::class . ':signUp');
$app->get('/confirm/{token}', \Budgetcontrol\Authtentication\Controller\SignUpController::class . ':confirmToken');
$app->post('/authenticate', \Budgetcontrol\Authtentication\Controller\LoginController::class . ':authenticate');
$app->post('/reset-password', \Budgetcontrol\Authtentication\Controller\AuthController::class . ':resetPassword');
$app->post('/verify-email', \Budgetcontrol\Authtentication\Controller\AuthController::class . ':sendVerifyEmail');
$app->put('/reset-password/{token}', \Budgetcontrol\Authtentication\Controller\AuthController::class . ':resetPassword');
$app->get('/authenticate/{provider}', \Budgetcontrol\Authtentication\Controller\ProviderController::class . ':authenticateProvider');
$app->get('/authenticate/token/{provider}', \Budgetcontrol\Authtentication\Controller\ProviderController::class . ':providerToken');
