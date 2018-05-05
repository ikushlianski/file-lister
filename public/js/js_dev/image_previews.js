/*
    IMAGE PREVIEW

    It makes sense to not trust file name (and extension) in our file list in browser
    and send ajax request to check for mime type anyways.
    In this example, let's send an AJAX request each time we enter any file
    and check for MIME type on the server side. Second and subsequent mouseovers do nothing,
    since we don't want to add load on server.
*/

// event delegation: we will be adding more elements to file list
$(".filesAvailable")
    .on("mouseenter", "td.sort_filename", function(e){
        let $targetCell = $(e.target);
        if (!$targetCell.is($(this))) {
            return; // we don't need mouseenter events on children of this element
        }
        // if targetCell has images with .imagePreview class
        if ($targetCell.children(".imagePreview").length > 0) {
            $targetCell.children(".imagePreview").fadeIn(200);
            return;
        }
        // if we already sent Ajax request for this file and it's not a PNG or JPEG
        if ($targetCell.hasClass("preventAjax")) {
            return;
        }

        let filename = $(e.target).text();
        let filehash = $(e.target).siblings().children(".fileDownloadButton").data("fhash");
        $.ajax({
            async: true,
            data: {filename: filename, filehash: filehash, getimagepreview: true},
            type: 'POST',
            url: '/downloadfile/imagepreview',
        }).done(function(res){
            var response = JSON.parse(res);
            if (!!response.imagePreviewStatus === false) {
                if (response.message) {
                    displayFeedbackMessage(response.message);
                }
                // if mime type is not jpeg or png, then prevent subsequent AJAX calls regarding this file
                if (response.mime !== "image/jpeg" && response.mime !== "image/png") {
                    $targetCell.addClass("preventAjax");
                }
                return;
            } else {
                // if target cell has no children that are images with ".imagePreview" class
                if ($targetCell.children(".imagePreview").length === 0) {
                    $(new Image()).attr('src', response.src).addClass("imagePreview")
                        .hide().appendTo($targetCell).fadeIn(200);
                }
            }
        }).fail(function(){
            alert("Image preview not available");
        });
    })
    .on("mouseleave", "td.sort_filename", function(e){
        let targetCell = $(e.target);
        if (!targetCell.is($(this))) {
            return;
        }
        targetCell.children("img.imagePreview").fadeOut(200);
    });