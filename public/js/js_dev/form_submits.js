/*
Makes file download and file delete buttons trigger submit
on respective forms outside of table listing files
 */

$(document).ready(function(){

    // what to do when 'delete file' and 'download file' buttons are pressed in our files list

    // event delegation here, since we will add elements to DOM dynamically
    // and they will also need the same events
    $(".filesAvailable").on("click", ".fileDeleteButton", function(e){
        e.preventDefault();
        const file_id = $(this).data("fhash");

        $("#filetodelete").val(file_id);
        $("#filetodelete").trigger("click");
    });

    $(".filesAvailable").on("click", ".fileDownloadButton", function(e){
        e.preventDefault();
        const file_id = $(this).data("fhash");

        $("#singlefiletodownload").val(file_id);
        $("#singlefiletodownload").trigger("click");
    });

});