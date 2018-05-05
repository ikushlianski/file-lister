<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\App;
use \Core\View;
use \App\Models\User as User;
use \App\Models\QueryWrapper as QueryWrapper;
use \App\Models\Addfiles as Addfiles;
use App\Models\Message as Message;

class Addfile extends \Core\Controller
{
    public function indexAction()
    {
        $addFilesModel = new Addfiles();
        $addFilesModel->handle_uploaded_file();

        // prepare accumulated queries before outputting them to view
        $queryWrapper = new QueryWrapper();
        $sortedQueries = $queryWrapper->sortQueries();
        $totalExecTime = $queryWrapper->getTotalExecTime();

        $userObject = new User();
        $userFreeSpaceInKB = $userObject->getUserFreeSpace();
        $acceptedFileFormatsAsString = $userObject->getAcceptedFileFormatsAsString();

        View::renderTemplate('Addfile/index.html', [
            'userFreeSpaceInKB' => $userFreeSpaceInKB,
            'acceptedFileFormats' => $acceptedFileFormatsAsString,
            'queries' => $queryWrapper->getQueries() ?: null,
            'totalExecTime' => $totalExecTime,
            'minExecTime' => empty(count($sortedQueries)) ? null : $sortedQueries[0]["Duration"],
            'maxExecTime' => empty(count($sortedQueries)) ? null : end($sortedQueries)["Duration"],
            'messageError' => $_SESSION['errors'],
            'messageSuccess' => $_SESSION['messages'],
        ]);
    }

    public function ajaxupload()
    {

        $messageModel = new Message();
        $addFilesModel = new Addfiles();
        $userObject = new User();
        $appObject = new App();

        $uploadResult = $addFilesModel->handle_uploaded_file(); // returns last insert ID
        if ($uploadResult) {
            $lastInsertedRow = $addFilesModel->getLastInsertedRow($uploadResult);
            if (!$lastInsertedRow) {
                http_response_code(404);
                $messageToReturn = json_encode(['message' => $_SESSION['errors']]);
                echo $messageToReturn;
                return;
            }
            $ajaxResult = json_encode([
                'row' => $lastInsertedRow,
                'message' => $messageModel->getMessageByCode('FILE_UPLOAD_SUCCESS'),
                'spaceInfo' => [
                    "userFreeSpaceInMB" => round(($userObject->getUserFreeSpace())/1000, 2),
                    "filesTotalSpaceKB" => $appObject->getFilesTotalSpace(),
                    "fileCount" => $appObject->getFilesTotalCount(),
                    "avgFileSpacePerUser" => $appObject->avgFileSpacePerUser(),
                ]
            ]);
            echo $ajaxResult;
            return;
        } else {
            $messageToReturn = json_encode(['message' => $_SESSION['errors'] ?? null]);
            echo $messageToReturn;
            return;
        }
    }

}