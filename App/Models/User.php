<?php
/**
 * Methods for calculating user space, listing user files and so on.
 * We will make it user-centric, since it's user who is actively interacting with files.
 * Files are passive.
 */

declare(strict_types=1);

namespace App\Models;

use \App\Models\QueryWrapper as QueryWrapper;
use App\Models\Message as Message;
use App\Config;

class User extends \Core\Model
{

    public function getUserFolder() : string
    {
        $stmt = 'SELECT `u_foldername` FROM `user` WHERE `u_name` = :u_name';
        $results = (new QueryWrapper())->queryDB($stmt, ['u_name' => $_SESSION['currentUser']]);
        return $results['u_foldername'];
    }

    public function getUserSpaceLimit($inbytes = false) : int
    {
        $stmt = 'SELECT `u_spacelimit` FROM `user` WHERE `u_name` = :u_name';
        $results = (new QueryWrapper())->queryDB($stmt, ['u_name' => $_SESSION['currentUser']]);
        if (!$inbytes) {
            $userSpaceLimitInKB = (int) ($results['u_spacelimit'] / 1024);
            return $userSpaceLimitInKB; // return user space limit in kilobytes
        } else {
            return (int) $results['u_spacelimit']; // return user space limit in bytes
        }
    }

    public function getUserFreeSpace($inbytes = false) : float
    {
        // get max space
        $spaceLimit = $this->getUserSpaceLimit(true);

        // get space used by user's files
        $spaceUsed = $this->getSpaceUsedByUserFiles(true);

        // calculate free space
        $userFreeSpace = $spaceLimit - $spaceUsed;

        if (!$inbytes) {
            return round(($userFreeSpace / 1024), 2);
        } else {
            return $userFreeSpace ; // return user free space in bytes for more precision
        }

    }

    public function getSpaceUsedByUserFiles($inbytes = false) : float
    {
        $stmt = 'SELECT `f_size` FROM `file` WHERE `u_id` = 
                (SELECT `u_id` FROM `user` WHERE `u_name` = :u_name)';
        $results = (new QueryWrapper())->queryDB($stmt, ['u_name' => $_SESSION['currentUser']], true);
        if (!$results) {
            return 0; // no files in user folder, so no space is used
        }
        $spaceUsedByUserFiles = 0;
        foreach ($results as $file) {
            $spaceUsedByUserFiles += $file['f_size'];
        }
        if (!$inbytes) {
            $spaceUsedByUserFiles = $spaceUsedByUserFiles / 1024;
            return $spaceUsedByUserFiles; // return space used by user files in kilobytes
        } else {
            return $spaceUsedByUserFiles; // return space used by user files in bytes
        }
    }

    public function getAcceptedFileFormats() : ?array
    {
        $stmt = 'SELECT `ext_name` FROM `extension` JOIN `users_extensions` USING(`ext_id`) WHERE `u_id` = 
                (SELECT `u_id` FROM `user` WHERE `u_name` = :u_name)';
        $results = (new QueryWrapper())->queryDB($stmt, ['u_name' => $_SESSION['currentUser']], true);

        if (!$results) {
            return []; // no formats allowed for this user
        }
        // turn this into a pure array of extensions without junk
        $acceptedFormatsArray = [];
        foreach ($results as $k => $format) {
            array_push($acceptedFormatsArray, $format["ext_name"]);
        }
        return $acceptedFormatsArray;
    }


    public function getAcceptedFileFormatsAsString() : string
    {
        $acceptedFileFormatsArray = $this->getAcceptedFileFormats();

        if (!$acceptedFileFormatsArray) {
            return 'None';
        }

        // I have a helper format which is empty ""
        // We need this empty format only when downloading file with unknown extension
        // but we will not print an empty string when listing available formats to user in UI
        foreach ($acceptedFileFormatsArray as $k => $format) {
            if (strlen($format) == 0) {
                unset($acceptedFileFormatsArray[$k]);
                break; // no more than one empty extension is allowed per user
            }
        }
        // make resulting formats array a string
        $acceptedFormatsString = implode(", ", $acceptedFileFormatsArray);
        return $acceptedFormatsString;
    }


