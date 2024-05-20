<?php

/**
 * workspace application routes
 */
$app->get('/check', \Budgetcontrol\Authentication\Controller\AuthController::class . ':check');
$app->get('/user-info', \Budgetcontrol\Authentication\Controller\AuthController::class . ':authUserInfo');
$app->post('/sign-up', \Budgetcontrol\Authentication\Controller\SignUpController::class . ':signUp');
$app->get('/confirm/{token}', \Budgetcontrol\Authentication\Controller\SignUpController::class . ':confirmToken');
$app->post('/authenticate', \Budgetcontrol\Authentication\Controller\LoginController::class . ':authenticate');
$app->post('/reset-password', \Budgetcontrol\Authentication\Controller\AuthController::class . ':sendResetPasswordMail');
$app->post('/verify-email', \Budgetcontrol\Authentication\Controller\AuthController::class . ':sendVerifyEmail');
$app->put('/reset-password/{token}', \Budgetcontrol\Authentication\Controller\AuthController::class . ':resetPassword');
$app->get('/authenticate/{provider}', \Budgetcontrol\Authentication\Controller\ProviderController::class . ':authenticateProvider');
$app->get('/authenticate/token/{provider}', \Budgetcontrol\Authentication\Controller\ProviderController::class . ':providerToken');
$app->get('/logout', \Budgetcontrol\Authentication\Controller\AuthController::class . ':logout');
$app->get('/user-info/by-email/{email}', \Budgetcontrol\Authentication\Controller\AuthController::class . ':userInfoByEmail');