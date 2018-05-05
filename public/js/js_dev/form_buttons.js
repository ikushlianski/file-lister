/*
 * Disables upload and download buttons when input data is empty
 */
$(document).ready(function(){

    // fixed action button
    $(document).ready(function(){
        $('.fixed-action-btn').floatingActionButton();
    });

    // check file upload submit button
    $("#file-upload-field").change(function(){
        if ($(this)[0].files.length > 0) {
            $("[name='fileSubmitted']").prop('disabled', false);
        } else {
            $("[name='fileSubmitted']").prop('disabled', true);
        }
    });

    // check file upload submit button
    $("[name='downloadUrl']").on('input', function(){
        if ($(this)[0].value !== "") {
            $("#submitFileUrlForDownload").prop('disabled', false);
        } else {
            $("#submitFileUrlForDownload").prop('disabled', true);
        }
    });
});