    public function getFileExtensionSizeLimit($extension) : ?int
    {
        $stmt = 'SELECT `u_ext_maxsize` FROM `users_extensions` WHERE `u_id` = 
                (SELECT `u_id` FROM `user` WHERE `u_name` = :u_name) AND `ext_id` = 
                (SELECT `ext_id` FROM `extension` WHERE `ext_name` = :extension)';
        $results = (new QueryWrapper())->queryDB(
            $stmt,
            ['u_name' => $_SESSION['currentUser'], 'extension' => $extension],
            true
        );

        if (!$results) {
            return null;
        }
        return (int) $results[0]['u_ext_maxsize'];
    }

    public function getUserIdByName($u_name) : int
    {
        $stmt = 'SELECT `u_id` FROM `user` WHERE `u_name` = :u_name';
        $results = (new QueryWrapper())->queryDB($stmt, ['u_name' => $u_name]);
        return (int) $results['u_id'];
    }

    public function getExtIdByExtName($ext_name) : int
    {
        $stmt = 'SELECT `ext_id` FROM `extension` WHERE `ext_name` = :ext_name';
        $results = (new QueryWrapper())->queryDB($stmt, ['ext_name' => $ext_name]);
        return (int) $results['ext_id'];
    }

    public function uploadFileToDB($f_id, $f_name, $f_size, $u_id, $ext_id)
    {
        $stmt = 'INSERT INTO `file` VALUES (:f_id, :f_name, :f_hash, :f_size, UNIX_TIMESTAMP(), :u_id, :ext_id)';
        $results = (new QueryWrapper())->queryDB($stmt, [
            'f_id' => $f_id,
            'f_name' => $f_name,
            'f_hash' => SHA1($f_name),
            'f_size' => $f_size,
            'u_id' => $u_id,
            'ext_id' => $ext_id
            ]);
        return $results;
    }

    public function listFiles() : ?array
    {
        $stmt = 'SELECT `f_name`, `f_hash`, `f_size`, `f_date` FROM `file` WHERE `u_id` = :u_id ';
        $results = (new QueryWrapper())->queryDB(
            $stmt,
            ['u_id' => $this->getUserIdByName($_SESSION['currentUser'])],
            true
        );

        if ($results == []) {
            return null;
        }

        return $results;
    }

    public function handle_deleted_file()
    {
        if (isset($_POST['filetodelete']) && !empty($_POST['filetodelete'])) {
            $target_dir = Config::USERDIRS . "/{$this->getUserFolder()}";
            $hashOfFileToDelete = trim(htmlspecialchars($_POST['filetodelete']));

            // delete files from file system
            if(file_exists("{$target_dir}/{$hashOfFileToDelete}")) {
                // check if the file exists in user's folder
                $checkDeletion = unlink("{$target_dir}/{$hashOfFileToDelete}");
                if ($checkDeletion === false) {
                    $_SESSION['errors']
                        = (new Message())->getMessageByCode("FILE_DELETION_ERROR");
                    return false;
                }
            } else {
                $_SESSION['errors']
                    = (new Message())->getMessageByCode("FILE_DELETION_ERROR");
                return false;
            }

            // Delete files from DB
            $stmt = "DELETE FROM `file` WHERE `u_id` = :u_id AND `f_hash` = :hashOfFileToDelete";
            $results = (new QueryWrapper())->queryDB($stmt, [
                'u_id' => $this->getUserIdByName($_SESSION['currentUser']),
                'hashOfFileToDelete' => $hashOfFileToDelete
            ]);

            // on successful deletion success we should get empty array
            if ($results !== []) {
                $_SESSION['errors']
                    = (new Message())->getMessageByCode("FILE_DELETION_ERROR");
                return false;
            }

            $_SESSION['messages'] =
                (new Message())->getMessageByCode("FILE_DELETION_SUCCESS");
            return true;
        } else {
            $_SESSION['errors'] = null;
            $_SESSION['messages'] = null;
        }
    }

}