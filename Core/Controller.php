<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 19.03.2018
 * Time: 16:57
 */

declare(strict_types=1);

namespace Core;


abstract class Controller
{
    protected $route_params = [];

    public function __construct($route_params)
    {
        $this->route_params = $route_params;
    }

    public function __call($name, $args)
    {
        // implements action filter
        $method = $name . 'Action';
        if (method_exists($this, $method)) {
            if ($this->before() !== false) {
                call_user_func_array([$this, $method], $args);
                $this->after();
            }
        } else {
            throw new \Exception("Method $method not found in controller " . get_class($this));
        }
    }

    protected function before()
    {

    }

    protected function after()
    {

    }
}