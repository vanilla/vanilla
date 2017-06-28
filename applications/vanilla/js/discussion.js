jQuery(document).ready(function($) {
    if (gdn.definition('NotifyNewDiscussion', false))
        $.post(gdn.url('/post/notifynewdiscussion?discussionid=' + gdn.definition('DiscussionID', '')));
    /* Comment Form */

    // Hide it if they leave the area without typing
    $('div.CommentForm textarea').blur(function(ev) {
        var Comment = $(ev.target).val();
        if (!Comment || Comment == '')
            $('a.Cancel').hide();
    });

    // Reveal the textarea and hide previews.
    $(document).on('click', 'a.WriteButton, a.Cancel', function() {
        if ($(this).hasClass('WriteButton')) {
            var frm = $(this).parents('.MessageForm').find('form');
            frm.trigger('WriteButtonClick', [frm]);
        }

        resetCommentForm(this);
        if ($(this).hasClass('Cancel'))
            clearCommentForm(this);

        return false;
    });

    // Hijack comment form button clicks.
    var draftSaving = 0;
    $(document).on('click', '.CommentButton, a.PreviewButton, a.DraftButton', function() {
        var btn = this;
        var parent = $(btn).parents('div.CommentForm, div.EditCommentForm');
        var frm = $(parent).find('form').first();
        var textbox = $(frm).find('textarea');
        var inpCommentID = $(frm).find('input:hidden[name$=CommentID]');
        var inpDraftID = $(frm).find('input:hidden[name$=DraftID]');
        var type = 'Post';
        var preview = $(btn).hasClass('PreviewButton');
        if (preview) {
            type = 'Preview';
            // If there is already a preview showing, kill processing.
            if ($('div.Preview').length > 0) {
                return false;
            }
        }
        var draft = $(btn).hasClass('DraftButton');
        if (draft) {
            type = 'Draft';
            // Don't save draft if string is empty
            if (jQuery.trim($(textbox).val()) == '')
                return false;

            if (draftSaving > 0)
                return false;

//         console.log('Saving draft: '+(new Date()).toUTCString());
            draftSaving++;
        }

        // Post the form, and append the results to #Discussion, and erase the textbox
        var postValues = $(frm).serialize();
        postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
        postValues += '&Type=' + type;
        var discussionID = $(frm).find('[name$=DiscussionID]');
        discussionID = discussionID.length > 0 ? discussionID.val() : 0;
        var tKey = $(frm).find('[name$=TransientKey]');
        var prefix = tKey.attr('name').replace('TransientKey', '');
        // Get the last comment id on the page
        var comments = $('ul.Comments li.ItemComment');
        var lastComment = $(comments).get(comments.length - 1);
        var lastCommentID = $(lastComment).attr('id');
        if (lastCommentID)
            lastCommentID = lastCommentID.indexOf('Discussion_') == 0 ? 0 : lastCommentID.replace('Comment_', '');
        else
            lastCommentID = 0;

        postValues += '&' + prefix + 'LastCommentID=' + lastCommentID;
        var action = $(frm).attr('action');
        if (action.indexOf('?') < 0)
            action += '?';
        else
            action += '&';

        if (discussionID > 0) {
            action += 'discussionid=' + discussionID;
        }

        $(frm).find(':submit').attr('disabled', 'disabled');
        $(parent).find('a.Back').after('<span class="TinyProgress">&#160;</span>');
        // Also add a spinner for comments being edited
        // $(btn).parents('div.Comment').find('div.Meta span:last').after('<span class="TinyProgress">&#160;</span>');
        $(frm).triggerHandler('BeforeSubmit', [frm, btn]);
        if (type != 'Draft')
            $(':submit', frm).addClass('InProgress');
        else
            $('.DraftButton', frm).addClass('InProgress');
        $.ajax({
            type: "POST",
            url: action,
            data: postValues,
            dataType: 'json',
            error: function(xhr) {
                gdn.informError(xhr, draft);
            },
            success: function(json) {
                var processedTargets = false;
                // If there are targets, process them
                if (json.Targets && json.Targets.length > 0) {
//               for(i = 0; i < json.Targets.length; i++) {
//                  if (json.Targets[i].Type != "Ajax") {
//                     json.Targets[i].Data = json.Data;
//                     processedTargets = true;
//                     break;
//                   }
//               }
                    gdn.processTargets(json.Targets);
                }

                // If there is a redirect url, go to it
                if (json.RedirectTo != null && jQuery.trim(json.RedirectTo) != '') {
                    resetCommentForm(btn);
                    clearCommentForm(btn);
                    window.location.replace(json.RedirectTo);
                    return false;
                }

                // Remove any old popups if not saving as a draft
                if (!draft && json.FormSaved == true)
                    $('div.Popup,.Overlay').remove();

                var commentID = json.CommentID;

                // Assign the comment id to the form if it was defined
                if (commentID != null && commentID != '') {
                    $(inpCommentID).val(commentID);
                }

                if (json.DraftID != null && json.DraftID != '')
                    $(inpDraftID).val(json.DraftID);

                if (json.MyDrafts != null) {
                    if (json.CountDrafts != null && json.CountDrafts > 0)
                        json.MyDrafts += '<span>' + json.CountDrafts + '</span>';

                    $('ul#Menu li.MyDrafts a').html(json.MyDrafts);
                }

                // Remove any old errors from the form
                $(frm).find('div.Errors').remove();
                if (json.FormSaved == false) {
                    $(frm).prepend(json.ErrorMessages);
                    json.ErrorMessages = null;
                } else if (preview) {
                    // Reveal the "Edit" button and hide this one
                    $(btn).hide();
                    $(parent).find('.WriteButton').removeClass('Hidden');

                    $(frm).find('.TextBoxWrapper').hide().afterTrigger(json.Data);
                    $(frm).trigger('PreviewLoaded', [frm]);

                } else if (!draft) {
                    // Clean up the form
                    if (processedTargets)
                        btn = $('div.CommentForm :submit, div.EditCommentForm :submit');

                    resetCommentForm(btn);
                    clearCommentForm(btn);

                    // If editing an existing comment, replace the appropriate row(s).
                    // There is a small possibility that there are multiple time the same comment on the page
                    var existingCommentRows = $('.ItemComment[id="Comment_' + commentID +'"]');
                    if (processedTargets) {
                        // Don't do anything with the data b/c it's already been handled by processTargets
                    } else if (existingCommentRows.length > 0) {
                        existingCommentRows.each(function(i, element) {
                            $(element).afterTrigger(json.Data);
                            $(element).remove();
                            $(element).effect("highlight", {}, "slow");
                        });
                    } else {
                        gdn.definition('LastCommentID', commentID, true);
                        // If adding a new comment, show all new comments since the page last loaded, including the new one.
                        if (gdn.definition('PrependNewComments') == '1') {
                            $(json.Data).prependTo('ul.Comments,.DiscussionTable');
                            $('ul.Comments li:first').effect("highlight", {}, "slow");
                        } else {
                            $(json.Data)
                                .appendTo('ul.Comments,.DiscussionTable')
                                .effect("highlight", {}, "slow")
                                .trigger('contentLoad');
//                     $('ul.Comments li:last,.DiscussionTable li:last').effect("highlight", {}, "slow");
                        }
                    }
                    // Remove any "More" pager links (because it is typically replaced with the latest comment by this function)
                    if (gdn.definition('PrependNewComments') != '1') // If prepending the latest comment, don't remove the pager.
                        $('#PagerMore').remove();

                    // Set the discussionid on the form in case the discussion was created by adding the last comment
                    var discussionID = $(frm).find('[name$=DiscussionID]');
                    if (discussionID.length == 0 && json.DiscussionID) {
                        $(frm).append('<input type="hidden" name="' + prefix + 'DiscussionID" value="' + json.DiscussionID + '">');
                    }

                    // Let listeners know that the comment was added.
                    $(document).trigger('CommentAdded');
                    $(frm).triggerHandler('complete');
                }
                gdn.inform(json);
                return false;
            },
            complete: function(XMLHttpRequest, textStatus) {
                // Remove any spinners, and re-enable buttons.
                $(':submit', frm).removeClass('InProgress');
                $('.DraftButton', frm).removeClass('InProgress');
                $(frm).find(':submit').removeAttr("disabled");
                if (draft)
                    draftSaving--;
            }
        });
        frm.triggerHandler('submit');
        return false;
    });

    function resetCommentForm(sender) {
        var parent = $(sender).parents('.CommentForm, .EditCommentForm');
        $(parent).find('.Preview').remove();
        $(parent).find('.TextBoxWrapper').show();
        $('.TinyProgress').remove();

        parent.find('.PreviewButton').show();
        parent.find('.WriteButton').addClass('Hidden');
    }

    // Utility function to clear out the comment form
    function clearCommentForm(sender, deleteDraft) {
        var container = $(sender).parents('.Editing');

        // By default, we delete comment drafts, unless sender was a "Post Comment" button. Can be overriden.
        if (typeof deleteDraft !== 'undefined') {
            deleteDraft = !!deleteDraft;
        } else if ($(sender).hasClass('CommentButton')) {
            deleteDraft = false;
        } else {
            deleteDraft = true
        }

        $(container).removeClass('Editing');
        $('div.Popup,.Overlay').remove();
        var frm = $(sender).parents('div.CommentForm, .EditCommentForm');
        frm.find('textarea').val('');
        frm.find('input:hidden[name$=CommentID]').val('');
        // Erase any drafts
        var draftInp = frm.find('input:hidden[name$=DraftID]');
        if (deleteDraft && draftInp.val() != '') {
            $.ajax({
                type: "POST",
                url: gdn.url('/drafts/delete/' + draftInp.val() + '/' + gdn.definition('TransientKey')),
                data: 'DeliveryType=BOOL&DeliveryMethod=JSON',
                dataType: 'json'
            });
        }

        draftInp.val('');
        frm.find('div.Errors').remove();
        $('div.Information').fadeOut('fast', function() {
            $(this).remove();
        });
        $(sender).closest('form').trigger('clearCommentForm');
    }

    // Set up paging
    if ($.morepager)
        $('.MorePager').not('.Message .MorePager').morepager({
            pageContainerSelector: 'ul.Comments',
            afterPageLoaded: function() {
                $(document).trigger('CommentPagingComplete');
            }
        });

    // Autosave comments
    if ($.fn.autosave) {
        $('div.CommentForm textarea').autosave({
            button: $('a.DraftButton')
        });
    }


    /* Options */

    // Edit comment
    $(document).on('click', 'a.EditComment', function() {
        var btn = this;
        var container = $(btn).closest('.ItemComment');
        $(container).addClass('Editing');
        var parent = $(container).find('div.Comment');
        var msg = $(parent).find('div.Message').first();
        $(parent).find('div.Meta span:last').after('<span class="TinyProgress">&#160;</span>');
        if (!parent.find('.EditCommentForm').length) {
            $.ajax({
                type: "GET",
                url: $(btn).attr('href'),
                data: 'DeliveryType=VIEW&DeliveryMethod=JSON',
                dataType: 'json',
                error: function(xhr) {
                    gdn.informError(xhr);
                },
                success: function(json) {
                    $(msg).afterTrigger(json.Data);
                    $(msg).hide();
                    $(document).trigger('EditCommentFormLoaded', [container]);
                },
                complete: function() {
                    $(parent).find('span.TinyProgress').remove();
                    $(btn).closest('.Flyout').hide().closest('.ToggleFlyout').removeClass('Open');
                }
            });
        } else {
            resetCommentForm($(parent).find('form'));
            clearCommentForm($(parent).find('form'));
            $(parent).find('div.EditCommentForm').remove();
            $(parent).find('span.TinyProgress').remove();
            $(msg).show();
        }

        $(document).trigger('CommentEditingComplete', [msg]);
        return false;
    });
    // Reveal the original message when cancelling an in-place edit.
    $(document).on('click', '.Comment .Cancel a, .Comment a.Cancel', function() {
        var btn = this;
        var $container = $(btn).closest('.ItemComment');

        $(btn).closest('.Comment').find('div.Message').show();
        $(btn).closest('.CommentForm, .EditCommentForm').remove();
        $container.removeClass('Editing');
        return false;
    });

    // Delete comment
    $('a.DeleteComment').popup({
        confirm: true,
        confirmHeading: gdn.definition('ConfirmDeleteCommentHeading', 'Delete Comment'),
        confirmText: gdn.definition('ConfirmDeleteCommentText', 'Are you sure you want to delete this comment?'),
        followConfirm: false,
        deliveryType: 'BOOL', // DELIVERY_TYPE_BOOL
        afterConfirm: function(json, sender) {
            var row = $(sender).parents('li.ItemComment');
            if (json.ErrorMessage) {
                $.popup({}, json.ErrorMessage);
            } else {
                // Remove the affected row
                $(row).slideUp('fast', function() {
                    $(this).remove();
                });
                gdn.processTargets(json.Targets);
            }
        }
    });

//   var gettingNew = 0;
//   var getNew = function() {
//      if (gettingNew > 0) {
//         return;
//      }
//      gettingNew++;
//
//      discussionID = gdn.definition('DiscussionID', 0);
//      lastCommentID = gdn.definition('LastCommentID', '');
//      if(lastCommentID == '')
//         return;
//
//      $.ajax({
//         type: "POST",
//         url: gdn.url('/discussion/getnew/' + discussionID + '/' + lastCommentID),
//         data: "DeliveryType=ASSET&DeliveryMethod=JSON",
//         dataType: "json",
//         error: function(xhr) {
//            gdn.informError(xhr, true);
//         },
//         success: function(json) {
//            if(json.Data && json.LastCommentID) {
//               gdn.definition('LastCommentID', json.LastCommentID, true);
//               $(json.Data).appendTo("ul.Comments")
//                  .effect("highlight", {}, "slow");
//            }
//            gdn.processTargets(json.Targets);
//         },
//         complete: function() {
//            gettingNew--;
//         }
//      });
//   }
//
//   // Load new comments like a chat.
//   var autoRefresh = gdn.definition('Vanilla_Comments_AutoRefresh', 0) * 1000;
//   if (autoRefresh > 1000) {
//      window.setInterval(getNew, autoRefresh);
//   }

    /* Comment Checkboxes */
    $('.AdminCheck [name="Toggle"]').click(function() {
        if ($(this).prop('checked'))
            $('.DataList .AdminCheck :checkbox').prop('checked', true).change();
        else
            $('.DataList .AdminCheck :checkbox').prop('checked', false).change();
    });
    $('.AdminCheck :checkbox').click(function(e) {
        e.stopPropagation();
        // retrieve all checked ids
        var checkIDs = $('.DataList .AdminCheck :checkbox');
        var aCheckIDs = new Array();
        var discussionID = gdn.definition('DiscussionID');
        checkIDs.each(function() {
            checkID = $(this);

            aCheckIDs[aCheckIDs.length] = {
                'checkId': checkID.val(),
                'checked': checkID.prop('checked') || '' // originally just, wrong: checkID.attr('checked')
            };
        });
        $.ajax({
            type: "POST",
            url: gdn.url('/moderation/checkedcomments'),
            data: {
                'DiscussionID': discussionID,
                'CheckIDs': aCheckIDs,
                'DeliveryMethod': 'JSON',
                'TransientKey': gdn.definition('TransientKey')
            },
            dataType: "json",
            error: function(xhr) {
                gdn.informError(xhr, true);
            },
            success: function(json) {
                gdn.inform(json);
            }
        });
    });
});
