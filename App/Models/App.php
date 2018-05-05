<?php
/*
 * Methods for calculating app-wide stats.
 */

declare(strict_types=1);

namespace App\Models;

use \App\Models\QueryWrapper as QueryWrapper;

class App extends \Core\Model
{

    public function getUserCount() : int
    {
        $stmt = 'SELECT COUNT(`u_id`) FROM `user`';
        $results = (new QueryWrapper())->queryDB($stmt);
        return (int) $results["COUNT(`u_id`)"];
    }

    public function getFilesTotalSpace() : float
    {
        $stmt = 'SELECT SUM(`f_size`) FROM `file`';
        $results = (new QueryWrapper())->queryDB($stmt);
        return round(($results["SUM(`f_size`)"] / 1024), 2);
    }

    public function getFilesTotalCount() : int
    {
        $stmt = 'SELECT COUNT(`f_id`) FROM `file`';
        $results = (new QueryWrapper())->queryDB($stmt);
        return (int) $results["COUNT(`f_id`)"];
    }

    public function avgFileSpacePerUser() : float
    {
        $totalUserCount = $this->getUserCount();
        if ($totalUserCount == 0) {
            return 0;
        } else {
            return (($this->getFilesTotalSpace()) / $totalUserCount);
        }
    }

}