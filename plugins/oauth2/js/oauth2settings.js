/**
 * When a user types in the name of the OAuth2 Connection they are creating, update the connection key with
 * a 'URL encoded' version of that name and update the instructions for the callback URL dynamically.
 */
$(document).on('ajaxComplete', function(e) {
    //Get the base path from the instructions for the callback URL (https://thisforum.com/entry).
    var callbackURL  = $(".modal-body .alert-warning code").text();
    // Map plain text connection key to url code
    $("#Form_Name, #Form_AuthenticationKey").keyup(function (event) {
        var val = $(this).val();
        val = val.replace(/[ \/\\&.?;,<>'"]+/g, '-');
        val = val.replace(/\-+/g, '-').toLowerCase();

        // Update the authentication key form field with a URL coded version of the name.
        $("#Form_AuthenticationKey").val(val);
        var authenticationKey = $("#Form_AuthenticationKey").val();

        // Update the instruction with the correct call back URL to be whitelisted (https://thisforum.com/entry/[authenticationKey]).
        $(".modal-body .alert-warning code").text(callbackURL+authenticationKey);
    });

    $(".modal").on('hidden.bs.modal', function () {
        // When the new OAuth2 provider has been saved refresh the page so we can see it.
        location.reload();
    });
});
