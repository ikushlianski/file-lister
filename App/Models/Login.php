<?php

declare(strict_types=1);

namespace App\Models;

use \App\Models\QueryWrapper as QueryWrapper;

class Login extends \Core\Model
{
    public function getUserInfo($login)
    {
        $stmt = 'SELECT * FROM `user` WHERE `u_login` = :login';
        $results = (new QueryWrapper())->queryDB($stmt, ['login' => $login]);
        return $results;
    }

    public function getUserAuthToken($token) : ?string
    {
        $stmt = 'SELECT `u_auth_token` FROM `user` WHERE `u_auth_token` = :token';
        $results = (new QueryWrapper())->queryDB($stmt, ['token' => $token], true);
        return $results[0]['u_auth_token'];
    }

    public function getUserByAuthToken($token) : ?array
    {
        $stmt = 'SELECT * FROM `user` WHERE `u_auth_token` = :token';
        $results = (new QueryWrapper())->queryDB($stmt, ['token' => $token]);
        return $results;
    }

    public function writeAuthTokenIntoDB($token, $safeUsername)
    {
        $stmt = 'UPDATE `user` SET `u_auth_token` = :token WHERE `u_login` = :u_login';
        $results = (new QueryWrapper())->queryDB($stmt, ['token' => $token, 'u_login' => $safeUsername]);
        return $results; // should return empty array on success
    }

    public function deleteAuthTokenFromDB()
    {
        $stmt = 'UPDATE `user` SET `u_auth_token` = "" WHERE `u_name` = :username';
        $results = (new QueryWrapper())->queryDB($stmt, ['username' => $_SESSION['currentUser']]);
        return $results;
    }

    public function getAuthCookieExpireDate() : ?int
    {
        $stmt = 'SELECT `setting_val` FROM `config` WHERE `setting_key` = "REMEMBER_ME_DURATION"';
        $results = (new QueryWrapper())->queryDB($stmt);
        return (int) $results["setting_val"];
    }

    public function getCurrentUserByUsername($safeUsername) : ?string
    {
        $stmt = 'SELECT `u_name` FROM `user` WHERE `u_login` = :login';
        $results = (new QueryWrapper())->queryDB($stmt, ['login' => $safeUsername]);
        return $results["u_name"];
    }

}