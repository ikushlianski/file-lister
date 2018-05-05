<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Config;
use App\Models\Message as Message;
use App\Models\User as User;
use \App\Models\QueryWrapper as QueryWrapper;

class Addfiles extends \Core\Model
{

    public function handle_uploaded_file()
    {

        $messageModel = new Message();
        $userModel = new User();

        // prepare necessary variables
        $maxSpacePerUser = $userModel->getUserSpaceLimit(true);
        $availableSpace = $userModel->getUserFreeSpace(true);
        $allowedExtensions = $userModel->getAcceptedFileFormats();

        if (isset($_FILES['file']) && $_FILES['file']['size'] > 0) {
            // first thing, check uploaded file for error
            if (!empty($_FILES['file']['error'])) {
                $_SESSION['errors'] = $messageModel->getMessageByCode('FILE_UPLOAD_ERROR');
                return false;
            }

            // check if a folder for all users' files exists on the server
            if (!file_exists(Config::USERDIRS)) {
                $_SESSION['errors'] = $messageModel->getMessageByCode('FILE_UPLOAD_ERROR');
                return false;
            }
            $currentUserFolder = $userModel->getUserFolder();
            $target_dir = Config::USERDIRS . "/{$currentUserFolder}/";
            if (!file_exists($target_dir)) {
                $_SESSION['errors'] = $messageModel->getMessageByCode('FILE_UPLOAD_ERROR');
                return false;
            }
            $target_file_for_db = basename($_FILES["file"]["name"]);
            $target_file_for_filesystem = $target_dir . sha1(basename($_FILES["file"]["name"]));
            $uploadOk = 1;
            $fileType = pathinfo($target_file_for_db,PATHINFO_EXTENSION);

            // Check if file already exists
            if (file_exists($target_file_for_filesystem)) {
                $uploadOk = 0;
                $_SESSION['errors'] = $messageModel->getMessageByCode('FILE_EXISTS_ERROR');
                return false;
            }

            // Check file size against general limit
            if ($_FILES["file"]["size"] > $maxSpacePerUser) {
                $uploadOk = 0;
                $_SESSION['errors'] =
                    $messageModel->getMessageByCode('FILE_UPLOAD_ERROR_OUT_OF_SPACE');
                return false;
            }

            // check if there was enough space for file
            if ($_FILES["file"]["size"] > $availableSpace) {
                $uploadOk = 0;
                $_SESSION['errors'] =
                    $messageModel->getMessageByCode('FILE_UPLOAD_ERROR_OUT_OF_SPACE');
                return false;
            }

            // if this file is among the allowed types
            if (!in_array($fileType, $allowedExtensions)) {
                $uploadOk = 0;
                $_SESSION['errors'] =
                    $messageModel->getMessageByCode('FILE_UPLOAD_ERROR_FORMAT_MISMATCH');
                return false;
            } else {
                // check space limit for this very type of file
                $fileExtensionSizeLimit = $userModel->getFileExtensionSizeLimit($fileType);
                if ($_FILES["file"]["size"] > $fileExtensionSizeLimit) {
                    $uploadOk = 0;
                    $_SESSION['errors'] =
                        $messageModel->getMessageByCode('FILE_UPLOAD_ERROR_FORMAT_SIZE_MISMATCH');
                    return false;
                }
            }

            // move uploaded file to target directory and DB
            if ($uploadOk === 1) {
                move_uploaded_file($_FILES["file"]["tmp_name"], $target_file_for_filesystem);

                // get user id by userName for insertion into DB
                $u_id = $userModel->getUserIdByName($_SESSION['currentUser']);
                $ext_id = $userModel->getExtIdByExtName($fileType);

                // insert new file into DB
                $dbUploadResult = $userModel->uploadFileToDB(
                    NULL,
                    $target_file_for_db,
                    $_FILES["file"]["size"],
                    $u_id,
                    $ext_id
                );

                // we expect either an empty array of an array with lastInsertId as first value
                if (!is_array($dbUploadResult)) {
                    $_SESSION['errors'] =
                        $messageModel->getMessageByCode('FILE_UPLOAD_ERROR');
                    return false;
                }

                $_SESSION['messages'] =
                    $messageModel->getMessageByCode('FILE_UPLOAD_SUCCESS');
                // return last insert ID
                return (int) $dbUploadResult[0];
            }
        }
    }

    public function getLastInsertId() : int
    {
        $db = static::getDB();
        $lastInsertId = $db->lastInsertId();
        return (int) $lastInsertId;
    }

    public function getLastInsertedRow($lastInsertId) : ?array
    {
        $queryWrapper = new QueryWrapper();
        $stmt = 'SELECT * FROM `file` WHERE `f_id` = :lastInsertId';
        $result = $queryWrapper->queryDB($stmt, ['lastInsertId' => $lastInsertId]);
        return $result ?: null;
    }

}