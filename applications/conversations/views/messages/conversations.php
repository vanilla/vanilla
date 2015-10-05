<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$Alt = FALSE;
$SubjectsVisible = c('Conversations.Subjects.Visible');

foreach ($this->data('Conversations') as $Conversation) {
    $Conversation = (object)$Conversation;
    $Alt = $Alt == TRUE ? FALSE : TRUE;


    // Figure out the last photo.
    $LastPhoto = '';
    if (empty($Conversation->Participants)) {
        $User = Gdn::userModel()->getID($Conversation->LastInsertUserID);
        $LastPhoto = userPhoto($User);
    } else {
        foreach ($Conversation->Participants as $User) {
            if ($User['UserID'] == $Conversation->LastInsertUserID) {
                $LastPhoto = userPhoto($User);
                if ($LastPhoto)
                    break;
            } elseif (!$LastPhoto) {
                $LastPhoto = userPhoto($User);
            }
        }
    }

    $CssClass = 'Item';
    $CssClass .= $Alt ? ' Alt' : '';
    $CssClass .= $Conversation->CountNewMessages > 0 ? ' New' : '';
    $CssClass .= $LastPhoto != '' ? ' HasPhoto' : '';
    $CssClass .= ' '.($Conversation->CountNewMessages <= 0 ? 'Read' : 'Unread');

    $JumpToItem = $Conversation->CountMessages - $Conversation->CountNewMessages;
    $Message = (sliceString(Gdn_Format::plainText($Conversation->LastBody, $Conversation->LastFormat), 100));

    if (stringIsNullOrEmpty(trim($Message))) {
        $Message = t('Blank Message');
    }

    $this->EventArguments['Conversation'] = $Conversation;
    ?>
    <li class="<?php echo $CssClass; ?>">
        <?php
        $Names = ConversationModel::participantTitle($Conversation, false);
        ?>
        <div class="ItemContent Conversation">
            <?php
            $Url = '/messages/'.$Conversation->ConversationID.'/#Item_'.$JumpToItem;

            echo '<h3 class="Users">';

            if ($Names) {
                if ($LastPhoto) {
                    echo '<div class="Author Photo">'.$LastPhoto.'</div>';
                }

                echo anchor(htmlspecialchars($Names), $Url);
            }
            if ($Subject = val('Subject', $Conversation)) {
                if ($Names) {
                    echo Bullet(' ');
                }
                echo '<span class="Subject">'.anchor(htmlspecialchars($Subject), $Url).'</span>';
            }

            echo '</h3>';
            ?>
            <div class="Excerpt"><?php echo anchor(htmlspecialchars($Message), $Url, 'Message'); ?></div>
            <div class="Meta">
                <?php
                $this->fireEvent('BeforeConversationMeta');

                echo ' <span class="MItem CountMessages">'.sprintf(Plural($Conversation->CountMessages, '%s message', '%s messages'), $Conversation->CountMessages).'</span> ';

                if ($Conversation->CountNewMessages > 0) {
                    echo ' <strong class="HasNew"> '.plural($Conversation->CountNewMessages, '%s new', '%s new').'</strong> ';
                }

                echo ' <span class="MItem LastDateInserted">'.Gdn_Format::date($Conversation->LastDateInserted).'</span> ';
                ?>
            </div>
        </div>
    </li>
<?php
}
