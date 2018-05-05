/*
HELPER FUNCTIONS
 */

// Function responsible for inserting new file's <tr> into DOM and formatting values properly.
// Will be used to upload files from local machine and after downloading them with CURL
function insertFileIntoDOM(filename, date, size, hash) {
    // convert date into proper format (we don't have Twig filters here)
    let microtime = new Date(date * 1000);
    let dateToInsert =
        microtime.getFullYear() + '/' +
        (((microtime.getMonth()+1) < 10) ? '0'+(microtime.getMonth()+1) : (microtime.getMonth()+1)) + '/' +
        ((microtime.getDate() < 10) ? '0'+microtime.getDate() : microtime.getDate()) + ' ' +
        ((microtime.getHours() < 10) ? '0'+microtime.getHours() : microtime.getHours()) + ':' +
        ((microtime.getMinutes() < 10) ? '0'+microtime.getMinutes() : microtime.getMinutes()) + ':' +
        ((microtime.getSeconds() < 10) ? '0'+microtime.getSeconds() : microtime.getSeconds());

    // convert size into rounded KB
    let sizeToInsert = Math.round((size / 1024) * 100) / 100;

    // clone referenceNode to populate it with data from Ajax call
    var nodeToClone = $(".referenceNode")[0]; // make it a regular Node, not jQuery object
    var newTr = nodeToClone.cloneNode(true);
    var newTrJQ = $(newTr); // maybe it's better to name variables with jQ objects using '$' - $newTr

    // prepare cloned node to be inserted into DOM
    newTrJQ.removeClass("referenceNode hidden");
    newTrJQ.children().eq(0).text(filename);
    newTrJQ.children().eq(1).text(dateToInsert);
    newTrJQ.children().eq(2).text(sizeToInsert);
    newTrJQ.children().eq(3).children().attr("data-fhash", hash);
    newTrJQ.children().eq(4).children().attr("data-fhash", hash);

    // append new row to <tbody> and remove its newlyInserted class
    $(".filesAvailable tbody").append(newTrJQ);

    // add jQuery Color Plugin animation, since jQ does not natively support color transitons.
    // CSS3 does, but it's not a fully cross-browser solution
    newTrJQ.css("backgroundColor", "teal");
    newTrJQ.animate({
        backgroundColor: "#f5f5f5"
    }, 1000);

    // make sure we preserve sorting
    $(".filesAvailable th").each(function(){
        // check if any table header has sorting classes.
        // If it does, we emulate two clicks to allow newly inserted element to "blend" into the list
        if ($(this).hasClass("sorted-asc") || $(this).hasClass("sorted-desc")) {
            $(this).trigger("click").trigger("click");
        }
    });
}

function removeFileFromDOM(fileElement) {
    /*
     CSS3 solution, was implemented in task 6.
    */
    // fileElement.addClass("toBeRemoved");
    // setTimeout(function(){
    //     fileElement.remove()
    // }, 1000);

    /*
    jQuery UI Color Plugin solution
     */
    fileElement.css("backgroundColor", "#f5f5f5");
    fileElement.animate({
        backgroundColor: "#ef5350cc"
    }, 1000, function() {
        $(this).remove();
    });
}

function displayFeedbackMessage(message) {
    M.toast({html: message});
}

function showProgressBar() {
    var progressBarWrapper = $(".progressBarWrapper");
    progressBarWrapper.removeClass("hidden");
    $(".page_wrapper").addClass("darkened");
}

function removeProgressBar() {
    var progressBarWrapper = $(".progressBarWrapper");
    progressBarWrapper.addClass("hidden");
    $(".page_wrapper").removeClass("darkened");
    $(".progressBar").val(0.0); // reset progress for subsequent calls
}

function updateSpaceDataUI(userFreeSpaceInMB, filesTotalSpaceKB, filesCount, avgFileSpacePerUser) {

    function updateUIPart(cls, data) {
        let el = $(`.${cls}`);
        el.text(data);
        el.addClass("newlyInserted");
        setTimeout(() => el.removeClass("newlyInserted"), 2000);
    }

    updateUIPart("freeSpaceMB", userFreeSpaceInMB);
    updateUIPart("filesTotalSpaceKB", filesTotalSpaceKB);
    updateUIPart("fileCount", filesCount);
    updateUIPart("avgFileSpacePerUser", avgFileSpacePerUser);
}