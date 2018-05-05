<?php

declare(strict_types=1);

namespace App\Controllers;

use \Core\View;
use \App\Models\QueryWrapper as QueryWrapper;
use \App\Models\User as User;
use App\Models\App as App;
use App\Models\Downloadfiles as Downloadfiles;
use \App\Models\Addfiles as Addfiles;


class Home extends \Core\Controller
{
    public function indexAction()
    {
        $userObject = new User();
        $userFolder = $userObject->getUserFolder();
        $userObject->handle_deleted_file();
        $userSpaceLimitInKB = $userObject->getUserSpaceLimit();
        $acceptedFileFormatsAsString = $userObject->getAcceptedFileFormatsAsString();

        // handle an uploaded file
        $addFilesModel = new Addfiles();
        $addFilesModel->handle_uploaded_file();

        // handle the file that user wants to download from their own folder
        $downloadFileObj = new Downloadfiles();
        $downloadFileObj->handle_downloaded_file();

        // handle file downloaded from remote website
        $downloadFileObj->download_file_from_url();

        // list all user files
        $preparedFileList = $userObject->listFiles();

        // prepare app stats
        $userFreeSpaceInKB = $userObject->getUserFreeSpace();
        $appObject = new App();
        $userCount = $appObject->getUserCount();
        $filesTotalSpace = $appObject->getFilesTotalSpace();
        $filesTotalCount = $appObject->getFilesTotalCount();
        $avgFileSpacePerUser = $appObject->avgFileSpacePerUser();

        // prepare accumulated queries before outputting them to view
        $queryWrapper = new QueryWrapper();
        $sortedQueries = $queryWrapper->sortQueries();
        $totalExecTime = $queryWrapper->getTotalExecTime();

        View::renderTemplate('Home/index.html', [
            'currentUser' => $_SESSION['currentUser'],
            'userFolder' => $userFolder,
            'userSpaceLimitInKB' => $userSpaceLimitInKB,
            'userSpaceLimitInMB' => (round($userSpaceLimitInKB/1000, 2)),
            'userFreeSpaceInKB' => $userFreeSpaceInKB,
            'userFreeSpaceInMB' => round($userFreeSpaceInKB/1000, 2),
            'userCount' => $userCount,
            'filesTotalSpace' => $filesTotalSpace,
            'filesTotalCount' => $filesTotalCount,
            'avgFileSpacePerUser' => $avgFileSpacePerUser,
            'acceptedFileFormats' => $acceptedFileFormatsAsString,
            'userFiles' => $preparedFileList,
            'queries' => $queryWrapper->getQueries() ?: null,
            'totalExecTime' => $totalExecTime,
            'minExecTime' => empty(count($sortedQueries)) ? null : $sortedQueries[0]["Duration"],
            'maxExecTime' => empty(count($sortedQueries)) ? null : end($sortedQueries)["Duration"],
            'messageError' => $_SESSION['errors'],
            'messageSuccess' => $_SESSION['messages'],

            // UI TEXT. May come from DB or file in a real-world app
            'UI_TXT_USER_INFO' => 'User information',
            'UI_TXT_APP_STATS' => 'App stats',
            'UI_TXT_FILES_IN_FOLDER' => 'Files in your folder',
            'UI_TXT_DELETE_SELECTED' => 'Delete selected ',
            'UI_TXT_CUR_USER' => 'Current user: ',
            'UI_TXT_SPACE_AVAILABLE' => 'Space available: ',
            'UI_TXT_CUR_USER_FOLDER' => 'Your folder: ',
            'UI_TXT_USER_COUNT' => 'Number of users',
            'UI_TXT_FILES_TOTAL_SPACE' => 'Total space used by all files: ',
            'UI_TXT_FILES_TOTAL_COUNT' => 'Total number of files: ',
            'UI_TXT_AVG_SPACE_TAKEN_BY_USER' => 'Average space taken by one user: ',
            'UI_TXT_NO_FILES_IN_FOLDER' => 'You have no files in your folder',
            'UI_TXT_ACCEPTED_FORMATS' => 'Accepted file formats: ',
            'UI_TXT_OUT_OF' => 'out of',
            'UI_TXT_KB' => 'kb',
            'UI_TXT_MB' => 'Mb',
            'UI_TXT_FILE_NAME' => 'File name',
            'UI_TXT_UPLOAD_DATE' => 'Upload date',
            'UI_TXT_FILE_SIZE' => 'File size',
            'UI_TXT_FILE_DELETE' => 'Delete',
            'UI_TXT_FILE_DOWNLOAD' => 'Download',

            // Upload section
            'UI_TXT_UPLOAD_BTN' => 'Upload file',
            'UI_TXT_UPLOAD_SECTION_TITLE' => 'Upload files from local machine',

            // Download section
            'UI_TXT_DOWNLOAD_SECTION_TITLE' => 'Download file from elsewhere',
            'UI_TEXT_FREE_SPACE_KB_LEFT' => 'kb of free space left in your folder',
            'UI_TEXT_ACCEPTED_FORMATS' => 'Accepted formats: ',
            'UI_TXT_SAVE_TO_FOLDER' => 'Save to folder',
            'UI_TXT_URL_PLACEHOLDER' => 'Please specify url to download from',
            'UI_TXT_DOWNLOAD_DIRECTLY' => 'Download file directly',
            'UI_TXT_DOWNLOAD_BTN' => 'Download'
        ]);
    }

    public function ajaxdelete()
    {
        $userObject = new User();
        $appObject = new App();
        $deletionResult = $userObject->handle_deleted_file();
        if ($deletionResult) {
            $deletionData = json_encode([
                "message" => $_SESSION['messages'],
                "deletionStatus" => true,
                "spaceInfo" => [
                    "userFreeSpaceInMB" => round(($userObject->getUserFreeSpace())/1000, 2),
                    "filesTotalSpaceKB" => $appObject->getFilesTotalSpace(),
                    "fileCount" => $appObject->getFilesTotalCount(),
                    "avgFileSpacePerUser" => $appObject->avgFileSpacePerUser(),
                ]
            ]);
        } else {
            $deletionData = json_encode([
                "message" => $_SESSION['errors'],
                "deletionStatus" => false
            ]);
        }
        echo $deletionData;
    }
}