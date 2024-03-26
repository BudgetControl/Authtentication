<?php

/**
 * workspace application routes
 */
$app->get('/check', \Budgetcontrol\Authtentication\Controller\AuthController::class . ':check');
$app->get('/user-info', \Budgetcontrol\Authtentication\Controller\AuthController::class . ':authUserInfo');