<?php if (!defined('APPLICATION')) exit();
$SubjectsVisible = c('Conversations.Subjects.Visible');
?>
<div id="InboxModule" class="Box BoxInbox">
    <h4><?php echo t('Inbox'); ?></h4>
    <?php if (count($this->data('Conversations')) > 0): ?>

        <ul id="" class="DataList Conversations PanelInfo">
            <?php foreach ($this->data('Conversations') as $Row): ?>
                <li id="Conversation_<?php echo $Row['ConversationID']; ?>" class="Item">
                    <?php
                    $JumpToItem = $Row['CountMessages'] - $Row['CountNewMessages'];
                    $Url = "/messages/{$Row['ConversationID']}/#Item_$JumpToItem";

                    if ($SubjectsVisible && $Row['Subject'])
                        $Message = htmlspecialchars($Row['Title']);
                    elseif ($Row['Format'] == 'Text')
                        $Message = (sliceString(Gdn_Format::to($Row['LastMessage'], $Conversation['Format']), 100));
                    else
                        $Message = (sliceString(Gdn_Format::text(Gdn_Format::to($Row['LastMessage'], $Row['Format']), false), 100));

                    if (stringIsNullOrEmpty(trim($Message)))
                        $Message = t('Blank Message');

                    echo anchor($Message, $Url, 'ConversationLink');
                    ?>
                    <div class="Meta">
            <span class="MItem Participants">
               <?php
               $First = TRUE;
               foreach ($Row['Participants'] as $User) {
                   if ($First)
                       $First = FALSE;
                   else
                       echo ', ';

                   if ($User['UserID'] == Gdn::session()->UserID)
                       $User['Name'] = t('You');

                   echo userAnchor($User);
               }
               ?>
            </span>
            <span class="MItem CountMessages">
               <?php
               echo plural($Row['CountMessages'], '%s message', '%s messages');
               ?>
            </span>
            <span class="MItem DateLastMessage">
               <?php
               echo Gdn_Format::date($Row['DateLastMessage']);
               ?>
            </span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="P PagerContainer">
            <?php
            if (checkPermission('Conversations.Conversations.Add'))
                echo anchor(sprite('SpNewConversation SpAdd').t('New Message'), '/messages/add');
            ?>
            <span class="Pager"><?php echo anchor(t('More…'), '/messages/inbox'); ?></span>
        </div>
    <?php else: ?>
        <?php
        echo wrap(t('Your private conversations with other members.'), 'div', array('class' => 'P'));
        ?>
        <div class="P PagerContainer">
            <?php
            if (checkPermission('Conversations.Conversations.Add'))
                echo anchor(sprite('SpNewConversation SpAdd').t('New Message'), '/messages/add');
            ?>
        </div>
    <?php endif; ?>
</div>
