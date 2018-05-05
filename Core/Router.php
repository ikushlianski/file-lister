<?php

declare(strict_types=1);

namespace Core;

use \Core\View;
use \App\Controllers\Login as LoginController;
use App\Models\Message as Message;

class Router
{
    protected $routes = [];

    // parameters from the matched route
    // for example [controller] => download [action] => someaction
    protected $params = [];

    public function dispatch($url)
    {
        session_start();

        if ((isset($_GET['logout'])) && ($_GET['logout'] === 'yes')) {
            // delete auth token from DB for security reasons
            $tokenRemovalResult = (new LoginController(null))->removeTokenFromDB();
            if ($tokenRemovalResult === []) {
                // unset session vars and delete "remember_me" cookie
                unset($_SESSION);
                setcookie("remember_me", "", 1);
                session_destroy();
                header("Location: index");
                exit;
            }
        }

        $url = $this->removeQueryStringVariables($url);

        if ($this->match($url)) {
            $controller = $this->params['controller'];
            $controller = $this->convertToStudlyCaps($controller);
            $controller = $this->getNamespace() . $controller;

            if (class_exists($controller)) {
                $controller_object = new $controller($this->params);
                $action = $this->params['action'];
                $action = $this->convertToCamelCase($action);

                if (is_callable([$controller_object, $action])) {
                    // If controller and action were found, check if user is authorized

                    /*
                     * CHECK IF USER IS AUTHORIZED WITHIN SESSION
                     */
                    if (!isset($_SESSION['loggedin'])) {
                        /*
                         * IF USER NOT AUTHORIZED IN SESSION, TRY SEARCHING THEIR COOKIE
                         */
                        if (!isset($_COOKIE["remember_me"])) {
                            if (!empty($_POST['loginCredentialsSubmitted'])) {
                                $safeUsername = trim(htmlspecialchars($_POST['username']));
                                $safePassword = trim(htmlspecialchars($_POST['password']));
                                // we assume logins in our app are unique
                                $LoginController = new LoginController(null);
                                $loginResult = $LoginController->doLogin($safeUsername, $safePassword);
                                if ($loginResult === true) {
                                    // validation is ok
                                    // does the user want to be remembered?
                                    if (isset($_POST["remember_me"])) {
                                        // if user wants to be remembered
                                        $token = sha1(time().$safeUsername);
                                        $tokenWrittenIntoDB = $LoginController->writeTokenIntoDB($token, $safeUsername);
                                        if ($tokenWrittenIntoDB === []) {
                                            $expire = $LoginController->getAuthCookieExpiration();
                                            setcookie("remember_me", $token, time() + $expire);
                                            $_SESSION['loggedin'] = true;
                                            $_SESSION['currentUser'] =
                                                $LoginController->getCurrentUserBySafeUsername($safeUsername);
                                        } else {
                                            throw new \Exception(
                                                "Cannot write security token into DB",
                                                500
                                            );
                                        }
                                    }
                                    $controller_object->$action();
                                } else {
                                    // if validation fails show them login page again
                                    // with message about validation fail
                                    $LoginController = new LoginController(null);
                                    $_SESSION['errors']['loginErrors']
                                        = (new Message())->getMessageByCode("LOGIN_ERROR");
                                    $LoginController->showLoginPageAction($_SESSION['errors']['loginErrors']);
                                    exit;
                                }
                            } else {
                                // if user is not logged in, does not have remember_me cookie
                                // and didn't send us login credentials, just show login page
                                $LoginController = new LoginController(null);
                                $LoginController->showLoginPageAction();
                                exit;
                            }
                        } else {
                            // get user with same cookie name as in $_COOKIE["remember_me"]
                            $LoginController = new LoginController(null);
                            if ($LoginController->checkCookie($_COOKIE["remember_me"])) {
                                $_SESSION['loggedin'] = true;
                                // set current username to session
                                $_SESSION['currentUser'] =
                                    $LoginController->getUserInfoByToken($_COOKIE["remember_me"])['u_name'];
                                // if validation through cookie is ok, send user to their desired page
                                $controller_object->$action();
                            } else {
                                // if user is not logged, does not have remember_me cookie
                                // but sent us their login credentials
                                if (!empty($_POST['loginCredentialsSubmitted'])) {
                                    $safeUsername = trim(htmlspecialchars($_POST['username']));
                                    $safePassword = trim(htmlspecialchars($_POST['password']));
                                    // we assume logins in our app are unique
                                    $LoginController = new LoginController(null);
                                    $loginResult = $LoginController->doLogin($safeUsername, $safePassword);
                                    if ($loginResult === true) {
                                        // validation is ok
                                        // does the user want to be remembered?
                                        if (isset($_POST["remember_me"])) {
                                            // if user wants to be remembered
                                            $token = sha1(time().$safeUsername);
                                            $tokenWrittenIntoDB =
                                                $LoginController->writeTokenIntoDB($token, $safeUsername);
                                            if ($tokenWrittenIntoDB === []) {
                                                $expire = $LoginController->getAuthCookieExpiration();
                                                setcookie("remember_me", $token, time() + $expire);
                                                $_SESSION['loggedin'] = true;
                                                $_SESSION['currentUser'] =
                                                    $LoginController->getCurrentUserBySafeUsername($safeUsername);
                                            } else {
                                                throw new \Exception(
                                                    "Cannot write security token into database",
                                                    500
                                                );
                                            }
                                        }
                                        $controller_object->$action();
                                    } else {
                                        // if validation fails show them login page again
                                        // with message about validation fail
                                        $LoginController = new LoginController(null);
                                        $_SESSION['errors']['loginErrors']
                                            = (new Message())->getMessageByCode("LOGIN_ERROR");
                                        $LoginController->showLoginPageAction($_SESSION['errors']['loginErrors']);
                                        exit;
                                    }
                                } else {
                                    // if user is not logged in, does not have remember_me cookie
                                    // and didn't send us login credentials, just show login page
                                    $LoginController = new LoginController(null);
                                    $LoginController->showLoginPageAction();
                                    exit;
                                }
                            }
                        }
                    } else {
                        // if user is logged in and everything is ok with controller and/or action,
                        // let them continue
                        $controller_object->$action();
                    }
                } else {
                    // echo "Method {$action} (in controller {$controller}) not found";
                    throw new \Exception("Method {$action} (in controller {$controller}) not found");
                }
            } else {
                // echo "Controller class $controller not found";
                throw new \Exception("Controller class $controller not found");
            }
        } else {
            // If no route matches
            throw new \Exception("No route matched", 404);
        }
    }

    protected function convertToStudlyCaps($string)
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }

    protected function convertToCamelCase($string)
    {
        return lcfirst($this->convertToStudlyCaps($string));
    }

    // $params is controller and action
    public function add($route, $params = [])
    {
        // convert route into a regex: escape forward slashes
        $route = preg_replace('/\//', '\\/', $route);

        // convert variables e.g. {controller}
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);

        // Add start and end delimiters and case insensitive flag
        $route = '/^' . $route . '$/i';

        $this->routes[$route] = $params;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function match($url)
    {
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                // $params = [];

                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }

                $this->params = $params;
                return true;
            }
        }
        return false;
    }

    protected function removeQueryStringVariables($url)
    {
        if ($url != '') {
            $parts = explode('&', $url, 2);
            if (strpos($parts[0], '=') === false) {
                $url = $parts[0];
            } else {
                $url = '';
            }
        }

        return $url;
    }

    public function getParams()
    {
        return $this->params;
    }

    protected function getNamespace()
    {
        $namespace = 'App\Controllers\\';
        if (array_key_exists('namespace', $this->params)) {
            $namespace .= $this->params['namespace'] . '\\';
        }
        return $namespace;
    }
}