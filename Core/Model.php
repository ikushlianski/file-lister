<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 23.03.2018
 * Time: 14:11
 */

declare(strict_types=1);

namespace Core;

use PDO;
use App\Config;

abstract class Model
{
    static $db = null;

    protected static function getDB()
    {

        if (static::$db === null) {
            try {
                $dsn = "mysql:host=".Config::DB_HOST.";dbname=".Config::DB_NAME.";charset=utf8";
                $db = new PDO($dsn, Config::DB_USER, Config::DB_PASSWORD);
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Throw an Exception if error occurs
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $db;
            } catch (\PDOException $e) {
                echo $e->getMessage();
            }
        }
    }
}