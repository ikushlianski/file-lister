<?php

/*
 * Class through which we query the DB. It catches each query and logs it into querylog.txt.
 */

declare(strict_types=1);

namespace App\Models;

use PDO;

class QueryWrapper extends \Core\Model
{

    // array of queries and their execution time
    private static $querylog = [];

    public function queryDB(string $queryString, array $dataToBind = [], bool $fetchAll = false) : ?array
    {
        // queries the DB, returns the result and logs the query
        try {
            $db = static::getDB();
            $db->query('set profiling=1');
            $firstQueryWord = explode(' ', $queryString)[0];
            // if query is not SELECT
            if ($firstQueryWord == 'SELECT') {
                if ($dataToBind == []) {
                    $result = $db->query($queryString);
                    if ($fetchAll) {
                        // useful result from DB query
                        $result = $result->fetchAll();
                    } else {
                        // useful result from DB query
                        $result = $result->fetch();
                    }
                } else {
                    $stmt = $db->prepare($queryString);
                    $stmt->execute($dataToBind);
                    if ($fetchAll) {
                        // useful result from DB query
                        $result = $stmt->fetchAll();
                    } else {
                        // useful result from DB query
                        $result = $stmt->fetch();
                    }
                }
                $dbProfile = $db->query('show profiles');
                // array of metadata about query
                $queryData = $dbProfile->fetchAll()[0];
                $this->addToQueryLog($queryData);
                // we want to return null instead of false for empty results.
                // Useful in return type hinting: we can use ": ?array", which means either array or null,
                // so PHP does not swear anymore
                return $result ?: null;
            } else {
                if ($dataToBind == []) {
                    $db->query($queryString);
                    $lastInsertId = $db->lastInsertId();
                } else {
                    $stmt = $db->prepare($queryString);
                    $stmt->execute($dataToBind);
                    $lastInsertId = $db->lastInsertId();
                }
                $dbProfile = $db->query('show profiles');
                // array of metadata about query
                $queryData = $dbProfile->fetchAll()[0];
                $this->addToQueryLog($queryData);
                if ($firstQueryWord == 'INSERT') {
                    return [$lastInsertId];
                } else {
                    return [];
                }
            }
        } catch (\Error $e) {
            echo $e->getMessage();
        }
    }

    protected function addToQueryLog(array $queryData)
    {
        // add query data to the log that was shown on each page as of task04.
        // It looks ugly so I removed it thereafter.
        // $queryData looks like this:
        // ["Query_ID"]=> string(1) "1" ["Duration"]=> string(10) "0.00028150"
        // ["Query"]=> string(57) "SELECT `u_password` FROM `user` WHERE `u_login` = 'root1'"
        array_push(static::$querylog, $queryData);

        // also, add query to general log located in querylog.txt
        $f = fopen("querylog.txt", "a") or die("Cannot open log file");
        fwrite($f, date('Y-m-d H:i:s') . ': ');
        fwrite($f, $queryData['Query'] . ';' . PHP_EOL);
        fclose($f);
    }

    public function sortQueries() : array
    {
        if (count(static::$querylog) > 1) {
            usort(static::$querylog, function($a, $b) {
                return $a['Duration'] <=> $b['Duration'];
            });
        }
        return static::$querylog;
    }

    public function getTotalExecTime() : float
    {
        $totalExecTime = 0;
        foreach (static::$querylog as $query) {
            $totalExecTime += $query["Duration"];
        }
        return $totalExecTime;
    }

    public function getQueries()
    {
        return static::$querylog;
    }

}