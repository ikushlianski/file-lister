$(document).ready(function() {

    /*
    handle file uploads via AJAX
     */
    var fileUploadForm = document.forms.namedItem("fileinfo");
    if (fileUploadForm) {
        $(fileUploadForm).on("submit", function(event){
            event.preventDefault();

            var fileSelect = $('#file-upload-field');
            var uploadButton = $('#submitFileUrlForUpload');

            // PRE-REQUEST WORK
            showProgressBar();
            uploadButton.attr("disabled", "disabled");
            var uploadButtonPrevValue = uploadButton.html();
            uploadButton.addClass("waiting");
            var formData = new FormData(fileUploadForm);

            $.ajax({
                async: true,
                contentType: false,
                data: formData,
                enctype: 'multipart/form-data',
                processData: false,
                type: 'POST',
                url: '/addfile/ajaxupload', // in Controllers/Addfile.php
                xhr: function(){
                    var xhr = $.ajaxSettings.xhr();
                    xhr.upload.addEventListener("progress", function(e){
                        if (e.lengthComputable) {
                            $(".progressBar").val(e.loaded / e.total);
                        }
                    }, false);
                    return xhr;
                }
            })
            .done(function(data, textStatus){
                var response = JSON.parse(data);
                if (response.hasOwnProperty("row")) {
                    insertFileIntoDOM(
                        response.row.f_name, response.row.f_date, response.row.f_size, response.row.f_hash
                    );
                }
                if (response.message) {
                    displayFeedbackMessage(response.message);
                }
                if (response.spaceInfo){
                    updateSpaceDataUI(
                        response.spaceInfo.userFreeSpaceInMB,
                        response.spaceInfo.filesTotalSpaceKB,
                        response.spaceInfo.fileCount,
                        response.spaceInfo.avgFileSpacePerUser
                    );
                }
            })
            .fail(function(){
                // fallback message
                displayFeedbackMessage('Error occurred');
            })
            .always(function(){
                // POST-REQUEST WORK
                // return to previous upload button state
                uploadButton.removeClass("waiting");
                // remove progress bar
                removeProgressBar();
                // clear file upload form
                fileSelect.val(null);
                $(".file-path-wrapper > .file-path").val(null);
            });
        });
    }

    /*
     Handle file deletion via AJAX
      */
    var fileDeletionForm = $("#fileDeletionForm");
    if (fileDeletionForm) {
        fileDeletionForm.on("submit", function(event){
            event.preventDefault();

            // find row to be removed from file list. "Trash bin button" is not part of form
            // that submits the delete request.
            // Otherwise we would detect the row to be removed simply by "event.target"...
            var deleteFileFormSubmitButton = $("#filetodelete");
            var idOfFileToDelete = deleteFileFormSubmitButton.val();
            var potentialRowsToDelete = $(".fileDeleteButton[data-fhash]");
            var rowToDelete;
            potentialRowsToDelete.each(function(){
                if ($(this).attr("data-fhash") === idOfFileToDelete) {
                    rowToDelete = $(this).parent().parent();
                }
            });

            var formData = new FormData(fileDeletionForm[0]);
            formData.append(deleteFileFormSubmitButton.attr("name"), deleteFileFormSubmitButton.val());

            $.ajax({
                async: true,
                contentType: false,
                data: formData,
                processData: false,
                type: 'POST',
                url: '/home/ajaxdelete', // in Controllers/Home.php
            })
            .done(function(data){
                var response = JSON.parse(data);
                if (response.deletionStatus === true) {
                    removeFileFromDOM(rowToDelete);
                }
                if (response.message) {
                    displayFeedbackMessage(response.message);
                }
                if (response.spaceInfo){
                    updateSpaceDataUI(
                        response.spaceInfo.userFreeSpaceInMB,
                        response.spaceInfo.filesTotalSpaceKB,
                        response.spaceInfo.fileCount,
                        response.spaceInfo.avgFileSpacePerUser
                    );
                }
            })
            .fail(function(){
                // fallback message
                displayFeedbackMessage('Error occurred');
            });
        });
    }


    /*
    Handle CURL downloads via AJAX
     */
    var curlDownloadForm = $("#curlDownloadForm");
    if (curlDownloadForm) {
        curlDownloadForm.on("submit", function(event){

            // check if "directly" CURL download option chosen
            var checkedRadio = $('#curlDownloadForm input[type="radio"]:checked');

            if (checkedRadio.val() === "directly") {
                return;
            }

            // if "to-folder" CURL download option chosen
            event.preventDefault();
            var curlDownloadSubmitButton = $("#submitFileUrlForDownload");
            var formData = new FormData(curlDownloadForm[0]);
            formData.append(curlDownloadSubmitButton.attr("name"), curlDownloadSubmitButton.val());

            // PRE-REQUEST WORK
            curlDownloadSubmitButton.addClass("waiting");
            // show progress bar and update it until full file download
            showProgressBar();
            // disable download button
            curlDownloadSubmitButton.attr("disabled", "disabled");

            $.ajax({
                async: true,
                contentType: false,
                data: formData,
                processData: false,
                type: 'POST',
                url: '/downloadfile/ajaxdownload', // in Controllers/Downloadfile.php
            })
            .done(function(data){
                var response = JSON.parse(data);

                if (response.curlDownloadStatus === true) {
                    insertFileIntoDOM(
                        response.row.f_name, response.row.f_date, response.row.f_size, response.row.f_hash
                    );
                    if (response.message) {
                        displayFeedbackMessage(response.message);
                    }
                    if (response.spaceInfo){
                        updateSpaceDataUI(
                            response.spaceInfo.userFreeSpaceInMB,
                            response.spaceInfo.filesTotalSpaceKB,
                            response.spaceInfo.fileCount,
                            response.spaceInfo.avgFileSpacePerUser
                        );
                    }
                } else {
                    if (response.message) {
                        displayFeedbackMessage(response.message);
                    }
                }
            })
            .fail(function(){
                // fallback message
                displayFeedbackMessage('Error occurred');
            })
            .always(function(){
                // POST-REQUEST WORK
                removeProgressBar();
                curlDownloadSubmitButton.removeClass("waiting");
                // clear file download form
                $("#curlDownloadForm input[name='downloadUrl']").val(null);
            });

            /*
            Second XMLHttpRequest will open connection to endpoint /downloadfile/trackprogress,
            which holds current CURL download percentage
             */
            var curlProgressStatuses = [];

            function checkProgress() {
                $.ajax({
                    type: "GET",
                    url: "/downloadfile/trackprogress",
                    xhr: function(){
                        var xhr = $.ajaxSettings.xhr();
                        $(xhr).on("readystatechange", (function(e){
                            var xhr = e.target;
                            if (xhr.readyState !== 4) {
                                return;
                            }
                            /*
                             * On second and subsequent downloads xhr.responseText
                             * always initally returns "1" and only then, in a couple of seconds,
                             * it finally begins count from 0 to 1.
                             */
                            if (xhr.responseText === "1") {
                                // this "1" should not be the only item in the curlProgressStatuses array
                                if (curlProgressStatuses.length > 0) {
                                    // last download status was not yet "1"
                                    if (curlProgressStatuses[curlProgressStatuses.length-1] !== 1) {
                                        curlProgressStatuses = []; // empty statuses array for next download
                                        return; // stop checking for progress
                                    }
                                }
                            } else {
                                // sometimes I got an error about a non-finite number.
                                // this should prevent the preloader script from halting
                                if (isFinite(+xhr.responseText)) {
                                    $(".progressBar")[0].value = xhr.responseText;
                                }
                            }

                            curlProgressStatuses.push(xhr.responseText);

                            setTimeout(checkProgress, 100); // how often to check for progress, recursively
                        }));
                        return xhr;
                    }
                });
            }
            checkProgress();
        });
    }
});