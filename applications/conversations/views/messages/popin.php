<?php if (!defined('APPLICATION')) exit(); ?>
<ul class="PopList Conversations">
    <li class="Item Title">
        <?php
        if (checkPermission('Conversations.Conversations.Add'))
            echo anchor(t('New Message'), 'messages/add');
        echo wrap(t('Inbox'), 'strong');
        ?>
    </li>
    <?php
    if (count($this->data('Conversations'))):
        ?>
        <?php
        foreach ($this->data('Conversations') as $Row):

            $Subject = '';
            if ($Row['Subject']) {
                $Subject = Gdn_Format::text($Row['Subject']);
            } else {
                $Subject = ConversationModel::participantTitle($Row, false);
            }
            $PhotoUser = userBuilder($Row, 'LastInsert');
            ?>
            <li class="Item" rel="<?php echo url("/messages/{$Row['ConversationID']}#Message_{$Row['LastMessageID']}"); ?>">
                <div class="Author Photo"><?php echo userPhoto($PhotoUser, ['NoLink' => true]); ?></div>
                <div class="ItemContent">
                    <b class="Subject"><?php echo anchor(htmlspecialchars($Subject), "/messages/{$Row['ConversationID']}#Message_{$Row['LastMessageID']}"); ?></b>
                    <?php
                    $Excerpt = sliceString(Gdn_Format::plainText($Row['LastBody'], $Row['LastFormat']), 80);
                    echo wrap(nl2br(htmlspecialchars($Excerpt)), 'div', ['class' => 'Excerpt']);
                    ?>
                    <div class="Meta">
                        <?php
                        echo ' <span class="MItem">'.plural($Row['CountMessages'], '%s message', '%s messages').'</span> ';

                        if ($Row['CountNewMessages'] > 0) {
                            echo ' <strong class="HasNew"> '.plural($Row['CountNewMessages'], '%s new', '%s new').'</strong> ';
                        }

                        echo ' <span class="MItem">'.Gdn_Format::date($Row['LastDateInserted']).'</span> ';
                        ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        <li class="Item Center">
            <?php
            echo anchor(t('All Messages'), '/messages/inbox');
            ?>
        </li>
    <?php else: ?>
        <li class="Item Empty Center"><?php echo t('Your inbox is empty.', sprintf(t('You do not have any %s yet.'), t('messages'))); ?></li>
    <?php endif; ?>
</ul>
