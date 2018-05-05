<?php

declare(strict_types=1);

namespace App\Models;

use \App\Models\QueryWrapper as QueryWrapper;

class Message extends \Core\Model
{
    public function getMessageByCode($code)
    {
        $stmt = 'SELECT `msg_text_en` FROM `message` WHERE `msg_code` = :code';
        $results = (new QueryWrapper())->queryDB($stmt, ['code' => $code]);
        return $results["msg_text_en"];
    }

}