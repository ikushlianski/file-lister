$(document).ready(function() {
    let messageError = $('.js-message-error').data('message');
    if (messageError) {
        M.toast({html: messageError})
    }

    let messageSuccess = $('.js-message-success').data('message');
    if (messageSuccess) {
        M.toast({html: messageSuccess})
    }
});