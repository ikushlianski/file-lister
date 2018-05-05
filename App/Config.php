<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 23.03.2018
 * Time: 18:26
 */
declare(strict_types=1);

namespace App;

class Config
{
    const DB_HOST = '127.0.0.1';
    const DB_NAME = 'file_lister';
    const DB_USER = 'root';
    const DB_PASSWORD = '';

    // directory where users' folders with files are stored
    const USERDIRS = 'userdirs';

    /**
     * Password cost for password_hash is 10 for this app.
     * I used password_hash('123456', PASSWORD_DEFAULT, ['cost' => 10])
     * to generate passwords for sample users.
     */

    /**
     * Show or hide error messages on screen for user.
     * If set to false, errors will be put into logs folder
     */
    const SHOW_ERRORS = false;
}