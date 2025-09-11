# Comment Item

## Props in `CommentItem.Props`

**`comment`**  
Contains the comment body, reactions, and other metadata.

**`editor`**  
The editor instance used to update a comment.

**`warnings`**  
Renders any warnings attached to the comment item.

**`isEditing`**  
Determines if the comment is in editing mode.

**`attachmentsContent`**  
Renders associated tickets for the comment (Vanilla and third-party escalations).

**`showOPTag`**  
Determines if the comment is by the original poster.

**`isHighlighted`**  
Determines if the comment is highlighted.

**`onReply`**  
Callback fired when the reply button is clicked.

**`replyLabel`**  
Text of the reply button.

**`isHidden`**  
Determines if the comment should be hidden from the sessioned user.

## Exports in `CommentItem`

**`CommentReactions`**  
Displays reactions associated with the comment.

**`UserSignature`**  
Displays the signature of the user who posted the comment.

**`ContentItemPermalink`**  
Provides a permalink to the comment.

**`ReplyButton`**  
A button to reply to the comment.

**`OptionsMenu`**  
Displays a dropdown menu with options for the comment.

**`CommentEditor`**  
Renders the editor for editing the comment.

**`Warnings`**  
Displays warnings related to the comment.

**`Attachments`**  
Displays attachments associated with the comment.

**`AuthorBadges`**  
Displays badges for the author of the comment.

**`ModerationCheckBox`**  
Renders a checkbox for moderation purposes.

**`IgnoredUserContent`**  
Displays content for ignored users.
