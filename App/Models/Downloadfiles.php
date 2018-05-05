<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Config;
use App\Models\Message as Message;
use App\Models\User as User;
use \App\Models\QueryWrapper as QueryWrapper;

class Downloadfiles extends \Core\Model
{

    function handle_downloaded_file()
    {
        if (isset($_POST['singlefiletodownload']) && !empty($_POST['singlefiletodownload'])) {
            // clear session messages
            $_SESSION['messages'] = $_SESSION['errors'] = null;

            // sanitize incoming data
            $f_hash = trim(htmlspecialchars($_POST['singlefiletodownload']));

            // prepare necessary variables
            $userModel = new User();
            $u_id = $userModel->getUserIdByName($_SESSION['currentUser']);
            $userFolder = $userModel->getUserFolder();

            $stmt = 'SELECT `f_hash`, `f_name` FROM `file` WHERE `u_id` = :u_id AND `f_hash` = :f_hash';
            $results = (new QueryWrapper())->queryDB($stmt, ['u_id' => $u_id, 'f_hash' => $f_hash]);
            if (!empty($results)) {
                $pathToFile = Config::USERDIRS . "/{$userFolder}/" . $results['f_hash'];
                if (!is_file($pathToFile)) {
                    header('HTTP/1.0 404 Not Found');
                    throw new \Exception("Not found", 404);
                }
                if (!file_exists($pathToFile)) {
                    header('HTTP/1.0 404 Not Found');
                    throw new \Exception("File not found", 404);
                }
                header('Content-Disposition: attachment; filename="' . $results['f_name'] . '"');
                header('Content-type: application/octet-stream');
                $fh = fopen($pathToFile, 'rb');
                fpassthru($fh);
                exit();
            } else {
                header('HTTP/1.0 404 Not Found');
                exit();
            }
        }
    }

    function download_file_from_url()
    {
        $messageModel = new Message();
        $userModel = new User();

        // prepare necessary variables
        $userFolder = $userModel->getUserFolder();
        $maxSpacePerUser = $userModel->getUserSpaceLimit(true);
        $availableSpace = $userModel->getUserFreeSpace(true);
        $allowedExtensions = $userModel->getAcceptedFileFormats();

        if (isset($_POST['fileurlsubmitted']) && !empty($_POST['fileurlsubmitted'])) {
            if (empty($_POST['downloadUrl'])){
                $_SESSION['errors'] = $messageModel->getMessageByCode("FILE_DOWNLOAD_ERROR");
                return false;
            }
            $downloadOption = trim(htmlspecialchars($_POST['downloadoption']));
            $url = trim($_POST['downloadUrl']);

            // if our file does not have a proper name or format
            (basename($url) !== "") ? $output_filename =  basename($url) : $output_filename = "file";
            $output_filename = ltrim(basename($url), '.');
            try {
                $fileType = pathinfo($output_filename)['extension'];
            } catch (\ErrorException $e) {
                $fileType = "";
            }

            if ($downloadOption === "directly") {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_NOPROGRESS, TRUE);
                curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
                curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
                curl_setopt($ch, CURLOPT_URL, $url);

                header('Content-disposition: attachment; filename='.$output_filename);
                header('Content-type: application/octet-stream');

                curl_exec($ch);
                curl_close($ch);
            }

            if ($downloadOption === "to-folder") {
                // try to find out information about the file
                $headers = get_headers($url, 1);
                try {
                    $downloadFileSize = $headers['Content-Length'];
                    if (!$downloadFileSize) {
                        $_SESSION['errors'] =
                            $messageModel->getMessageByCode("FILE_DOWNLOAD_ERROR");
                        return false;
                    }
                } catch (\ErrorException $e) {
                    $_SESSION['errors'] =
                        $messageModel->getMessageByCode("FILE_DOWNLOAD_ERROR");
                    return false;
                }

                // check if /userdirs/ folder (parent of all user folders) exists,
                // if not, then show error message
                if (!file_exists(Config::USERDIRS)) {
                    $_SESSION['errors'] = $messageModel->getMessageByCode('FILE_UPLOAD_ERROR');
                    return false;
                }

                $pathToFile = Config::USERDIRS . "/{$userFolder}/" . sha1($output_filename);

                // Check if file already exists
                if (file_exists($pathToFile)) {
                    $uploadOk = 0;
                    $_SESSION['errors'] = $messageModel->getMessageByCode('FILE_EXISTS_ERROR');
                    return false;
                }

                // Check file size against general limit
                if ($downloadFileSize > $maxSpacePerUser) {
                    $uploadOk = 0;
                    $_SESSION['errors'] =
                        $messageModel->getMessageByCode('FILE_UPLOAD_ERROR_OUT_OF_SPACE');
                    return false;
                }

                // check if there was enough space for file
                if ($downloadFileSize > $availableSpace) {
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
                    if ($downloadFileSize > $fileExtensionSizeLimit) {
                        $uploadOk = 0;
                        $_SESSION['errors'] =
                            $messageModel->getMessageByCode('FILE_UPLOAD_ERROR_FORMAT_SIZE_MISMATCH');
                        return false;
                    }
                }

                /*
                 * Copy file (hashed) to our filesystem
                 */

                // clear download percentage from previous download
                $_SESSION['download_percentage'] = 0.0;

                $ch = curl_init();
                $fp = fopen($pathToFile, 'w+b') or die('Cannot open file');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_NOPROGRESS, false); // turn on progress
                curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, [$this, "progress"]); // define a progress callback
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 0);

                $result = curl_exec($ch);
                curl_close($ch);
                fclose($fp);

                // if something went wrong with inserting into folder in our filesystem
                if (!$result) {
                    $_SESSION['messages'] = null;
                    $_SESSION['errors'] =
                        $messageModel->getMessageByCode('FILE_DOWNLOAD_ERROR');
                    return false;
                }

                /*
                 * Save file to DB
                 */
                // prepare variables for inserting into DB
                $u_id = $userModel->getUserIdByName($_SESSION['currentUser']);
                $ext_id = $userModel->getExtIdByExtName($fileType);
                // perform insertion
                $dbUploadResult = $userModel->uploadFileToDB(
                    NULL,
                    $output_filename,
                    $downloadFileSize,
                    $u_id,
                    $ext_id
                );

                // on successful insertion we get empty array back
                if (!is_array($dbUploadResult)) {
                    $_SESSION['errors'] =
                        $messageModel->getMessageByCode('FILE_UPLOAD_ERROR');
                    return false;
                }

                $_SESSION['errors'] = null;
                $_SESSION['messages'] =
                    $messageModel->getMessageByCode('FILE_DOWNLOAD_SUCCESS');
                return (int) $dbUploadResult[0];
            }
        }
    }

    // define a progress function so we can listen to download progress with AJAX
    protected function progress($resource, $download_size, $downloaded, $upload_size, $uploaded)
    {
        $percentage = $download_size == 0 ? 0.0 : ($downloaded / $download_size);
        $_SESSION['download_percentage'] = $percentage;
        session_write_close();
        session_start();
    }
}