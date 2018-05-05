<?php

declare(strict_types=1);

namespace App\Controllers;

use \App\Config;
use \Core\View;
use \App\Models\QueryWrapper as QueryWrapper;
use \App\Models\Login as LoginModel;


class Login extends \Core\Controller
{
    public function showLoginPageAction($message = null)
    {
        // prepare accumulated queries before outputting them to view
        $queryWrapper = new QueryWrapper();
        $sortedQueries = $queryWrapper->sortQueries();
        $totalExecTime = $queryWrapper->getTotalExecTime();

        View::renderTemplate('login.html', [
            'loggedout' => true,
            'messageError' => $message,
            'queries' => $queryWrapper->getQueries() ?: null,
            'totalExecTime' => $totalExecTime,
            'minExecTime' => empty(count($sortedQueries)) ? null : $sortedQueries[0]["Duration"],
            'maxExecTime' => empty(count($sortedQueries)) ? null : end($sortedQueries)["Duration"],

            // UI TEXT
            'PLEASE_LOG_IN' => 'Please log in',
            'UI_TXT_USERNAME_PLACEHOLDER' => 'username',
            'UI_TXT_PASSWORD_PLACEHOLDER' => 'password',
            'UI_TXT_ON' => 'On',
            'UI_TXT_OFF' => 'Off',
            'UI_TXT_REMEMBER_ME' => 'Remember me',
            'UI_TXT_LOG_IN_BTN' => 'Log in'
        ]);
    }

    public function doLogin(string $loginFromUser, string $passwordFromUser) : bool
    {
        $loginModel = new LoginModel();
        $userInfo = $loginModel->getUserInfo($loginFromUser);
        // try to match login and password
        $checked = password_verify($passwordFromUser, (string) $userInfo['u_password']);
        if ($checked) {
            $_SESSION['loggedin'] = true;
            $_SESSION['currentUser'] = $userInfo['u_name'];
            return true;
        } else {
            return false;
        }
    }

    public function checkCookie($token) : bool
    {
        $loginModel = new LoginModel();
        $tokenFromDB = $loginModel->getUserAuthToken($token);
        // if we do match a user's cookie from DB, then return success
        if ($tokenFromDB) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserInfoByToken($token) : ?array
    {
        $loginModel = new LoginModel();
        $userInfoByToken = $loginModel->getUserByAuthToken($token);
        return $userInfoByToken;
    }

    public function getCurrentUserBySafeUsername($safeUsername) : ?string
    {
        $loginModel = new LoginModel();
        $currentUser = $loginModel->getCurrentUserByUsername($safeUsername);
        return $currentUser;
    }

    public function writeTokenIntoDB($token, $safeUsername) : array
    {
        $loginModel = new LoginModel();
        $lastInsertID = $loginModel->writeAuthTokenIntoDB($token, $safeUsername);
        return $lastInsertID;
    }

    public function removeTokenFromDB() : array
    {
        $loginModel = new LoginModel();
        $tokenRemoved = $loginModel->deleteAuthTokenFromDB();
        return $tokenRemoved; // should return empty array on successful removal
    }

    public function getAuthCookieExpiration() : int
    {
        $loginModel = new LoginModel();
        $expires = $loginModel->getAuthCookieExpireDate();
        return $expires;
    }

}