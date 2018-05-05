<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\App;
use App\Models\Message;
use \Core\View;
use \App\Models\User as User;
use \App\Models\QueryWrapper as QueryWrapper;
use \App\Models\Downloadfiles as Downloadfiles;
use \App\Models\Addfiles as Addfiles;
use \App\Config as Config;

class Downloadfile extends \Core\Controller
{
    public function indexAction()
    {
        $addFilesModel = new Downloadfiles();
        $addFilesModel->download_file_from_url();

        // prepare useful data for display
        $userObject = new User();
        $userFreeSpaceInKB = $userObject->getUserFreeSpace();
        $acceptedFileFormatsAsString = $userObject->getAcceptedFileFormatsAsString();

        // prepare accumulated queries before outputting them to view
        $queryWrapper = new QueryWrapper();
        $sortedQueries = $queryWrapper->sortQueries();
        $totalExecTime = $queryWrapper->getTotalExecTime();

        View::renderTemplate('Downloadfile/index.html', [
            'userFreeSpaceInKB' => $userFreeSpaceInKB,
            'acceptedFileFormats' => $acceptedFileFormatsAsString,
            'queries' => $queryWrapper->getQueries() ?: null,
            'totalExecTime' => $totalExecTime,
            'minExecTime' => empty(count($sortedQueries)) ? null : $sortedQueries[0]["Duration"],
            'maxExecTime' => empty(count($sortedQueries)) ? null : end($sortedQueries)["Duration"],
            'messageError' => $_SESSION['errors']['uploadErrors'],
            'messageSuccess' => $_SESSION['messages'],

            // UI TEXT
            'UI_TXT_DOWNLOAD_PAGE_TITLE' => 'Download file from elsewhere',
            'UI_TEXT_FREE_SPACE_KB_LEFT' => 'kb of free space left in your folder',
            'UI_TEXT_ACCEPTED_FORMATS' => 'Accepted formats: ',
            'UI_TXT_SAVE_TO_FOLDER' => 'Save to folder',
            'UI_TXT_URL_PLACEHOLDER' => 'Please specify url to download from',
            'UI_TXT_DOWNLOAD_DIRECTLY' => 'Download file directly',
            'UI_TXT_DOWNLOAD_BTN' => 'Download'
        ]);
    }

    // function responsible for handling file downloads via CURL
    public function ajaxdownload()
    {
        $addFilesModel = new Addfiles();
        $userObject = new User();
        $appObject = new App();
        $downloadFilesModel = new Downloadfiles();
        $lastInsertId = $downloadFilesModel->download_file_from_url();
        if (!$lastInsertId) {
            $curlDownloadResult = json_encode([
                'message' => ($_SESSION['errors'] ?? null),
                'curlDownloadStatus' => false
            ]);
            echo $curlDownloadResult;
            return;
        }
        $lastInsertedRow = $addFilesModel->getLastInsertedRow($lastInsertId);

        if ($lastInsertedRow) {
            $curlDownloadResult = json_encode([
                'message' => ($_SESSION['messages'] ?? null),
                'curlDownloadStatus' => true,
                'row' => $lastInsertedRow,
                'spaceInfo' => [
                    "userFreeSpaceInMB" => round(($userObject->getUserFreeSpace())/1000, 2),
                    "filesTotalSpaceKB" => $appObject->getFilesTotalSpace(),
                    "fileCount" => $appObject->getFilesTotalCount(),
                    "avgFileSpacePerUser" => $appObject->avgFileSpacePerUser(),
                ]
            ]);
        } else {
            $curlDownloadResult = json_encode([
                'message' => ($_SESSION['errors'] ?? null),
                'curlDownloadStatus' => false
            ]);
        }

        echo $curlDownloadResult;
    }

    // function tracking CURL download progress
    public function trackprogress()
    {
        if("" === session_id()){
            session_start();
        }
        echo $_SESSION['download_percentage'] ?? '?';
    }

    public function imagepreview()
    {
        if (isset($_POST["getimagepreview"]) && $_POST["getimagepreview"] == true) {
            $userObject = new User();
            $messageObject = new Message();

            $filehash = trim(htmlspecialchars($_POST["filehash"]));
            $userFolder = $userObject->getUserFolder();
            $fileToCheck = Config::USERDIRS . "/{$userFolder}/{$filehash}";

            if (!file_exists($fileToCheck)) {
                $imagePreviewResult = json_encode([
                    'message' => $messageObject->getMessageByCode("ERROR_500"),
                    'imagePreviewStatus' => false
                ]);
                echo $imagePreviewResult;
                return;
            }

            $mime = getimagesize($fileToCheck)['mime'];

            if ($mime == "image/jpeg" || $mime == "image/png") {
                $imagePreviewResult = json_encode([
                    'mime' => $mime,
                    'src' => $fileToCheck,
                    'imagePreviewStatus' => true
                ]);
                echo $imagePreviewResult;
                return;
            }

            $imagePreviewResult = json_encode([
                'mime' => $mime,
                'imagePreviewStatus' => false
            ]);
            echo $imagePreviewResult;

        } else {
            echo "Silence is gold :)";
        }
    }

}