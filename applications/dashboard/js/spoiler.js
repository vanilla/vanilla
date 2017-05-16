$(document).on('contentLoad', function(e) {
    // Reveals spoiler
    $(e.target).on('click', '.spoiler-trigger', function(e) {
        e.stopPropagation();
        e.preventDefault();
        $(this).closest('.spoiler')
            .addClass('spoiler-visible')
            .find('.form-control').focus().select(); // Select text field if it's text
    });
});
