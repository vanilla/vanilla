jQuery(document).ready(function($) {

    // Approve / Decline an applicant asynchronously.
    $(document).delegate('a.DeclineApplicant, a.ApproveApplicant', 'click', function() {
        // Prepare our message.
        var approvedMsg = gdn.definition('ApprovedTranslation', 'Approved');
        var declinedMsg = gdn.definition('DeclinedTranslation', 'Declined');
        var message = ($(this).hasClass('ApproveApplicant')) ? approvedMsg : declinedMsg;
        message = '<em class="ConfirmApplicant'+message+'">'+message+'</em>';

        // Prepare action & effect.
        var anchor = this;
        var container = $(anchor).closest('td');
        var transientKey = gdn.definition('TransientKey');
        var data = 'DeliveryType=BOOL&TransientKey=' + transientKey;

        // Do the action.
        $(container).html('<span class="AfterButtonLoading">&#160;</span>');
        $.post($(anchor).attr('href'), data, function(response) {
            if (response == 'TRUE') {
                //gdn.informMessage(message);
                // Do the effect.
                $(container).html(message);
            }
        });
        return false;
    });
});